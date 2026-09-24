# Panduan Menjalankan dan Menguji KPI Kepegawaian

Panduan ini digunakan untuk menjalankan aplikasi di komputer lokal, memeriksa
tampilan melalui browser, menguji PWA, serta menjalankan pemeriksaan otomatis.

## 1. Masuk ke Folder Project

Buka PowerShell, kemudian jalankan:

```powershell
cd C:\Users\rezzdev\Desktop\KPI
```

## 2. Persiapan Awal

Pastikan konfigurasi database pada file `.env` sudah benar. Jangan mengganti
`APP_KEY` pada instalasi yang sudah memiliki data terenkripsi.

Jalankan migration tanpa menghapus data:

```powershell
php artisan migrate
```

Isi role, permission, dan konfigurasi domain awal:

```powershell
php artisan db:seed
```

> Jangan menggunakan `php artisan migrate:fresh` atau
> `php artisan migrate:reset` pada database yang berisi data penting karena
> perintah tersebut dapat menghapus struktur atau data yang sudah ada.

## 3. Menjalankan Aplikasi untuk Pengembangan di Windows

Laravel Pail membutuhkan ekstensi `pcntl` yang tidak tersedia pada PHP Windows.
Gunakan script Windows berikut agar Laravel, queue listener, dan Vite berjalan
bersama tanpa Pail:

```powershell
composer run dev:windows
```

Biarkan terminal tetap terbuka selama aplikasi digunakan. Log Laravel tetap
tersedia di `storage/logs/laravel.log`.

Jika ingin menjalankan layanan pada terminal terpisah, gunakan:

```powershell
php artisan serve
```

```powershell
npm.cmd run dev
```

```powershell
php artisan queue:listen --tries=1 --timeout=0
```

Setelah server aktif, buka alamat berikut menggunakan Brave atau Chrome:

```text
http://127.0.0.1:8000/login
```

Registrasi publik dinonaktifkan. Gunakan akun yang sudah tersedia di database.
Seeder bawaan hanya menyiapkan role, permission, dan konfigurasi awal; seeder
tidak membuat akun pengguna.

Untuk membuat akun lokal bagi setiap role yang sudah memiliki portal, jalankan:

```powershell
php artisan db:seed --class=LocalRoleAccountSeeder
```

Password default akun lokal adalah `KpiLokal2026!`. Password dapat diganti
melalui `LOCAL_ROLE_SEED_PASSWORD` pada `.env` sebelum seeder dijalankan.
Seeder ini hanya dapat dijalankan pada environment `local` atau `testing`.

Email akun mengikuti nama role:

- `super.admin@kpi.local.test`
- `hr.admin@kpi.local.test`
- `hr.manager@kpi.local.test`
- `branch.head@kpi.local.test`
- `division.head@kpi.local.test`
- `sub.division.head@kpi.local.test`
- `auditor@kpi.local.test`

Role `Employee` tidak dibuat sebagai akun login UAT karena belum ada relasi
eksplisit dan aman antara tabel `users` dan `employees`. Matriks lengkap tersedia
di `RBAC_MATRIX.md`.

Seeder juga membuat empat data karyawan UAT pada Cabang UAT Jakarta dan Cabang
UAT Surabaya. Gunakan akun Branch Head, Division Head, dan Sub Division Head
untuk membuktikan bahwa isi daftar mengikuti scope dan menghasilkan jumlah data
yang berbeda.

## 3.1 Menguji Lupa Kata Sandi di Lokal

Dengan konfigurasi lokal bawaan `MAIL_MAILER=log`, email tidak dikirim ke inbox.
Setelah mengirim form `/forgot-password`, buka file berikut dan cari subjek
`Atur Ulang Kata Sandi - KPI Kepegawaian`:

```powershell
Get-Content storage\logs\laravel.log -Tail 200
```

Salin URL `/reset-password/...` dari log ke browser. Untuk pengiriman ke inbox,
isi konfigurasi SMTP yang sah pada `.env`, lalu jalankan:

```powershell
php artisan config:clear
```

## 4. Menjalankan Scheduler

Buka terminal PowerShell kedua:

```powershell
cd C:\Users\rezzdev\Desktop\KPI
```

Jalankan scheduler:

```powershell
php artisan schedule:work
```

Biarkan terminal scheduler tetap terbuka selama pengujian operasional.

Untuk melihat daftar task yang terjadwal:

```powershell
php artisan schedule:list
```

## 5. Memeriksa Tampilan Browser

Buka DevTools dengan menekan `F12`, kemudian periksa:

1. **Console**: tidak boleh ada error JavaScript atau pelanggaran CSP.
2. **Network**: file CSS, JavaScript, font, dan ikon harus berstatus 200.
3. **Application → Manifest**: manifest harus terbaca tanpa error.
4. **Application → Service Workers**: service worker harus berstatus
   `activated`.
5. **Application → Cache Storage**: cache `kpi-static-v4` harus tersedia.
6. Gunakan responsive device mode untuk memeriksa ukuran berikut:
   - Mobile: 390 × 844.
   - Tablet: 768 × 1024.
   - Desktop: 1440 × 900.

Periksa halaman berikut:

- `/login`
- `/dashboard`
- `/karyawan`
- `/karyawan-bermasalah`
- `/penilaian`
- `/laporan`
- `/notifikasi`
- `/pengaturan`

Pada setiap halaman, periksa kondisi loading, data kosong, sukses, error,
disabled, validasi form, navigasi, dan responsive layout.

## 6. Menguji PWA

PWA sebaiknya diuji menggunakan production build.

Hentikan `composer run dev:windows` jika masih berjalan, kemudian buat production
bundle:

```powershell
npm.cmd run build
```

Jalankan Laravel:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

Buka:

```text
http://127.0.0.1:8000
```

Di DevTools, periksa manifest, service worker, dan cache. Setelah halaman
pernah dimuat, aktifkan mode **Offline** pada tab Network lalu buka URL yang
belum pernah dikunjungi. Aplikasi harus menampilkan halaman
`Koneksi tidak tersedia`, bukan error browser mentah.

Pastikan data personal, laporan, dokumen private, token, dan session tidak
tersimpan dalam Cache Storage.

## 7. Menjalankan Queue Secara Terpisah

Jika tidak menggunakan `composer run dev:windows`, jalankan queue worker pada terminal
tersendiri:

```powershell
php artisan queue:work --tries=3 --timeout=180
```

Melihat failed job:

```powershell
php artisan queue:failed
```

## 8. Menjalankan Test Otomatis

Jalankan full backend test:

```powershell
php artisan test
```

Jalankan pemeriksaan TypeScript:

```powershell
npm.cmd run typecheck
```

Jalankan lint frontend:

```powershell
npm.cmd run lint
```

Jalankan pemeriksaan format PHP:

```powershell
npm.cmd run format:check
```

Jalankan production build:

```powershell
npm.cmd run build
```

## 9. Smoke Test Dasar

Periksa health endpoint:

```powershell
Invoke-WebRequest http://127.0.0.1:8000/up
```

Respons yang diharapkan adalah HTTP 200.

Periksa manifest PWA:

```powershell
Invoke-WebRequest http://127.0.0.1:8000/manifest.webmanifest
```

Periksa service worker:

```powershell
Invoke-WebRequest http://127.0.0.1:8000/sw.js
```

Periksa route aplikasi:

```powershell
php artisan route:list
```

## 10. Kondisi yang Saat Ini Diketahui

Berdasarkan UAT lokal tanggal 18 September 2026, backend test, queue,
scheduler, backup-restore SQLite, PWA offline, TypeScript, dan production build
berhasil.

Rendered halaman login lokal sudah diperbaiki dan diverifikasi melalui browser
headless: status 200, heading terlihat, serta tidak ada console error atau page
error. Perbaikannya menggunakan nonce CSP per request dan origin Vite lokal
`127.0.0.1:5173`.

HTTPS melalui reverse proxy masih memerlukan konfigurasi trusted proxy yang
disesuaikan dengan infrastruktur deployment agar URL aset, redirect, dan HSTS
menggunakan skema HTTPS secara konsisten.

Jika perubahan belum terlihat, hentikan lalu jalankan kembali Vite dengan
`npm.cmd run dev`, kemudian lakukan hard refresh browser menggunakan
`Ctrl+Shift+R`.

Laporan pengujian lengkap tersedia di `LOCAL_UAT_REPORT.md`.
