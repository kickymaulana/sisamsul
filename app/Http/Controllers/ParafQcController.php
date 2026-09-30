<?php

namespace App\Http\Controllers;

use App\Models\DepartemenTerlibat;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ParafQcController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->string('tab')->toString() ?: 'menunggu';
        $isAdmin = $request->user()->hasRole('admin');

        $tugasList = DepartemenTerlibat::query()
            ->join('sub_departemen', 'departemen_terlibat.sub_departemen_id', '=', 'sub_departemen.id')
            ->join('formulirs', 'departemen_terlibat.formulir_id', '=', 'formulirs.id')
            ->join('samples', 'formulirs.sampel_id', '=', 'samples.id')
            ->select('departemen_terlibat.*')
            ->when($tab === 'menunggu', function ($query) {
                $query->where('formulirs.status', 'Proses')
                    ->whereNotNull('departemen_terlibat.tanggal_diterima')
                    ->whereNotNull('departemen_terlibat.paraf_spv')
                    ->whereNull('departemen_terlibat.paraf_qc');
            })
            ->when($tab === 'afrida', function ($query) {
                $query->where('formulirs.diperiksa_oleh', 2);
            })
            ->when($tab === 'parinton', function ($query) {
                $query->where('formulirs.disetujui_oleh', 3);
            })
            ->when($request->search, function ($query, $search) {
                $query->where('samples.kode_sample', 'like', "%{$search}%");
            })
            ->with(['formulir.sampel', 'sub_departemen.departemen', 'qcUser', 'spvUser'])
            ->orderBy('departemen_terlibat.formulir_id')
            ->orderBy('sub_departemen.urutan')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('TugasProduksi/ParafQc', [
            'tugas_list' => $tugasList,
            'filters' => $request->only(['search', 'tab']),
            'tab' => $tab,
            'is_admin' => $isAdmin,
        ]);
    }
}
