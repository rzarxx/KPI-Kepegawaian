# IMPLEMENTATION PLAN — KPI Kepegawaian

## Phase 0 — Bootstrap
- Laravel 13
- React/TypeScript/Inertia
- Tailwind
- shadcn/ui
- Lucide
- MySQL
- test framework
- lint/format
- local Inter font

## Phase 1 — Identity & Authorization
- auth
- users
- roles
- permissions
- organizational scopes
- policies
- audit baseline

Exit:
scope tests pass.

## Phase 2 — Organization
- branch
- division
- sub division
- position

## Phase 3 — Employee
- employee
- assignment
- status history
- create/edit/detail
- transfer/resign
- track record

## Phase 4 — Evaluation Configuration
- periods
- components
- weights
- criteria
- tenure rules

## Phase 5 — Evaluation Flow
- draft
- score input
- server total
- criteria
- finalize
- audit

## Phase 6 — Incident & Attention
- incident
- severity
- resolve
- rule engine
- dashboard warning
- notification

## Phase 7 — Reporting
- filters
- report page
- XLSX
- queue export
- audit

## Phase 8 — PWA
- manifest
- icons
- service worker
- cache policy
- offline page
- update prompt
- install UX

## Phase 9 — Security Hardening
- CSP
- headers
- rate limiting
- upload hardening
- private storage
- permission regression

## Phase 10 — Production
- aaPanel
- Nginx
- Supervisor
- scheduler
- backup
- smoke test


## Phase 11 — Update Besar (28 September 2026)

Enam perubahan besar yang harus diimplementasikan secara berurutan:

### 11.1 Penilaian Ad-Hoc (Tanpa Periode Wajib)

Perubahan:
- `period_id` pada `employee_evaluations` menjadi nullable.
- Route penilaian tidak lagi mewajibkan parameter `{period}`.
- Form penilaian menampilkan dropdown periode opsional (nullable).
- `SaveEmployeeEvaluationAction` tidak menolak penilaian tanpa periode.
- Dashboard dan laporan tetap dapat memfilter berdasarkan periode.
- Migration: alter `period_id` nullable, buat index baru.

Test:
- Penilaian berhasil disimpan tanpa periode.
- Penilaian dengan periode tetap berfungsi.
- Filter dashboard/laporan tetap benar.

### 11.2 Dashboard Pie Chart

Perubahan:
- `DashboardController` mengirim data distribusi per divisi, per cabang, dan
  per status karyawan.
- Data mengikuti organizational scope dan filter aktif.
- Frontend `Dashboard.tsx` menampilkan tiga `PieChart` (Recharts) di bawah
  summary cards.
- Warna konsisten dengan DESIGN.md (hijau, biru, amber, slate).
- Empty state bila data nol.

Test:
- Distribusi per divisi cocok dengan data.
- Distribusi per cabang cocok dengan scope.
- Distribusi per status cocok.

### 11.3 Riwayat Kerja Hanya HRD/SDM

Perubahan:
- Tambah permission baru: `employee.manage_track_record`.
- Hanya HR Admin dan HR Manager mendapat permission ini.
- Policy `EmployeePolicy` menambah method `manageTrackRecord`.
- Controller riwayat kerja memeriksa permission sebelum create/update.
- Kepala Divisi/Sub Divisi/Cabang hanya melihat, tidak mengubah.
- UI menyembunyikan tombol tambah/ubah riwayat untuk role non-HRD.

Test:
- HR Admin dapat menambah riwayat kerja.
- Division Head tidak dapat menambah riwayat kerja (403).
- Division Head dapat melihat riwayat kerja dalam scope.

### 11.4 Perbaikan Sistem Impersonasi Super Admin

Audit dan perbaikan:
- Verifikasi session regeneration saat mulai dan selesai.
- Verifikasi blocking aksi sensitif (password, role, permission, scope,
  pengaturan sistem, branding, buat pengguna, impersonasi berantai).
- Verifikasi auto-end saat timeout, akun nonaktif, izin dicabut.
- Verifikasi audit log lengkap untuk setiap event.
- Perbaiki bug atau gap yang ditemukan.
- Tambah middleware block untuk route branding/settings saat impersonasi.

Test:
- Impersonasi mulai: session regenerated, audit tercatat.
- Aksi terlarang menghasilkan 403 saat impersonasi.
- Timeout mengakhiri sesi dan memulihkan akun asal.
- Impersonasi berantai ditolak.

### 11.5 Perbaikan Copywriting Seluruh Halaman

Audit dan perbaikan:
- Audit seluruh halaman frontend terhadap standar AGENTS.md bagian 4.
- Perbaiki istilah yang tidak konsisten.
- Perbaiki heading, label, tombol, placeholder, pesan error, pesan sukses,
  empty state, dan tooltip.
- Pastikan tidak ada teks bahasa Inggris yang terekspos ke pengguna.
- Perbaiki typo dan kalimat yang kurang jelas.

Cakupan halaman:
- Login, Beranda, Karyawan (Index, Create, Edit, Show, Incidents, ProblemIndex),
  Penilaian (Configuration, Form), Laporan (Index), Notifikasi (Index),
  Organisasi (Index), Pengguna (Index), Profil (Edit), Audit (Index),
  Welcome, Layout (AuthenticatedLayout, GuestLayout).

### 11.6 Branding dan Tampilan Sistem oleh Super Admin

Perubahan:
- Migration: buat tabel `app_settings` (key, value, type, group).
- Seeder: default settings (app_name, app_logo, primary_color, footer_text).
- Model `AppSetting` dengan cache.
- Controller `AppSettingsController` (CRUD, hanya Super Admin).
- Route: GET/PUT `/pengaturan/tampilan` dengan middleware `not-impersonating`.
- Halaman frontend `Settings/Appearance.tsx` untuk kelola branding.
- `HandleInertiaRequests` menyebarkan branding settings ke semua halaman.
- Layout menggunakan dynamic logo, warna, footer, dan nama dari settings.
- Upload logo ke `storage/app/private/branding/` dengan validasi.
- Endpoint publik untuk serve logo (tanpa auth, karena dipakai di login).
- Audit log untuk setiap perubahan branding.

Test:
- Super Admin dapat mengubah logo, warna, footer, nama.
- Perubahan berlaku di semua halaman.
- Non-Super Admin tidak dapat mengakses pengaturan tampilan (403).
- Perubahan branding tercatat di audit log.

### Exit Criteria Phase 11

- Penilaian ad-hoc berfungsi (dengan dan tanpa periode).
- Dashboard menampilkan 3 pie chart yang benar.
- Riwayat kerja hanya dapat dikelola HRD/SDM.
- Impersonasi aman dan lengkap.
- Copywriting konsisten Bahasa Indonesia di semua halaman.
- Branding dapat dikelola Super Admin.
- Seluruh test terkait lulus.
- Dokumentasi tersinkronisasi.

---

## Checkpoint Lanjutan — 18 September 2026

### Pembaruan verifikasi — 24 September 2026

- Figma `EmIOtdlEaEnJR1f4PryW4m` diperiksa ulang; file masih hanya memiliki
  halaman/frame Fondasi, tanpa frame layar detail karyawan atau penilaian.
- Gap UX **Mulai Penilaian** sudah ditutup pada detail karyawan. Backend hanya
  mengirim periode aktif setelah policy dan organizational scope lulus, serta
  mengarahkan penilai ke draf miliknya bila sudah tersedia.
- Editor pengguna kini mempertahankan banyak organizational scope. Profil,
  kata sandi, dan penghapusan akun diaudit serta diblokir selama impersonasi.
- Props root dan dialog khusus yang tidak digunakan sudah dihapus; flash Inertia
  sukses/gagal sekarang dirender pada layout utama.
- Quality gate terbaru lulus: `89 test (519 assertion)`, Pint, ESLint,
  TypeScript, dan production build dengan 4.005 modul.
- Setelah Laragon diaktifkan, MySQL 8.0.30 terhubung dan seluruh 22 migration
  berstatus `Ran`. Seeder domain/RBAC/UAT terbukti idempotent pada MySQL,
  `schedule:list` menampilkan cleanup pukul 02:30, queue/failed job kosong,
  worker dapat start/stop bersih, dan UAT Brave tujuh role lulus.
- Runner UAT sekarang membagi login ke batch maksimum lima per menit agar tidak
  bertabrakan dengan rate limit route yang memang harus tetap aktif.

### Pembaruan verifikasi — 22 September 2026

- Konfigurasi reverse proxy sekarang menggunakan daftar IP/CIDR eksplisit dari
  `TRUSTED_PROXIES`; spoofing `X-Forwarded-*` dari klien langsung tetap ditolak.
- Regresi HTTPS memverifikasi host, skema, URL, dan HSTS di belakang proxy
  tepercaya.
- Production bundle dirender melalui Brave headless untuk tujuh role. Login,
  dashboard, matriks menu wajib/terlarang, dan console/page error lulus.
- Full suite terbaru lulus `78 test (458 assertion)`, Pint, ESLint, TypeScript,
  production build, Composer audit, dan npm audit.
- Phase 10 tetap **belum final** karena MySQL 8 staging, reverse proxy HTTPS
  aktual, queue/Supervisor, cron, backup-restore target, dan smoke test aaPanel
  belum dibuktikan pada environment deployment.
- Gap UX **Mulai Penilaian** belum diubah karena Figma MCP tidak tersedia pada
  sesi 22 September. Frame terkait wajib di-inspect sebelum perubahan frontend.

Pekerjaan dilanjutkan dari handoff 17 September. Gap aman yang dapat
diverifikasi lokal sudah ditutup pada backend dan frontend: workflow evaluasi,
lifecycle catatan masalah, aturan perhatian, pusat notifikasi, dashboard
scoped, laporan 21 kolom, ekspor queue, dokumen pribadi, rehire, filter
organisasi karyawan, timeline aktivitas, PWA, dan regresi keamanan.

| Phase | Status saat ini | Batas verifikasi |
| --- | --- | --- |
| 0 — Bootstrap | **Lulus lokal** | Stack frontend wajib sudah digunakan, Welcome lokal berbahasa Indonesia, dan quality gate lulus. |
| 1 — Identity & Authorization | **Lulus lokal** | Scope, policy, audit, provisioning, dan impersonasi memiliki regresi test; UAT browser belum dilakukan. |
| 2 — Organization | **Lulus lokal** | Hierarki dan pembatasan scope lulus feature test. |
| 3 — Employee | **Lulus lokal dengan batas UAT** | Filter organisasi/status, riwayat permanen, mutasi, status akhir, rehire, dokumen pribadi, dan timeline terpadu tersedia. |
| 4 — Evaluation Configuration | **Lulus lokal dengan batas UAT** | Tambah/ubah konfigurasi dan transisi periode tersedia serta tervalidasi. |
| 5 — Evaluation Flow | **Lulus lokal** | Alur `DRAFT → SUBMITTED → APPROVED → FINALIZED → CLOSED`, policy, timestamp, dan audit lulus test. |
| 6 — Incident & Attention | **Lulus lokal dengan batas UAT** | Kategori terkelola, update/transisi, rule engine, notifikasi, dan halaman Karyawan Bermasalah tersedia. |
| 7 — Reporting | **Lulus lokal** | Filter, scope snapshot, 21 kolom XLSX, format, queue, notifikasi, private download, expiry, audit, dan cleanup terjadwal lulus test. |
| 8 — PWA | **Lulus pemeriksaan aset/build** | Manifest, icon 64/192/512/maskable/apple, offline page, cache statis, indikator koneksi, install/update UX tersedia; install/offline/update pada browser/perangkat nyata belum diuji. |
| 9 — Security Hardening | **Lulus regresi lokal dengan pengecualian terdokumentasi** | Header, HSTS, rate limit, registrasi tertutup, MIME/ukuran upload, private storage, dan scope diuji. `style-src 'unsafe-inline'` masih diperlukan React/Recharts dan dicatat di `SECURITY.md`; `script-src` tetap ketat. |
| 10 — Production | **Belum diverifikasi** | Queue worker, cron, HTTPS aktual, backup/restore, permission filesystem, dan smoke test harus dibuktikan di environment target. |

### Bukti checkpoint lokal

- `php artisan test`: lulus — 78 test, 458 assertion.
- `vendor/bin/pint --test`, `npm run lint`, `npm run typecheck`: lulus.
- `npm run build`: lulus, 4.005 modul ditransformasi.
- `composer audit --locked --no-interaction`: tidak ada advisory.
- `npm audit --omit=dev`: 0 vulnerability.
- SQLite sementara: `migrate:fresh --seed`, seed kedua, rollback migration
  terakhir, dan migrate ulang semuanya lulus; file sementara dihapus.
- MySQL lokal 8.0.30 terdeteksi melalui `php artisan db:show`; migration aktif
  seluruhnya berstatus `Ran`.
- `php artisan route:list --except-vendor`: 69 route.
- `php artisan schedule:list`: cleanup laporan setiap hari pukul 02:30.

UAT browser role utama sudah lulus secara lokal, tetapi UAT seluruh form/mobile,
install/offline/update PWA pada perangkat nyata, dan verifikasi environment
produksi tetap menjadi exit gate; project belum boleh dinyatakan siap produksi
sebelum semuanya selesai.

Bagian audit 17 September di bawah dipertahankan sebagai baseline historis
sebelum pekerjaan lanjutan.

## Audit Kesiapan — 17 September 2026

### Keputusan

**Status project: BELUM SIAP dinyatakan selesai seluruh phase dan BELUM SIAP
produksi.**

Fondasi aplikasi berjalan dan quality gate lokal yang tersedia lulus, tetapi
implementasi saat ini belum memenuhi seluruh kontrak pada `PRD.md`, `MVP.md`,
`BUSINESS_RULES.md`, `REPORTING.md`, `PWA.md`, `SECURITY.md`, `DATABASE.md`,
dan `TESTING.md`. Label phase di bawah adalah hasil audit kode dan verifikasi
lokal, bukan hanya berdasarkan keberadaan route, migration, atau halaman.

### Hasil verifikasi lokal

| Pemeriksaan | Hasil |
| --- | --- |
| `php artisan test` | Lulus — 49 test, 174 assertion |
| `vendor/bin/pint --test` | Lulus |
| `npm run lint` | Lulus |
| `npm run typecheck` | Lulus |
| `npm run build` | Lulus — production bundle berhasil dibuat |
| `composer audit --locked --no-interaction` | Lulus — tidak ada advisory |
| `npm audit --omit=dev` | Lulus — 0 vulnerability |
| `php artisan migrate:status` | Seluruh migration terdaftar berstatus `Ran` |
| `php artisan schedule:list` | **Gagal memenuhi kebutuhan — belum ada scheduled task** |

Verifikasi browser end-to-end, pengujian perangkat PWA, dan verifikasi
infrastruktur produksi belum dilakukan pada audit ini.

### Status per phase

| Phase | Status audit | Temuan utama |
| --- | --- | --- |
| 0 — Bootstrap | **Sebagian; quality gate lulus** | Font lokal, lint, typecheck, test, dan production build tersedia dan lulus. Namun React Hook Form, Zod, TanStack Table, Recharts, dan date-fns belum dipakai di source. Halaman root masih berupa Welcome Laravel, memakai aset gambar runtime dari `laravel.com`, dan menampilkan judul Inggris. Ini belum sesuai stack, bahasa UI, dan larangan dependency/aset runtime eksternal. |
| 1 — Identity & Authorization | **Lulus lokal dengan batas verifikasi** | Authentication, permission, policy, organizational scope, audit, dan impersonasi tersedia serta memiliki test penting. Matriks regresi seluruh permission/role belum selengkap kontrak `TESTING.md`. |
| 2 — Organization | **Lulus lokal dengan batas verifikasi** | Cabang, divisi, sub divisi, dan jabatan memiliki alur serta test hierarchy/scope. Uji browser operator belum dilakukan. |
| 3 — Employee | **Sebagian** | Identitas, assignment, mutasi, status, dan histori tersedia. Filter organisasi/status lengkap, deteksi rehire/duplikasi lintas status, activity timeline terpadu, serta penguncian otomatis field scope tunggal belum terbukti lengkap. |
| 4 — Evaluation Configuration | **Sebagian** | Backend periode, komponen, bobot, kriteria, dan tenure rule tersedia. UI konfigurasi terutama menyediakan tambah; alur edit belum lengkap. Status periode juga belum sepenuhnya selaras dengan dokumen. |
| 5 — Evaluation Flow | **Belum final** | Sistem baru mendukung `DRAFT` dan `FINALIZED`. Workflow terdokumentasi `SUBMITTED`, `APPROVED`, `FINALIZED`, dan `CLOSED`, termasuk transisi serta otorisasinya, belum tersedia lengkap. |
| 6 — Incident & Attention | **Belum final** | Pencatatan dan penyelesaian insiden tersedia, tetapi lifecycle `UNDER_REVIEW`/`CLOSED`, update insiden, kategori terkelola, rule engine configurable, pusat notifikasi, dan dashboard peringatan lengkap belum tersedia. Tabel attention rule belum menjadi sumber keputusan dashboard; dashboard masih memakai severity yang di-hardcode. |
| 7 — Reporting | **Belum final / blocker** | Laporan hanya memfilter status. Filter periode dan organisasi, kolom evaluasi sesuai `REPORTING.md`, scope snapshot, row count, expiry, notifikasi selesai, cleanup terjadwal, formatting XLSX, dan test isi file belum tersedia. Ekspor saat ini hanya enam kolom identitas/organisasi. |
| 8 — PWA | **Sebagian** | Manifest, service worker, offline fallback, install prompt, dan update prompt tersedia. Set icon raster/maskable/apple-touch belum lengkap, belum ada indikator koneksi, dismissal instalasi tidak dipersistenkan, dan update belum melindungi form yang masih memiliki perubahan. Uji installability/offline/update pada browser nyata belum dilakukan. |
| 9 — Security Hardening | **Sebagian** | Security headers, HSTS saat HTTPS, login rate limit, private storage, policy/scope dasar, dan audit dependency tersedia. CSP masih mengizinkan inline style tanpa catatan pengecualian; coverage test header/rate-limit/regresi permission belum lengkap. Upload hardening belum dapat dinyatakan selesai karena alur dokumen private belum diimplementasikan. |
| 10 — Production | **Belum siap** | Dokumentasi deployment tersedia, tetapi Scheduler kosong. Supervisor queue, cron scheduler, HTTPS/header aktual, permission filesystem, backup/restore, queue worker, dan smoke test belum diverifikasi pada environment produksi. |

### Gap penghalang kesiapan

1. Tutup gap bootstrap: gunakan stack frontend wajib pada fitur yang relevan,
   ganti halaman Welcome bawaan dengan UI Indonesia, dan hapus seluruh aset
   runtime eksternal.
2. Lengkapi workflow penilaian beserta status, policy, audit, dan test transisi.
3. Lengkapi rule perhatian, lifecycle insiden, notifikasi in-app, dan seluruh
   metrik/filter dashboard sesuai MVP/PRD.
4. Bangun ulang laporan sesuai kontrak filter, kolom XLSX, scope snapshot,
   lifecycle file private, notifikasi, serta scheduled cleanup.
5. Lengkapi kebutuhan employee yang belum tertutup: filter organisasi/status,
   pencegahan duplikasi/rehire, activity timeline, dan UX scope tunggal.
6. Lengkapi PWA icon matrix, connectivity state, safe update untuk dirty form,
   persistensi dismissal, serta browser/device test.
7. Tambahkan menu operator untuk **Karyawan Bermasalah**, **Laporan**, dan
   **Notifikasi**; saat ini navigasi utama hanya memuat Beranda, Karyawan,
   Penilaian, dan Pengaturan.
8. Tambahkan test yang diwajibkan dokumen tetapi belum ada: report content dan
   filter/scope, notification, dashboard scope, security headers/rate limit,
   permission regression matrix, serta browser/PWA end-to-end.
9. Definisikan scheduled task yang diperlukan, lalu lakukan UAT produksi untuk
   queue, scheduler, backup-restore, HTTPS, dan smoke test.

### Syarat perubahan status menjadi siap

Project hanya boleh dinyatakan **siap/final** setelah seluruh gap penghalang
ditutup, dokumentasi disinkronkan, seluruh quality gate di atas tetap lulus,
test tambahan sesuai `TESTING.md` lulus, dan checklist produksi diverifikasi
pada environment target. Sampai saat itu, catatan implementasi di
`CHANGELOG.md` berarti fitur dasar telah ditambahkan, bukan bukti bahwa phase
sudah final.
