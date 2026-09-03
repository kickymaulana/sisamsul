# 3 — TASKS: SISAMSUL

## Bug Fixes (prioritas)

- [ ] **[BUG][Tinggi]** `PersetujuanManagerController::index()` — filter `where('status', 'proses')` tidak match enum `'Proses'`. Akibat: daftar persetujuan selalu kosong. Fix: `'Proses'`.
- [ ] **[BUG][Tinggi]** `DepartemenTerlibat::sampel()` — `HasOneThrough` pakai `Sampel::class` (tidak ada) + argumen FK salah. Relasi rusak. Fix: pakai `Sample::class` + argumen benar (`'id'` sampel, `'sampel_id'` formulir, `'formulir_id'`, `'id'`).
- [ ] **[BUG][Sedang]** `DepartemenSeeder` — `id => 9` di-insert 2x (FQC + OFFICE QC). Pakai `updateOrInsert`, baris kedua timpa baris pertama (id sama). Data OFFICE QC hilang. Fix: bedakan id.

## Refactor

- [ ] **[REF][Tinggi]** Hardcoded user IDs (2 Afrida, 3 Parinton, 5 Sarah, 32 Dina) tersebar di `TugasProduksiController::parafSpv`, `DepartemenTerlibatController::parafQc`, `PersetujuanManagerController`. Fix: pindah ke `config/` atau lookup by role/username.
- [ ] **[REF][Sedang]** Duplikasi `kirimWhatsApp()` di 4 controller (Formulir, TugasProduksi, DepartemenTerlibat, PersetujuanManager). Fix: pindah ke trait/service `WhatsAppGateway`.
- [ ] **[REF][Sedang]** Hardcoded `sub_departemen_id = 11` (FQC) di `FormulirController::index` + `PersetujuanManagerController`. Fix: nama konstan/binding.
- [ ] **[REF][Sedang]** `switch($user->id)` di `parafSpv` (TugasProduksiController:221-238) — hanya id 32 khusus, sisanya default. Rapuh. Fix: role-based mapping.
- [ ] **[REF][Sedang]** Kode mati: blok comment besar di `TugasProduksiController::parafSpv` (old 60 baris) + `DepartemenTerlibatController::parafQc` (else-branch commented ~35 baris). Fix: hapus, pindah ke git history.

## Optimasi

- [ ] **[OPT][Sedang]** Format pesan WA dibangun inline berulang (4+ tempat). Pindah ke helper/notifikasi class agar konsisten.
- [ ] **[OPT][Rendah]** `FormulirController::index` + `PersetujuanManagerController::index` transformasi koleksi via `->through()` — bisa digabung dengan eager-load, hindari double-query.

## Dokumentasi

- [ ] **[DOC]** Test setup belum jalan: tidak ada RefreshDatabase, factories, seed khusus test. Tambah `TestCase::setUp` refresh + seed.
- [ ] **[DOC]** README.md masih template default Laravel — belum deskripsi SISAMSUL.

## Prioritas Eksekusi
1. Fix `'proses'` → `'Proses'` (bug kecil, dampak besar)
2. Fix relasi `sampel()`
3. Fix seeder duplikat id
4. Refactor WA gateway + user IDs hardcode
5. Bersihkan kode mati