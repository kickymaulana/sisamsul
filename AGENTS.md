# SISAMSUL — Agent Guide

## Stack
- Laravel 13.3 / PHP 8.5 / Inertia 3 / Vue 3 / Tailwind 4 / shadcn-vue (reka-ui)
- MariaDB, spatie/laravel-permission, barryvdh/laravel-dompdf, tightenco/ziggy
- WA gateway via HTTP POST (SSL verify disabled)

## Dev Commands
```bash
composer run dev          # concurrently: artisan serve + queue:listen + pail + npm run dev
composer run test         # config:clear + artisan test
composer run setup        # full bootstrap (composer install, .env, key, migrate, npm build)
npm run dev               # vite dev server
npm run build             # vite build
```

## DB & Env
- **DB:** MariaDB (`.env` — not sqlite; `.env.example` is misleading)
- session/cache/queue: all `database` driver
- **queue:listen MUST run** for notifications (WA + in-app)
- WA gateway config in `.env`: `WA_GATEWAY_*` → mapped via `config/services.php`

## Architecture
- Monolith. Entry: `resources/js/app.ts` (Vue + Inertia + Ziggy). Root template: `resources/views/app.blade.php`
- Layout: `resources/js/Layouts/AuthenticatedLayout.vue`
- UI components: `resources/js/components/ui/` (shadcn-vue style, reka-ui)
- Lib/utils: `resources/js/lib/utils.ts` (tailwind-merge + clsx)
- Shared Inertia props: `app/Http/Middleware/HandleInertiaRequests.php` (auth, roles, unread notifications count, flash)

## Models — PHP 8 Attributes
Use `#[Table]`, `#[Fillable]`, `#[Hidden]` attributes (not `$table`/`$fillable` properties).
`DepartemenTerlibat` casts `data_tambahan` & `item_pemeriksaan` as `array` (JSON).
Relasi `qcUser()` / `spvUser()` — method name differs from DB column (`paraf_qc` / `paraf_spv`).

## Role-Based Routing
`DashboardController` auto-redirects:
- `QC Manager|Factory Manager|General Manager` → persetujuan manager
- `Manager|Supervisor|Leader|Operator` → tugas produksi
- `admin|Quality Control` → dashboard utama

Route middleware: `role:admin|Quality Control` etc. via spatie `RoleMiddleware`.

## Workflow
1. **Sample** created → **Formulir** created (if `running_ke > 1`, auto-duplicate `departemen_terlibat` from previous run, reset `actual`, `tanggal_diterima`, `paraf_*`)
2. Status `Proses` triggers WA + in-app notification to first department's users
3. **Operator** clicks "terima" (validates: previous steps must be accepted + QC-parafed, except Glaze)
4. SPV **paraf** → routes to QC or QC Manager depending on user ID
5. QC **paraf** via `DepartemenTerlibatController::parafQc`
6. FQC (`sub_departemen_id = 11`): paraf_spv blocked until paraf_qc done
7. Manager **pemeriksa** + **penyetuju** via `PersetujuanManagerController`
8. **PDF** generated via `PdfController` (dompdf blade template)

## WA Gateway
Reused `kirimWhatsApp()` private method in 4 controllers. Uses `Http::withoutVerifying()`.
Format: `withBasicAuth` + `X-Device-Id` header → POST JSON `{phone, message}`.

## PDF
`PdfController::pdf()` → `resources/views/pdf.blade.php`. Uses `public_path('icon_mark.png')` for logo.
Eager load + join `sub_departemen` for ordering: `->join('sub_departemen', ...)->orderBy('sub_departemen.urutan')`.

## Tests
Only ExampleTest placeholders. No RefreshDatabase, no factories, no seed setup for tests.
Run: `composer run test`.

## Conventions
- All UI text in **Bahasa Indonesia**
- Flash messages: `session('success'|'error')` → shared as `flash` prop
- Pagination: 7–10 items per page, `withQueryString()`
- Git commits in Indonesian

## Deployment
Project under Apache subdirectory: `http://localhost/sisamsul/public`