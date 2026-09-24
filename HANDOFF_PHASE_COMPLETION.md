# Handoff Lanjutan KPI Kepegawaian

Tanggal checkpoint: 24 September 2026
Status: **implementasi dan UAT browser role utama lulus lokal; belum siap deploy sebelum verifikasi MySQL, HTTPS/PWA perangkat nyata, dan environment production**

## 1. Batas desain yang sudah diverifikasi

- Figma file key: `EmIOtdlEaEnJR1f4PryW4m`
- File: **KPI Kepegawaian — Sistem Penilaian Karyawan**
- Page: `0:1` — Fondasi
- Frame: `1:37` — Dokumentasi Fondasi
- Design context dan screenshot sudah dibaca sebelum perubahan frontend.
- Variable collection pada frame tidak tersedia (`{}`), sehingga token visual
  mengikuti nilai frame: Inter lokal, latar `#F8FAFC`, hijau `#16A34A`, hijau
  lembut `#DCFCE7`, teks `#0F172A`, sekunder `#64748B`, border `#E2E8F0`, dan
  bahaya `#DC2626`.
- Figma tidak diubah.
- Pada 24 September 2026 file diperiksa ulang. Figma masih hanya menyediakan
  halaman Fondasi dan frame dokumentasi, tanpa frame khusus detail karyawan atau
  penilaian; CTA baru mengikuti hierarchy, warna, spacing, dan pola komponen
  aplikasi yang sudah ada.

## 2. Titik pekerjaan yang ditemukan

Handoff lama berhenti setelah migration/model/service/request/controller parsial.
Route, UI, reporting, PWA, dan test perubahan tersebut belum tersambung. Baseline
pertama pada kelanjutan ini menemukan satu kegagalan feature test insiden dan
tiga file tidak lolos Pint. Repository juga tidak memiliki direktori `.git`,
sehingga tidak tersedia diff atau commit historis untuk dijadikan patokan.

## 3. Implementasi yang sudah dilanjutkan

### Karyawan dan organisasi

- Filter pencarian, status, cabang, divisi, dan sub divisi pada daftar karyawan.
- Filter tetap memakai organizational scope dan penempatan terakhir untuk
  karyawan yang sudah resign/inaktif.
- Rehire membuat penempatan serta status baru tanpa menghapus histori.
- Dokumen pribadi menggunakan storage lokal, UUID, policy/scope, validasi
  MIME/ukuran, download terotorisasi, delete, dan audit.
- Detail karyawan memuat timeline penempatan, status, penilaian, dan catatan
  masalah sesuai permission pengguna.

### Penilaian, masalah, dan Beranda

- Workflow evaluasi lengkap: `DRAFT → SUBMITTED → APPROVED → FINALIZED → CLOSED`.
- Detail karyawan menyediakan **Mulai Penilaian** untuk periode aktif yang lolos
  policy/scope dan **Lanjutkan Penilaian** untuk draf milik penilai yang sama.
- Konfigurasi periode, komponen, tenure rule, kriteria, dan attention rule dapat
  ditambah/diubah melalui UI.
- Catatan masalah memakai kategori terkelola, update, status
  `OPEN → UNDER_REVIEW → RESOLVED → CLOSED`, penyelesaian, audit, dan notifikasi.
- Beranda memakai data scoped, filter organisasi/periode, Recharts, rule
  perhatian configurable, serta tidak membaca evaluasi/insiden tanpa permission.
- Notifikasi hanya dapat dibaca oleh pemiliknya.

### Laporan

- Filter tanggal/organisasi/status/kriteria/status masalah.
- Scope snapshot pada saat request ekspor.
- XLSX 21 kolom sesuai `REPORTING.md`, format tanggal/persen, header, freeze,
  auto-filter, urutan deterministik, dan hanya baris dalam scope.
- Queue retry/backoff, status proses, row count, notifikasi, audit, private
  download, expiry 24 jam, serta cleanup scheduler setiap hari pukul 02:30.

### Frontend dan PWA

- Welcome lokal berbahasa Indonesia tanpa aset runtime eksternal.
- Navigasi Karyawan Bermasalah, Laporan, dan Notifikasi berbasis abilities dari
  backend untuk UX; backend policy tetap menjadi batas keamanan.
- React Hook Form/Zod, TanStack Table, Recharts, dan date-fns dipakai pada fitur
  yang relevan.
- Manifest dan icon lokal 64/192/512/maskable/apple-touch, offline page, cache
  aset statis saja, indikator koneksi, install dismissal tujuh hari, dan update
  prompt yang memperingatkan perubahan formulir belum tersimpan.
- Teks auth/profile yang terlihat pengguna sudah diterjemahkan ke Bahasa
  Indonesia.

### Security dan data

- Test CSP/security headers, HSTS, rate limit login, registrasi publik tertutup,
  scope, notification ownership, serta upload MIME/ukuran.
- CSP script tetap hanya `'self'`. Pengecualian `style-src 'unsafe-inline'`
  untuk gaya runtime React/Recharts dicatat di `SECURITY.md`.
- Migration memeriksa `national_id` duplikat sebelum unique index dan
  menormalisasi string kosong.
- `AccessControlSeeder` dan `DomainDefaultsSeeder` terbukti idempotent.
- Reverse proxy hanya mempercayai daftar IP/CIDR eksplisit dari
  `TRUSTED_PROXIES`; regresi memastikan skema/host HTTPS dan HSTS dipertahankan
  oleh proxy tepercaya, sedangkan header palsu dari klien langsung diabaikan.
- Profil, pembaruan kata sandi, dan penghapusan akun menghasilkan audit log.
  Selama impersonasi, profil, kata sandi, logout, dan aksi sensitif tetap
  diblokir; validasi timeout dijalankan lebih dahulu agar akun asal dipulihkan.
- Editor pengguna mempertahankan banyak organizational scope dan memiliki
  regresi test agar scope kedua dan seterusnya tidak hilang saat pembaruan.
- Flash sukses/gagal dibagikan melalui Inertia dan dirender secara aksesibel pada
  layout utama.

### UAT browser role

- Brave headless menjalankan production bundle dengan database SQLite sementara
  tanpa mengubah `.env` atau database MySQL proyek.
- Login dan dashboard dirender untuk Super Admin, HR Admin, HR Manager, Branch
  Head, Division Head, Sub Division Head, dan Auditor.
- Menu wajib/terlarang setiap role lulus dan tidak ditemukan console/page error.
- Ringkasan mesin tersedia di `artifacts/local-uat/browser-role-results.json`;
  screenshot dashboard setiap role diperbarui pada 24 September 2026 melalui
  MySQL Laragon.

## 4. Bukti verifikasi lokal

- Full suite terbaru: `89 passed (519 assertions)`.
- Pint: lulus.
- ESLint: lulus.
- TypeScript: lulus.
- Production build: lulus; 4.005 modul ditransformasi.
- Composer audit: tidak ada advisory.
- npm audit production: 0 vulnerability.
- Route: 66 route terdaftar.
- Scheduler: `reports:cleanup-expired` setiap hari pukul 02:30.
- MySQL Laragon 8.0.30: seluruh 22 migration `Ran`; seeder domain/RBAC/UAT
  dijalankan dua kali dengan jumlah data tetap (idempotent).
- Queue database: `jobs = 0`, `failed_jobs = 0`; worker `--stop-when-empty`
  berhasil start dan berhenti bersih.
- Brave headless: login, dashboard, matriks menu, dan console/page error tujuh
  role lulus terhadap MySQL; artefak diperbarui pada 24 September 2026.
- SQLite sementara: migrate fresh + seed, seed kedua, rollback migration
  terakhir, dan migrate ulang lulus; database sementara sudah dihapus.

## 5. Batas yang belum diverifikasi

1. Rendered login/dashboard dan matriks menu tujuh role sudah lulus melalui
   Brave headless. UAT seluruh form, layout mobile, installability/update PWA,
   HTTPS reverse proxy aktual, dan perangkat nyata masih wajib.
2. Queue worker lokal dapat start/stop, tetapi worker persisten, cron production,
   Nginx/HTTPS, permission filesystem,
   backup/restore, dan smoke test aaPanel belum diverifikasi.
3. Repository tidak memiliki `.git`; perubahan belum dapat direview sebagai
   commit atau dipush dari workspace ini.

## 6. Urutan aman berikutnya

1. Pulihkan/konfirmasi repository Git bila version control memang diharapkan.
2. Jalankan queue worker persisten dan scheduler pada staging, lalu uji ekspor XLSX sampai
   notifikasi dan cleanup file.
3. Lakukan UAT lanjutan untuk direct URL 403, seluruh form, layout
   responsif, timeline, laporan, serta lifecycle evaluasi/insiden.
4. Lakukan PWA install/offline/update test pada Chrome/Edge desktop dan Android.
5. Verifikasi backup-restore MySQL, HTTPS/security headers aktual, filesystem private,
   dan smoke test sebelum menyatakan siap production.

## 7. Larangan operasional

- Jangan deploy hanya berdasarkan build lokal.
- Jangan menjalankan migration destruktif pada database berisi data.
- Jangan membuka `/register` tanpa keputusan eksplisit.
- Jangan menjalankan queue laporan tanpa worker dan monitoring.
- Jangan menghapus histori penempatan/status saat memperbaiki data karyawan.
