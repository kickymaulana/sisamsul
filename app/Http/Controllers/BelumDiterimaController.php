<?php

namespace App\Http\Controllers;

use App\Models\DepartemenTerlibat;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BelumDiterimaController extends Controller
{
    public function index(Request $request)
    {
        $isAdmin = $request->user()->hasAnyRole(['admin', 'Quality Control']);

        $tugasList = DepartemenTerlibat::query()
            ->join('sub_departemen', 'departemen_terlibat.sub_departemen_id', '=', 'sub_departemen.id')
            ->join('formulirs', 'departemen_terlibat.formulir_id', '=', 'formulirs.id')
            ->join('samples', 'formulirs.sampel_id', '=', 'samples.id')
            ->select('departemen_terlibat.*')
            ->where('formulirs.status', 'Proses')
            ->whereNull('departemen_terlibat.tanggal_diterima')
            ->when(! $isAdmin, function ($query) use ($request) {
                $query->where('sub_departemen.departemen_id', $request->user()->departemen_id);
            })
            ->with(['formulir.sampel', 'sub_departemen.departemen'])
            ->when($request->search, function ($query, $search) {
                $query->where('samples.kode_sample', 'like', "%{$search}%");
            })
            ->orderBy('departemen_terlibat.formulir_id')
            ->orderBy('sub_departemen.urutan')
            ->orderBy('formulirs.running_ke')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('TugasProduksi/BelumDiterima', [
            'tugas_list' => $tugasList,
            'filters' => $request->only(['search']),
            'is_admin' => $isAdmin,
        ]);
    }
}
