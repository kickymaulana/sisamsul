<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Models\Formulir;
use App\Models\DepartemenTerlibat;
use App\Models\Sample;

class DashboardController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->hasAnyRole(['QC Manager', 'Factory Manager', 'General Manager'])) {
            return redirect()->route('persetujuan.manager.index');
        }

        if ($user->hasAnyRole(['Manager', 'Supervisor', 'Leader', 'Operator'])) {
            return redirect()->route('tugas.produksi.index');
        }

        $proses = Formulir::where('status', 'Proses');
        $belumDiterima = DepartemenTerlibat::whereNull('tanggal_diterima')
            ->whereHas('formulir', fn ($query) => $query->where('status', 'Proses'));
        $menungguQc = DepartemenTerlibat::whereNotNull('tanggal_diterima')
            ->whereNotNull('paraf_spv')
            ->whereNull('paraf_qc')
            ->whereHas('formulir', fn ($query) => $query->where('status', 'Proses'));

        $recentSamples = Formulir::with('sampel')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Formulir $formulir) => [
                'code' => $formulir->sampel?->kode_sample ?? '-',
                'customer' => $formulir->sampel?->customer ?? '-',
                'status' => $formulir->status,
                'date' => $formulir->created_at?->format('d M Y'),
            ]);

        return Inertia::render('Dashboard/Index', [
            'stats' => [
                'totalSamples' => Sample::count(),
                'processing' => (clone $proses)->count(),
                'pendingTasks' => (clone $belumDiterima)->count(),
                'pendingQc' => (clone $menungguQc)->count(),
                'pendingAfrida' => Formulir::where('status', 'Proses')->whereNull('diperiksa_oleh')->count(),
                'pendingParinton' => Formulir::whereNotNull('diperiksa_oleh')->whereNull('disetujui_oleh')->count(),
                'completedToday' => Formulir::where('status', 'Selesai')->whereDate('updated_at', today())->count(),
                'unreadNotifications' => $user->unreadNotifications()->count(),
            ],
            'recentSamples' => $recentSamples,
        ]);
    }

    public function testing()
    {
        return Inertia::render('Dashboard/Testing');
    }
}
