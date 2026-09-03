# 2 — TECH SPEC: SISAMSUL

## 1. Tech Stack
| Layer | Teknologi |
|-------|-----------|
| Backend | Laravel 13.3 (PHP 8.5) |
| Frontend | Vue 3.5 (TS 6) + Inertia 3 + Tailwind 4 |
| UI | shadcn-vue (reka-ui 2.9), tabler-icons, lucide-vue, vue-sonner |
| Table/Chart | @tanstack/vue-table 8, unovis 1.6 |
| Build | Vite 8 + laravel-vite-plugin 3 |
| DB | MariaDB (5.5+) |
| Auth | spatie/laravel-permission 7 (role-based, no permission gates) |
| PDF | barryvdh/laravel-dompdf 3.1 |
| Routing | tightenco/ziggy 2.6 (TS route helper) |
| WA | HTTP POST ke gateway eksternal (basic auth + device header) |
| Queue | database driver (wajib jalan untuk notifikasi) |

## 2. DB Design

### Entity-Relationship
```
users ──departemen_id──> departemen
users <──paraf_qc─── dep_terlibat
users <──paraf_spv── dep_terlibat
users <──diterima_oleh── dep_terlibat
users <──diperiksa_oleh── formulirs
users <──disetujui_oleh── formulirs

departemen ──> sub_departemen (1:N)
sub_departemen ──> departemen_terlibat (1:N)
formulirs ──> departemen_terlibat (1:N, cascade)
samples ──> formulirs (1:N, cascade)
```

### Tabel Utama
**samples**: id, kode_sample, diajukan_oleh, kepada, customer, model, spesifikasi, timestamps

**formulirs**: id, sampel_id FK→samples, size, qty_sampel_kirim, running_ke, tanggal_permintaan, status enum(Draft|Proses|Selesai|Ditolak), diperiksa_oleh FK→users nullable, disetujui_oleh FK→users nullable, timestamps

**departemen_terlibat**: id, formulir_id FK→formulirs cascade, sub_departemen_id FK→sub_departemen, tanggal_diterima nullable, diterima_oleh FK→users nullable, tanggal_selesai nullable, qty, paraf_qc FK→users nullable, paraf_spv FK→users nullable, data_tambahan JSON nullable, item_pemeriksaan JSON nullable, timestamps

**departemen**: id, nama, timestamps
**sub_departemen**: id, departemen_id FK, urutan (int), nama, timestamps

**users**: id, departemen_id FK nullable, name, username, whatsapp, email unique, password, timestamps

**spatie tables**: permissions, roles, model_has_roles, model_has_permissions, role_has_permissions

### Index/Constraint
- `samples.kode_sample`: unique via app validation (not DB unique)
- `formulirs.sampel_id` FK → samples.id CASCADE
- `departemen_terlibat.formulir_id` FK → formulirs.id CASCADE
- All user FKs: ON DELETE SET NULL

## 3. Interface

### Endpoints Utama
| Method | Path | Role | Controller |
|--------|------|------|------------|
| GET/POST | /login, /register | guest | Auth |
| POST | /logout | auth | Auth |
| GET | /dashboard | auth | DashboardController |
| CRUD | /master/users, /roles, /departemens, /sub-departemens | admin/QC | Master/* |
| CRUD | /samples | admin/QC | SampleController |
| CRUD | /formulirs | admin/QC | FormulirController |
| PATCH | /formulirs/{id}/departemen/{dt}/paraf-qc | admin/QC | DepartemenTerlibatController |
| GET | /tugas-produksi | auth | TugasProduksiController |
| PATCH | /tugas-produksi/{dt}/terima | auth | TugasProduksiController |
| PATCH | /formulirs/{f}/departemen/{dt}/paraf-spv | auth | TugasProduksiController |
| GET | /persetujuan-manager | QC Manager/FM/GM | PersetujuanManagerController |
| POST | /persetujuan-manager/{f}/paraf-pemeriksa | QC Manager/admin | PersetujuanManagerController |
| POST | /persetujuan-manager/{f}/paraf-penyetuju | Factory Manager/admin | PersetujuanManagerController |
| GET | /notifikasi/index | auth | NotifikasiController |
| GET | /pdf/formulir/{formulir} | public | PdfController |

### Shared Inertia Props
- `auth.user` (User object, nullable)
- `auth.roles` (string[])
- `auth.unreadNotificationsCount` (int)
- `flash.success` (string|null)
- `flash.error` (string|null)

## 4. Alur
Lihat `4-LEGACY-DECODER.md` → Alur Bisnis (diagram langkah lengkap teratas).

## 5. Keamanan
- Auth: Laravel session-based (login via username)
- Role: spatie RoleMiddleware (route `role:X`). Tidak ada permission gates/checks
- Tidak ada CSRF exemption (kecuali default Laravel)
- WA gateway: basic auth over HTTPS (but SSL verify disabled)
- Tidak ada rate limiting, audit log, atau soft delete