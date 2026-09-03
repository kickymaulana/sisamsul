# 4 — LEGACY DECODER: SISAMSUL

## Stack
- Laravel 13.3 / PHP 8.5 / Inertia 3 / Vue 3 / TypeScript 6 / Tailwind 4
- shadcn-vue (reka-ui), @tanstack/vue-table, vue-sonner, unovis
- MariaDB, spatie/laravel-permission, barryvdh/laravel-dompdf, tightenco/ziggy
- WA gateway (HTTP POST, SSL verify disabled)

## Struktur Dir
```
app/
├── Http/
│   ├── Controllers/      (11 + Auth + Master)
│   ├── Middleware/        HandleInertiaRequests (shared auth, roles, flash)
│   └── ...
├── Models/                (6: Sample, Formulir, DepartemenTerlibat, Departemen, SubDepartemen, User)
├── Notifications/         SampelSiapDiproses (database channel only)
├── Providers/
resources/
├── js/
│   ├── app.ts             (entry: createInertiaApp + ZiggyVue)
│   ├── bootstrap.js       (axios global)
│   ├── Layouts/           AuthenticatedLayout.vue
│   ├── Pages/             (Auth, Dashboard, Formulir, Sample, TugasProduksi, PersetujuanManager, dll)
│   ├── components/        (AppSidebar, NavMain, NavUser, SiteHeader, Master, DataTable, DragHandle, ChartArea...)
│   └── components/ui/     (shadcn-vue style: sidebar, input, button, card, table, alert-dialog, select, field, chart, sonner)
├── views/
│   ├── app.blade.php      (root: @routes + @vite + Inertia head)
│   └── pdf.blade.php      (dompdf template)
routes/
├── web.php                (all routes)
└── console.php
database/
├── migrations/            (11 files)
└── seeders/               (7 files: Departemen, SubDepartemen, Role, Permission, User, ModelHasRole)
```

## Alur Request
1. `resources/views/app.blade.php` → @routes (Ziggy) + @vite('resources/js/app.ts')
2. `app.ts` → createInertiaApp → resolve `./Pages/${name}.vue` via glob
3. HandleInertiaRequests share: `auth.user`, `auth.roles`, `auth.unreadNotificationsCount`, `flash.success|error`
4. Route `web.php` → middleware `auth` + `role:X` → Controller → `Inertia::render('Folder/Page')`

## Role-Based Routing
**DashboardController → redirect by role:**
- QC Manager / Factory Manager / General Manager → persetujuan.manager.index
- Manager / Supervisor / Leader / Operator → tugas.produksi.index
- admin / Quality Control → Dashboard/Index (asli)

**Sidebar vue** (AppSidebar.vue) — filter menu duplikat:
- admin/QC: Sampel, Formulir, Master Data
- admin/Operator/Leader/Manager/Supervisor: Tugas Produksi
- admin/QC Manager/Factory Manager: Persetujuan Manager
- Semua: Daftar Pengguna

## Alur Bisnis
```
Sample (QC/admin buat)
  → Formulir (QC/admin buat, pilih sample + size + running_ke)
    → jika running_ke > 1: duplikat departemen_terlibat dari formulir sebelumnya (sampel+size sama), reset actual/paraf/tanggal
    → status "Proses" → notif WA + in-app ke user departemen pertama
      → Tugas Produksi: operator "terima" (validasi: semua dept urutan sebelumnya sudah diterima + paraf QC, kecuali GLAZE)
        → isi qty, data_tambahan, item_pemeriksaan[item,spec,actual]
        → SPV "paraf":
            - user id 32 (Dina) → notif ke Afrida (id 2) → halaman persetujuan manager
            - default → notif ke Sarah (id 5) → halaman paraf QC
          → QC "paraf":
            - ada dept selanjutnya → notif ke Manager/Supervisor dept berikutnya
            - dept terakhir (FQC) → notif ke user id 32
              → Manager "pemeriksa" (QC Manager) → cek semua paraf_spv IS NOT NULL + tanggal_selesai IS NOT NULL
                → notif ke user id 3 (Pak Parinton)
                → Manager "penyetuju" (Factory Manager) → status = "Selesai"
                  → PDF export via dompdf
```

## Catatan Arsitektur
- Models pakai PHP 8 attributes `#[Table]`, `#[Fillable]`, `#[Hidden]`
- `DepartemenTerlibat`: JSON cast `data_tambahan` + `item_pemeriksaan` (array)
- `DepartemenTerlibat`: relasi `qcUser()` / `spvUser()` — nama method ≠ kolom DB (`paraf_qc` / `paraf_spv`)
- Session / Cache / Queue: semua `database` driver
- WA gateway: `Http::withoutVerifying()` + basicAuth + X-Device-Id header → POST JSON `{phone, message}`
- PDF: barryvdh/laravel-dompdf, `loadView('pdf', $data)` → stream response
- Notifikasi: database channel only (SampelSiapDiproses → `toArray()` → `{pesan, url}`)
- PDF preview: `PersetujuanManager/Show.vue` render dokumen asli (mirip PDF) + tombol export PDF

## Temuan Anomali
1. **Bug enum case**: `PersetujuanManagerController::index` filter `where('status', 'proses')` (lowercase) tapi enum di migration: `'Proses'` (capital P). Tidak akan match → halaman kosong.
2. **Hardcoded user IDs**: 2 (Afrida), 3 (Parinton), 5 (Sarah), 32 (Dina) di seluruh logika notifikasi. Rapuh terhadap perubahan seed.
3. **Hardcoded FQC ID**: `sub_departemen_id = 11` di query. Rapuh.
4. **Relasi `DepartemenTerlibat::sampel()`**: `HasOneThrough` argumen tidak konsisten — menggunakan `Sampel::class` (tidak ada) bukan `Sample::class`, dan argumen FK kemungkinan salah.
5. **Duplikasi `kirimWhatsApp()`**: 4 controller (Formulir, TugasProduksi, DepartemenTerlibat, PersetujuanManager) copy-paste method private yang sama.
6. **ParafSpv routing**: `switch($user->id)` hardcoded — hanya user id 32 (Dina) yang routing ke Afrida, sisanya ke Sarah. Tidak scalable.
7. **Kode mati**: Blok commment besar di `parafSpv()` (2 versi), `parafQc()` else branch (commented), dan beberapa route tidak dipakai.
8. **DepartemenSeeder duplikat ID**: `id => 9` dipakai 2x (FQC dan OFFICE QC) — akan timpa/konflik.
9. **No RefreshDatabase in tests**: Tests tidak bisa jalan karena tidak ada factory/seed setup.