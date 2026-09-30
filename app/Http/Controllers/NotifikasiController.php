<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class NotifikasiController extends Controller
{
    public function index(Request $request)
    {
        // Ambil query builder notifikasi milik user yang sedang login
        $notifikasiQuery = auth()->user()->notifications();

        // Fitur Pencarian berdasarkan pesan teks di dalam data JSON
        $notifikasi = $notifikasiQuery
            ->when($request->search, function ($query, $search) {
                $query->where('data->pesan', 'like', "%{$search}%")
                      ->orWhere('data->nomor_sampel', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Notifikasi/Index', [
            'notifikasi' => $notifikasi,
            'filters' => $request->only(['search'])
        ]);
    }

    public function bacaDanRedirect($id)
    {
        // Cari notifikasi berdasarkan ID milik user saat ini
        $notification = auth()->user()->notifications()->findOrFail($id);

        // Tandai sudah dibaca jika belum dibaca
        if ($notification->unread()) {
            $notification->markAsRead();
        }

        // Ambil URL tujuan yang disimpan saat memicu Notification class
        $urlTujuan = $notification->data['url'] ?? route('dashboard');

        // Normalisasi URL: ambil path+query, strip app base path, lalu rebuild
        // dengan url() agar otomatis pakai host & base path sesuai environment
        $parsed = parse_url($urlTujuan);
        $path   = $parsed['path'] ?? '/';
        $query  = isset($parsed['query']) ? '?' . $parsed['query'] : '';

        $basePath = rtrim((string) parse_url(config('app.url'), PHP_URL_PATH), '/');
        if ($basePath !== '' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }

        return redirect(url($path . $query))
            ->with('success', 'Notifikasi diperbarui.');
    }

    public function tandai_semua_dibaca(Request $request)
    {
        // 1. Ambil user yang sedang login
        $user = $request->user();

        // 2. Cari semua notifikasi yang belum dibaca (read_at IS NULL) dan update menjadi sekarang
        $user->unreadNotifications->markAsRead();

        // 3. Kembali ke halaman sebelumnya dengan pesan sukses (opsional)
        return redirect()->back()->with('success', 'Semua notifikasi berhasil ditandai sebagai dibaca.');
    }
}
