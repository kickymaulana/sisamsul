<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{

    public function index(Request $request)
    {
        $users = User::query()
            ->select('id', 'name', 'username', 'email', 'created_at', 'departemen_id')
            ->with(['departemen:id,nama'])
            ->with(['roles:id,name'])
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString(); // Sangat penting agar filter pencarian tidak hilang saat klik page 2

        return Inertia::render('Master/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search']) // Kirim balik input pencarian ke frontend
        ]);
    }

    public function create()
    {
        return Inertia::render('Master/Users/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|lowercase|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('users.index')->with('success', 'User berhasil dibuat.');
    }


    public function show(User $user)
    {
        // Load relasi departemen
        $user->load('departemen');

        return Inertia::render('Master/Users/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                // Ambil nama departemen jika ada, jika tidak tampilkan 'Tanpa Departemen'
                'departemen_nama' => $user->departemen->nama ?? 'Tanpa Departemen',
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $user->updated_at->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    public function edit(User $user)
    {
        return Inertia::render('Master/Users/Edit', [
            'user' => $user,
            'departemens' => \App\Models\Departemen::select('id', 'nama')->get()
        ]);
    }


    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username,' . $user->id],
            'whatsapp' => ['required', 'string', 'regex:/^628[0-9]{7,12}$/', 'unique:users,whatsapp,' . $user->id],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'departemen_id' => ['nullable', 'exists:departemen,id'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ], [
            // Custom message agar user tidak bingung dengan regex
            'whatsapp.regex' => 'Format nomor WhatsApp harus diawali dengan 628 diikuti 7–12 digit.',
        ]);

        // Update data dasar
        $user->name = $request->name;
        $user->username = $request->username;
        $user->whatsapp = $request->whatsapp;
        $user->email = $request->email;
        $user->departemen_id = $request->departemen_id;

        // Cek jika password diisi, baru kita update
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return redirect()
            ->route('users.show', $user->id)
            ->with('success', 'Data user berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        // Pastikan user tidak menghapus dirinya sendiri (Opsional tapi penting!)
        if (auth()->id() === $user->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'User berhasil dihapus selamanya.');
    }
}
