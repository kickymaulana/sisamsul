<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Departemen;
use App\Models\SsoApplication;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class SsoApplicationController extends Controller
{
    public function index()
    {
        return Inertia::render('Master/Users/SsoApplications', [
            'applications' => SsoApplication::oldest()->paginate(10)->withQueryString(),
            'departemens' => Departemen::select('id', 'nama')->orderBy('nama')->get(),
            'roles' => Role::where('guard_name', 'web')->orderBy('name')->pluck('name'),
        ]);
    }

    public function approve(Request $request, SsoApplication $application)
    {
        $data = $request->validate([
            'nik' => ['required', 'string', Rule::in([$application->nik]), Rule::unique('users', 'nik'), Rule::unique('users', 'username')],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')],
            'whatsapp' => ['required', 'string', 'regex:/^628[0-9]{7,12}$/', Rule::unique('users', 'whatsapp')],
            'departemen_id' => ['required', 'integer', Rule::exists('departemen', 'id')],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'string', 'distinct', Rule::exists('roles', 'name')->where('guard_name', 'web')],
        ], [
            'required' => ':attribute wajib diisi.',
            'unique' => ':attribute sudah digunakan akun lain.',
            'exists' => ':attribute tidak valid.',
            'in' => 'NIK harus sesuai identitas SSO pengajuan.',
            'whatsapp.regex' => 'WhatsApp harus berformat 628 diikuti 7–12 digit.',
            'email.lowercase' => 'Email harus menggunakan huruf kecil.',
        ]);

        try {
            DB::transaction(function () use ($application, $data) {
                $pending = SsoApplication::whereKey($application->id)->lockForUpdate()->firstOrFail();
                if (User::where('nik', $pending->nik)->orWhere('username', $pending->nik)->exists()
                    || User::where('whatsapp', $data['whatsapp'])->exists()) {
                    throw ValidationException::withMessages(['nik' => 'Data sudah digunakan akun lain. Periksa kembali pengajuan.']);
                }
                $user = User::create([
                    'nik' => $pending->nik,
                    'username' => $pending->nik,
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'whatsapp' => $data['whatsapp'],
                    'departemen_id' => $data['departemen_id'],
                    'password' => Str::random(64),
                ]);
                $user->syncRoles($data['roles']);
                $pending->delete();
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['nik' => 'NIK atau email sudah digunakan akun lain. Pengajuan tidak digabungkan.']);
        }

        return redirect()->route('sso-applications.index')->with('success', 'Pengajuan disetujui. Pengguna dapat masuk melalui SSO.');
    }
}
