# Laporan UAT Lokal dan Uji Operasional

Tanggal pengujian awal: 18 September 2026
Pembaruan terakhir: 24 September 2026
Lingkungan terbaru: Windows lokal, PHP 8.3.29, Laravel 13.32.0, MySQL 8.0.30
Laragon, dan Brave headless melalui CDP. Pengujian PWA/backup historis memakai
SQLite terisolasi dan reverse proxy HTTPS lokal dengan sertifikat self-signed.

## Kesimpulan

Status terkini: **rendered login dan dashboard tujuh role sudah lulus lokal;
integrasi HTTPS/PWA perangkat nyata dan environment production belum final**.

Queue, scheduler, backup-restore SQLite, endpoint smoke, lapisan PWA, full test
suite, TypeScript, production build, serta UAT menu tujuh role berhasil. Blank
page lokal akibat CSP telah diperbaiki. Konfigurasi `TRUSTED_PROXIES` dan regresi
spoofing/HTTPS sudah ditambahkan; integrasi reverse proxy aktual tetap harus
diulang pada topologi staging/production.

Kegagalan HTTPS 18 September di bawah adalah bukti historis sebelum
`TRUSTED_PROXIES` ditambahkan. Regresi 22 September memastikan proxy tepercaya
mempertahankan HTTPS/host/HSTS dan proxy tidak tepercaya diabaikan.

Verifikasi 24 September memakai database MySQL `kpi_kepegawaian` yang awalnya
kosong. Seluruh migration sudah `Ran`; seeder domain/RBAC dan akun UAT lokal
dijalankan dua kali tanpa menggandakan data. UAT login/dashboard tujuh role
lulus langsung terhadap MySQL tanpa console/page error. Worker database dapat
start dan berhenti bersih saat queue kosong; worker persisten production belum
termasuk cakupan verifikasi ini.

Pembaruan setelah UAT: CSP sekarang menggunakan nonce per request, Ziggy/Vite menerima nonce yang sama, dan origin Vite dibatasi ke loopback pada environment lokal. Brave headless memverifikasi `/login` berstatus 200, heading terlihat, dan tidak memiliki console error maupun page error.

## Matriks hasil

| Area | Status | Bukti utama |
|---|---|---|
| Rendered browser desktop | **LULUS untuk login/dashboard tujuh role** | Production bundle dirender terhadap MySQL Laragon untuk Super Admin, HR Admin, HR Manager, Branch Head, Division Head, Sub Division Head, dan Auditor; matriks menu serta console/page error lulus. Seluruh form dan viewport mobile masih perlu UAT lanjutan. |
| MySQL Laragon | **LULUS lokal** | MySQL 8.0.30 terhubung; 22 migration `Ran`; seeder idempotent menghasilkan 7 akun, 4 karyawan, 33 permission, dan konfigurasi domain tanpa duplikasi. |
| PWA pada loopback HTTP | **LULUS terbatas** | Manifest valid, service worker `activated`, cache `kpi-static-v4` tersedia, `/offline.html` tercache, endpoint personal/API tidak tercache, pemeriksaan manifest Chromium tanpa error, dan fallback offline benar-benar dirender. |
| HTTPS reverse proxy | **PERBAIKAN KODE LULUS REGRESI / INTEGRASI BELUM DIULANG** | Proxy tepercaya mempertahankan skema, host, URL, dan HSTS; spoofing dari alamat lain ditolak. Uji TLS melalui proxy staging masih wajib. |
| Queue database | **LULUS** | Job ekspor menghasilkan XLSX 1 baris, audit tercatat, queued notification diproses, queue habis, dan `failed_jobs = 0`. |
| Scheduler | **LULUS** | Event `reports:cleanup-expired` terdaftar pukul 02:30, `schedule:test` berhasil mengubah ekspor menjadi `EXPIRED` dan menghapus file, sedangkan `schedule:run` di luar jadwal melaporkan tidak ada task yang due. |
| Backup-restore | **LULUS untuk SQLite terisolasi** | Backup dan sumber awal memiliki SHA-256 yang sama; setelah data sengaja dimutasi, restore mengembalikan `Karyawan Uji Lokal`, 1 pengguna, 1 karyawan, 33 permission, dan 22 migration `Ran`. |
| Smoke endpoint | **LULUS sebagian** | `/up`, `/login`, manifest, service worker, dan ikon memberi 200; `/dashboard` memberi 302 seperti yang diharapkan, tetapi target redirect salah skema (`http`). |
| Regression backend | **LULUS** | 89 test, 519 assertion, tanpa kegagalan. |
| TypeScript dan production build | **LULUS** | `npm run build` menyelesaikan TypeScript dan Vite; 4.005 module ditransformasi. |

## Detail pengujian

### Rendered browser dan HTTPS

- Browser: Brave headless melalui Playwright 1.63.
- HTTPS: `https://localhost:8443` diteruskan ke Laravel lokal pada `http://127.0.0.1:8088` dengan header `X-Forwarded-*`.
- HTTPS `/login`:
  - status 200 dan secure context aktif;
  - body kosong;
  - aset dibentuk sebagai `http://localhost:8443/build/...`;
  - service worker tidak terdaftar;
  - console berisi pelanggaran CSP untuk CSS, JavaScript, dan script inline.
- HTTP langsung `/login`:
  - aset same-origin berhasil dimuat dan service worker terdaftar;
  - dua script inline tetap diblokir CSP;
  - aplikasi gagal dengan `ReferenceError: route is not defined`.
- Middleware HSTS hanya mengirim header ketika request dianggap secure, sementara `bootstrap/app.php` belum memiliki konfigurasi trusted proxy.
- Template memuat `@routes`, `@viteReactRefresh`, dan Inertia; kebijakan CSP saat ini tidak menyediakan nonce/hash bagi script inline tersebut.

Implikasi: respons HTTP-level dan feature test tidak cukup membuktikan halaman dapat digunakan oleh operator. Screenshot kegagalan tersedia di `artifacts/local-uat/https-login-blank.png` dan `artifacts/local-uat/http-login-blank.png`.

### PWA

Pengujian PWA independen dilakukan melalui origin loopback HTTP, yang diperlakukan sebagai secure context oleh Chromium:

- `manifest.webmanifest`, `sw.js`, dan `offline.html`: 200;
- nama aplikasi `Sistem Penilaian Karyawan`, mode `standalone`, dan ikon maskable ditemukan;
- service worker mencapai status `activated`;
- cache `kpi-static-v4` berisi fallback offline;
- cache tidak berisi path `/karyawan`, `/laporan`, atau `/api/`;
- manifest Chromium tidak memiliki error;
- simulasi offline menampilkan pesan `Koneksi tidak tersedia`.

Batas verifikasi: prompt instalasi OS/perangkat nyata dan lifecycle update PWA
terautentikasi belum diuji pada perangkat nyata.

### Queue dan scheduler

Queue database diuji dengan alur nyata, bukan fake queue:

1. Membuat `ReportExport` berstatus `QUEUED` dengan snapshot scope Super Admin uji.
2. Menjalankan worker pertama untuk `GenerateEmployeeReportExport`.
3. Memastikan ekspor selesai, berisi 1 baris, file XLSX ada, dan audit `report.export.complete` tercatat.
4. Menjalankan worker kedua untuk queued database notification.
5. Memastikan 1 notifikasi tersimpan, `jobs = 0`, dan `failed_jobs = 0`.
6. Mengubah expiry ke masa lalu dan menjalankan `schedule:test --name=reports:cleanup-expired`.
7. Memastikan status menjadi `EXPIRED`, `file_path` menjadi `null`, dan file XLSX telah dihapus.

### Backup-restore

Pengujian memakai database sementara `kpi-local-uat.sqlite`; `.env` dan database MySQL utama tidak diubah.

- SHA-256 sumber dan backup sebelum mutasi: `38B8863ABE880FC423C8F24E9C0058F381F3D88275D49E3885F84C1A8F4D8572`.
- Setelah nama karyawan dimutasi, checksum sumber berubah.
- Setelah restore, checksum sumber kembali sama dengan backup.
- Verifikasi pascarestore: data sampel kembali benar dan seluruh 22 migration berstatus `Ran`.

Batas verifikasi: prosedur backup-restore MySQL 8, privilege pengguna database, retensi backup, enkripsi, dan restore pada host terpisah belum diuji.

## Artefak

- `artifacts/local-uat/browser-role-results.json` — ringkasan lulus rendered UAT
  tujuh role pada MySQL Laragon, diperbarui 24 September 2026.
- `artifacts/local-uat/dashboard-*.png` — screenshot dashboard terbaru per role.
- `artifacts/local-uat/playwright-results.json` — kegagalan gate HTTPS/rendered UAT.
- `artifacts/local-uat/pwa-http-results.json` — hasil lulus pengujian PWA loopback.
- `artifacts/local-uat/pwa-offline.png` — fallback offline yang dirender.
- `artifacts/local-uat/https-login-blank.png` — bukti halaman HTTPS kosong.
- `artifacts/local-uat/http-login-blank.png` — bukti halaman HTTP kosong akibat CSP/bootstrap.

## Tindak lanjut yang diperlukan

1. Konfigurasikan `TRUSTED_PROXIES` dengan IP/CIDR aktual staging/production dan
   ulangi pengujian TLS/HSTS melalui reverse proxy nyata.
2. Lanjutkan UAT seluruh form pada desktop/mobile, direct URL 403, permintaan
   ekspor dari UI, serta install/update PWA melalui HTTPS.
3. Ulangi backup-restore menggunakan MySQL 8 dan alat backup yang benar-benar
   dipakai di deployment.
