# CHANGELOG — KPI Kepegawaian

## Unreleased

### Panduan Instalasi Production — 24 September 2026

- Mengganti README bootstrap dengan dokumentasi repository yang mencakup fitur,
  arsitektur, role, instalasi lokal, pengujian, keamanan, dan deployment.
- Menambahkan `PANDUAN_INSTALASI_PRODUCTION.md` untuk deployment aaPanel,
  Nginx, PHP 8.3, MySQL 8, build frontend, `.env`, migration aman, pembuatan
  Super Admin pertama, Supervisor queue, scheduler, HTTPS, backup, smoke test,
  prosedur update, dan checklist go-live.
- Menegaskan larangan `migrate:fresh`, perubahan `APP_KEY`, penggunaan
  `LocalRoleAccountSeeder`, wildcard trusted proxy, serta permission `777` pada
  production.
- Menyelaraskan timeout job export, worker, dan retry antrean database agar job
  panjang tidak diproses ganda; default `DB_QUEUE_RETRY_AFTER` menjadi 300 detik.

### Penutupan Gap Akses dan Penilaian — 24 September 2026

- Menambahkan akses **Mulai Penilaian** pada detail karyawan untuk periode aktif
  yang telah lolos policy dan organizational scope, termasuk akses melanjutkan
  penilaian milik penilai yang sudah tersimpan.
- Memperbaiki editor pengguna agar seluruh organizational scope dapat ditambah,
  diubah, dan dipertahankan tanpa hanya menyimpan scope pertama.
- Menampilkan flash sukses/gagal pada layout utama serta menambahkan audit untuk
  perubahan profil, kata sandi, dan penghapusan akun.
- Menguatkan impersonasi dengan memblokir profil, kata sandi, logout, serta aksi
  sensitif lain, sambil tetap menjalankan validasi timeout sebelum penolakan.
- Menghapus props root dan dialog khusus yang tidak digunakan, lalu memakai
  komponen modal aksesibel yang sudah menjadi pola aplikasi.
- Menambahkan unit test enum status dan regresi untuk CTA penilaian, multi-scope,
  flash, audit profil, serta pembatasan profil saat impersonasi.
- Memverifikasi MySQL 8.0.30 Laragon: seluruh 22 migration berstatus `Ran`,
  seeder domain/RBAC/UAT idempotent, antrean dan failed job kosong, worker dapat
  start/stop bersih, serta scheduler cleanup terdaftar pukul 02:30.
- Memperbaiki runner UAT agar tujuh login role dibagi sesuai rate limit lima
  request per menit; UAT Brave headless tujuh role kembali lulus pada MySQL.

### Reverse Proxy dan HTTPS — 22 September 2026

- Menambahkan konfigurasi `TRUSTED_PROXIES` berbasis daftar IP/CIDR eksplisit
  agar skema, host, redirect, URL aset, dan HSTS tetap benar di belakang reverse
  proxy tanpa mempercayai header spoofing dari klien langsung.
- Menambahkan regresi test untuk request HTTPS dari proxy tepercaya dan
  penolakan header `X-Forwarded-*` dari alamat yang tidak dipercaya.
- Menambahkan timeout dan validasi respons CDP pada runner UAT agar kegagalan
  koneksi browser berhenti dengan pesan yang jelas dan tidak menggantung.
- Memperjelas konfigurasi reverse proxy pada dokumentasi deployment dan
  keamanan; wildcard proxy tetap dilarang.

### Pembersihan Dead Code dan Artefak Pengembangan — 22 September 2026

- Menghapus controller dan halaman registrasi publik yang tidak memiliki route,
  sambil mempertahankan regresi test bahwa `/register` tetap tertutup.
- Menghapus komponen frontend tanpa import, policy scope yang tidak pernah
  dipanggil, dua test contoh bawaan Laravel, dan command `inspire` bawaan.
- Menghapus dependency frontend tanpa pemakaian (`@tailwindcss/vite`,
  `react-aria`, `tailwindcss-animate`, dan `vite-plugin-pwa`) serta Sanctum yang
  tidak digunakan oleh aplikasi web internal ini.
- Menghapus pointer Vite `public/hot` yang tertinggal agar runtime memakai
  production build ketika dev server tidak aktif.
- Menghapus profil/cache browser sementara berukuran besar tanpa menghapus
  screenshot, JSON, atau log yang menjadi bukti UAT, serta menambahkan aturan
  ignore agar artefak sementara tersebut tidak kembali masuk ke project.

### Manual Book Operasional — 18 September 2026

- Menambahkan manual book penggunaan KPI Kepegawaian dalam format HTML/PDF
  yang menjelaskan role dan scope, konfigurasi KPI, pengisian performa per
  divisi, formula, workflow persetujuan, laporan, UAT, dan pemecahan masalah.
- Mendokumentasikan batas implementasi saat ini secara eksplisit: belum ada
  tombol navigasi untuk memulai penilaian, hasil bersifat per penilai, belum
  ada alur kembali ke draf, dan laporan belum membatasi status final.

### Posisi Temporary Reveal Kata Sandi — 18 September 2026

- Memindahkan temporary reveal dari area ikon Eye ke indeks karakter yang
  benar di dalam input Login dan Reset Password, termasuk saat karakter
  disisipkan di tengah nilai.
- Mempertahankan native `type="password"`, masking setelah 500 ms, serta
  menonaktifkan temporary reveal untuk paste dan autofill.

### Matriks RBAC dan Pemulihan Kata Sandi — 18 September 2026

- Menetapkan ulang permission, menu, dan tindakan Super Admin, HR Admin, HR
  Manager, Branch Head, Division Head, Sub Division Head, Auditor, dan Employee.
- Menambahkan badge role/cakupan aktif, halaman audit organisasi hanya-baca,
  perlindungan direct URL, serta pemeriksaan permission catatan masalah.
- Mengunci kontrak scope Branch Head, Division Head, dan Sub Division Head pada
  level organisasinya serta mengabaikan record scope global yang tidak valid.
- Memperluas data UAT lokal menjadi dua cabang dan empat karyawan agar scope
  cabang, divisi, dan sub divisi menghasilkan isi yang berbeda.
- Menonaktifkan akun UAT Employee dan menutup provisioning role tersebut sampai
  relasi akun ke identitas karyawan serta Beranda self-service tersedia.
- Mendesain ulang halaman Lupa Kata Sandi dan Atur Ulang Kata Sandi mengikuti
  fondasi Figma `1:37`, termasuk Eye/EyeOff dan temporary character reveal.
- Menambahkan email reset kata sandi berbahasa Indonesia, respons anti-enumerasi,
  pembatasan akun aktif, dan rate limit permintaan reset.
- Menambahkan `RBAC_MATRIX.md` serta pengujian matriks, scope data UAT, badge,
  direct URL 403, dan isi email reset.

### Perbaikan Query Tren Beranda — 18 September 2026

- Mengkualifikasi kolom `employee_id`, `status`, dan `period_id` pada query
  evaluasi Beranda agar join dengan `performance_periods` tidak menghasilkan
  error ambiguous column pada MySQL.
- Menambahkan regresi test untuk pengguna berscope tanpa karyawan yang tetap
  memiliki izin melihat tren penilaian.

### Akun Pengujian Lokal per Role — 18 September 2026

- Menambahkan `LocalRoleAccountSeeder` yang idempotent untuk membuat satu akun
  aktif bagi setiap role dengan scope organisasi yang sesuai.
- Membatasi seeder akun hanya untuk environment `local/testing` dan menyediakan
  override password melalui `LOCAL_ROLE_SEED_PASSWORD`.
- Menambahkan test cakupan seluruh role dan idempotensi seeder serta petunjuk
  penggunaannya pada `panduan.md`.

### Perbaikan Render Browser Lokal — 18 September 2026

- Menambahkan nonce CSP per request pada tag Ziggy, React Refresh, dan Vite
  tanpa membuka `script-src 'unsafe-inline'`.
- Mengizinkan origin Vite hanya untuk loopback pada environment lokal dan
  menormalkan dev server ke `127.0.0.1:5173` agar HMR serta font lokal tidak
  diblokir CSP browser.
- Menambahkan regresi test untuk nonce dan origin Vite lokal; 6 security test
  dengan 39 assertion lulus.
- Memverifikasi halaman login melalui Brave headless: status 200, heading
  terlihat, serta tidak ada console error atau page error.
- Menambahkan Composer script `dev:windows` tanpa Laravel Pail karena ekstensi
  `pcntl` tidak tersedia pada PHP Windows.

### UAT Lokal dan Uji Operasional — 18 September 2026

- Memverifikasi queue database end-to-end untuk ekspor XLSX dan queued
  notification tanpa failed job, serta scheduled cleanup ekspor kedaluwarsa.
- Memverifikasi backup, mutasi, restore, checksum, dan migration status pada
  database SQLite terisolasi; database MySQL utama tidak disentuh.
- Memverifikasi manifest, service worker, cache statis aman, dan fallback PWA
  offline melalui browser headless pada origin loopback.
- Menjalankan smoke endpoint, 61 test/313 assertion, TypeScript, dan production
  build hingga berhasil.
- Menemukan gate browser/HTTPS belum lulus: CSP memblokir script inline Ziggy
  dan bootstrap, sedangkan reverse proxy belum dipercaya sehingga aset serta
  redirect HTTPS dibentuk sebagai HTTP dan HSTS tidak terkirim.
- Menambahkan `LOCAL_UAT_REPORT.md` dan artefak browser di
  `artifacts/local-uat`; tidak ada perbaikan kode produksi pada sesi uji ini.
- Menambahkan `panduan.md` berisi perintah menjalankan aplikasi, queue,
  scheduler, pemeriksaan browser/PWA, smoke test, dan quality gate lokal.

### Lanjutan Penyelesaian Phase — 18 September 2026

- Menyelesaikan workflow penilaian `DRAFT → SUBMITTED → APPROVED → FINALIZED
  → CLOSED`, transisi periode, policy, timestamp, audit, dan UI tindakan.
- Menyelesaikan lifecycle catatan masalah, kategori terkelola, rule perhatian
  configurable, notifikasi dalam aplikasi, Beranda scoped, serta halaman
  Karyawan Bermasalah dengan status dan penyelesaian.
- Menambahkan filter organisasi/status pada daftar karyawan, rehire yang tetap
  mempertahankan histori, dokumen pribadi berizin, dan timeline aktivitas
  gabungan untuk penempatan, status, penilaian, dan catatan masalah.
- Menyelesaikan Laporan dengan filter lengkap, scope snapshot, ekspor XLSX 21
  kolom, formatting, queue retry, notifikasi, private download, expiry 24 jam,
  audit, dan scheduled cleanup harian pukul 02:30.
- Mengganti Welcome bawaan dengan halaman Indonesia tanpa aset runtime
  eksternal dan menerapkan React Hook Form/Zod, TanStack Table, Recharts, serta
  date-fns pada fitur terkait.
- Melengkapi PWA dengan icon raster 64/192/512, maskable, apple-touch, cache
  statis aman, status koneksi, persistensi penolakan instalasi, dan update yang
  melindungi formulir belum tersimpan.
- Menambahkan regresi dashboard scope, dokumen pribadi, notification ownership,
  seeder idempotence, keamanan header/HSTS/rate limit, registrasi tertutup,
  validasi upload, isi XLSX, dan cache PWA.
- Memperbaiki urutan cache permission pada `AccessControlSeeder`, typo Carbon
  pada migration, serta filter metadata sub-divisi agar tidak keluar dari
  cakupan organisasi.
- Mencatat pengecualian CSP `style-src 'unsafe-inline'` untuk gaya runtime
  React/Recharts; kebijakan script tetap tanpa `unsafe-inline`/`unsafe-eval`.
- Memverifikasi 61 test/313 assertion pada full suite, Pint, ESLint,
  TypeScript, production build, Composer audit, npm audit, route, scheduler,
  serta migrate/seed/idempotensi/rollback pada SQLite sementara.
- Verifikasi MySQL lokal, browser/PWA perangkat nyata, dan infrastruktur
  production belum dilakukan dan tetap menjadi gate sebelum deploy.

### Audit Kesiapan — 17 September 2026

- Mengaudit implementasi terhadap PRD, MVP, business rules, database,
  reporting, PWA, security, testing, dan implementation plan.
- Menetapkan status project **belum siap produksi**: quality gate Phase 0 lulus
  tetapi implementasinya masih sebagian; Phase 1–2 lulus lokal dengan batas
  verifikasi; Phase 3–4, 6, 8, dan 9 masih sebagian; Phase 5 dan 7 belum final;
  Phase 10 belum siap.
- Mencatat gap penghalang di `IMPLEMENTATION_PLAN.md`, terutama workflow
  penilaian, rule perhatian/notifikasi/dashboard, laporan lengkap, PWA,
  coverage test, scheduler, dan pembuktian operasional produksi.
- Mencatat quality gate lokal yang lulus: 49 test/174 assertion, Pint, ESLint,
  TypeScript, production build, Composer audit, npm audit, dan seluruh
  migration berstatus `Ran`.
- Mencatat bahwa Laravel Scheduler belum memiliki scheduled task dan bahwa
  browser/PWA UAT serta verifikasi infrastruktur produksi belum dilakukan.
- Mencatat stack frontend wajib yang belum digunakan serta halaman Welcome
  bawaan yang masih memakai judul Inggris dan aset runtime dari `laravel.com`.

### Dokumentasi

- Menambahkan aturan implementasi impersonasi pengguna: hanya Super Admin,
  scope dan permission mengikuti akun target, audit sesi, pembatasan aksi
  sensitif, dan regenerasi sesi.
- Menetapkan banner header serta tombol **Kembali ke akun Super Admin** sebagai
  UI wajib selama impersonasi aktif.

### Changed

- Menutup regresi test terhadap pendaftaran publik: endpoint `/register` tetap
  tidak tersedia sesuai kebijakan aplikasi internal.
- Menetapkan pengguna hasil factory sebagai aktif agar fixture mencerminkan
  akses akun internal yang valid.
- Menambahkan pembaruan identitas karyawan dengan FormRequest dan audit log.
- Menambahkan policy evaluasi, validasi periode aktif, pemeriksaan komponen
  unik, serta kalkulasi komponen masa kerja dari aturan yang dikonfigurasi.
- Menambahkan dasar pengelolaan cabang, divisi, sub divisi, jabatan, periode,
  komponen, dan kriteria melalui route, policy, validasi, dan audit log.
- Menambahkan sidebar responsif, halaman Beranda berbahasa Indonesia, dan
  prompt pembaruan PWA yang hanya memuat ulang setelah tindakan pengguna.
- Menambahkan test regresi untuk IDOR/scope karyawan, kalkulasi evaluasi
  server-side, dan kebijakan cache aset PWA.
- Memulihkan trait otorisasi Laravel pada base controller agar pemeriksaan
  Policy menghasilkan respons 403 yang benar, bukan kesalahan server.
- Menambahkan konfigurasi ESLint lokal serta skrip `typecheck`, `lint`, dan
  pemeriksaan formatter untuk memenuhi baseline tooling Phase 0.
- Mendokumentasikan kontrak bootstrap dan urutan verifikasi lokal Phase 0 di
  README tanpa mengekspos konfigurasi rahasia.

### Phase 1 — Identity dan Authorization

- Menyatukan kontrak provisioning pengguna dan cakupan organisasi melalui
  FormRequest serta service transaksional yang memvalidasi hierarchy scope.
- Membatasi pengelolaan akun Super Admin hanya kepada Super Admin lain.
- Menambahkan permission dan lifecycle impersonasi server-side: session
  diregenerasi, audit start/end/timeout dicatat, aksi sensitif diblokir, dan
  timeout atau pencabutan akses memulihkan akun asal bila masih valid.
- Menambahkan banner dan dialog alasan impersonasi pada antarmuka pengguna.
- Menambahkan regresi test provisioning berscope, audit, perlindungan Super
  Admin, impersonasi, dan timeout impersonasi.

### Phase 2 — Struktur Organisasi

- Melengkapi pengelolaan Cabang, Divisi, Sub Divisi, dan Jabatan dengan alur
  tambah, ubah, dan penonaktifan tanpa penghapusan histori.
- Menegakkan validasi parent-child dan cakupan organisasi pada setiap mutasi.
- Menambahkan audit create/update serta regresi test hierarchy, scope, dan
  uniqueness kode organisasi.

### Phase 3 — Data Karyawan

- Menutup lifecycle karyawan: identitas, penempatan awal, mutasi, perubahan status
  akhir, serta riwayat penempatan dan status yang tidak dihapus.
- Membatasi pilihan penempatan dan daftar karyawan berdasarkan cakupan organisasi.
- Memindahkan validasi mutasi dan status ke FormRequest; perubahan status selalu
  menghasilkan histori dan audit log.

### Phase 4 — Konfigurasi Penilaian

- Menegakkan policy dan FormRequest pada periode, komponen, serta kriteria.
- Menambahkan validasi total bobot aktif dan rentang aturan masa kerja.
- Menyediakan input aturan masa kerja pada konfigurasi komponen serta audit perubahan.

### Phase 5 — Alur Penilaian

- Menambahkan FormRequest penilaian, pemisahan izin simpan draf dan finalisasi,
  pemulihan draf evaluator, serta regresi lifecycle penilaian.

### Phase 6 — Catatan Masalah dan Perhatian

- Menambahkan catatan masalah privat per karyawan, tingkat keparahan, penyelesaian,
  policy/scope, audit log, aturan perhatian, peringatan dashboard, dan notifikasi penyelesaian.

### Phase 7 — Laporan

- Menambahkan laporan karyawan scoped, ekspor XLSX berbasis queue, riwayat status ekspor,
  download privat milik pemohon, dan audit penyelesaian ekspor.

### Phase 8 — PWA

- Menambahkan UX instalasi dan pembaruan PWA, offline fallback, serta cache aset statis yang tidak menyimpan data privat.

### Phase 9 — Security Hardening

- Menambahkan CSP, security headers, HSTS saat HTTPS, dan rate limit login.

### Phase 10 — Kesiapan Produksi

- Menyelaraskan dokumentasi aaPanel/Nginx, Supervisor, scheduler, backup, dan smoke test operasional.

## 2026-09-16

### Added
- Project scope employee performance/KPI.
- Laravel 13 + React + TypeScript + Inertia architecture.
- MySQL database target.
- aaPanel + Nginx deployment target.
- Modular monolith.
- Figma source-of-truth workflow.
- Full Bahasa Indonesia UI requirement.
- Green corporate minimal visual direction.
- Inter self-hosted font.
- Lucide local icon policy.
- Emoji UI prohibition.
- PWA requirement.
- Strict RBAC + organizational scope.
- Permanent employee identity + assignment history.
- Configurable evaluation component design.
- Incident-based problematic employee model.
- Custom period reporting.
- XLSX export columns.
- Password Eye/EyeOff.
- Temporary last-character reveal password UX.
- Runtime CDN prohibition.
- Security-first and UX-first acceptance principles.
- Initial Figma screens and design polish.
- Phase 1 identity and authorization foundation: active user accounts, Spatie roles and permissions, organizational scope records, and append-only audit logs.
- Seeded role and permission matrix for Super Admin, HR, organizational leaders, Auditor, and Employee.
- Login activity audit baseline and last-login timestamp.
- Policy baseline untuk pengelolaan pengguna dan organizational scope.
- Phase 2 organization foundation: branch, division, sub division, position, relationship models, and scoped organization policies.
- Phase 3 employee foundation: permanent employee identity, assignment and status history, scoped employee query, policy, creation workflow, and Inertia list, create, and detail pages.
- Phase 5 evaluation flow: draft/finalized evaluation snapshots, server-side total and criteria calculation, audit logs, and Inertia score-entry form.
- Phase 3 completed with protected mutation and status transitions that retain employee assignment and status history.
- Phase 1 user-management flow: internal account provisioning, roles, organizational scopes, account activation, and audit entries.
- Bootstrap PWA dengan manifest, service worker lokal yang hanya menyimpan aset statis, ikon lokal, dan halaman offline aman.
- Inter self-hosted melalui aset WOFF2 lokal dan fondasi konfigurasi shadcn/ui.

### Changed
- Public self-registration is disabled; internal user provisioning will be introduced through the authorized user-management flow.
