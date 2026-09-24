# Panduan Instalasi Production KPI Kepegawaian

Panduan ini ditujukan untuk deployment **KPI Kepegawaian** pada server Linux
dengan aaPanel, Nginx, PHP-FPM, MySQL 8, Supervisor, Cron, dan HTTPS.

Contoh dalam dokumen memakai:

- direktori aplikasi: `/www/wwwroot/kpi-kepegawaian`
- PHP aaPanel: `/www/server/php/83/bin/php`
- user service web: `www`
- domain: `kpi.example.com`

Ganti seluruh nilai contoh sesuai server sebenarnya.

> Jangan menjalankan `migrate:fresh`, `migrate:reset`, atau menghapus database
> production. Perintah tersebut dapat menghapus seluruh data.

## 1. Persyaratan Server

Pasang melalui aaPanel:

- Nginx
- PHP 8.3 atau lebih baru
- MySQL 8
- Supervisor Manager
- Cron
- SSL/HTTPS
- Composer 2
- Node.js 22 LTS dan npm

Aktifkan ekstensi PHP berikut:

- `bcmath`
- `ctype`
- `curl`
- `dom`
- `fileinfo`
- `gd`
- `intl`
- `mbstring`
- `openssl`
- `pdo_mysql`
- `tokenizer`
- `xml`
- `zip`

Periksa runtime:

```bash
/www/server/php/83/bin/php -v
```

```bash
/www/server/php/83/bin/php /usr/bin/composer --version
```

```bash
node --version
```

```bash
npm --version
```

## 2. Persiapan Database

Buat database dan pengguna khusus melalui aaPanel:

```text
Database : kpi_kepegawaian
User     : kpi_app
Password : gunakan password acak yang kuat
Host     : 127.0.0.1
Port     : 3306
```

Jangan membuka port MySQL `3306` ke internet. Berikan pengguna database hanya
hak yang diperlukan pada database KPI.

## 3. Mengunggah Kode

Masuk ke direktori web:

```bash
cd /www/wwwroot
```

Clone repository:

```bash
git clone <URL_REPOSITORY> kpi-kepegawaian
```

Masuk ke aplikasi:

```bash
cd /www/wwwroot/kpi-kepegawaian
```

Pastikan branch/revisi yang akan dirilis sudah benar:

```bash
git status
```

```bash
git log -1 --oneline
```

Jangan mengunggah `.env` lokal, `node_modules`, database lokal, atau akun UAT
ke server production.

## 4. Memasang Dependency

Pasang dependency PHP production:

```bash
/www/server/php/83/bin/php /usr/bin/composer install --no-dev --optimize-autoloader --no-interaction
```

Pasang dependency frontend sesuai lockfile:

```bash
npm ci
```

Buat production bundle:

```bash
npm run build
```

Pastikan file manifest terbentuk:

```bash
test -f public/build/manifest.json && echo "Build tersedia"
```

## 5. Membuat `.env` Production

Untuk instalasi baru:

```bash
cp .env.example .env
```

Edit `.env` melalui editor aaPanel. Contoh minimum:

```env
APP_NAME="KPI Kepegawaian"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://kpi.example.com
APP_LOCALE=id
APP_FALLBACK_LOCALE=id
APP_TIMEZONE=Asia/Jakarta

# Isi hanya IP/CIDR reverse proxy yang benar-benar dipercaya.
# Jangan menggunakan wildcard *.
TRUSTED_PROXIES=127.0.0.1,::1

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kpi_kepegawaian
DB_USERNAME=kpi_app
DB_PASSWORD="GANTI_DENGAN_PASSWORD_DATABASE"

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

CACHE_STORE=database
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=300
FILESYSTEM_DISK=local

MAIL_MAILER=smtp
MAIL_HOST=GANTI_HOST_SMTP
MAIL_PORT=587
MAIL_USERNAME=GANTI_USERNAME_SMTP
MAIL_PASSWORD="GANTI_PASSWORD_SMTP"
MAIL_SCHEME=smtp
MAIL_FROM_ADDRESS=no-reply@example.com
MAIL_FROM_NAME="${APP_NAME}"

VITE_APP_NAME="${APP_NAME}"
```

Untuk SMTP port `587`, gunakan `MAIL_SCHEME=smtp`; koneksi akan meningkatkan
ke TLS melalui STARTTLS ketika server mendukungnya. Gunakan `smtps` hanya bila
provider mensyaratkan implicit TLS, umumnya pada port `465`.

Catatan `TRUSTED_PROXIES`:

- Jika Nginx dan PHP-FPM berada pada server yang sama tanpa proxy tambahan,
  gunakan alamat loopback sesuai topologi server.
- Jika memakai Cloudflare/load balancer, masukkan IP/CIDR proxy aktual dan
  batasi origin agar tidak menerima bypass langsung.
- Jangan memakai `TRUSTED_PROXIES=*`.

Untuk instalasi baru dengan database kosong, buat `APP_KEY` satu kali:

```bash
/www/server/php/83/bin/php artisan key:generate --force
```

> Pada update berikutnya jangan menjalankan `key:generate`. Mengganti `APP_KEY`
> dapat memutus session dan membuat data terenkripsi lama tidak dapat dibaca.
> Jika deployment memindahkan data dari instalasi lama, gunakan `APP_KEY` lama.

## 6. Permission Direktori

Set pemilik direktori runtime:

```bash
chown -R www:www storage bootstrap/cache
```

Atur permission direktori:

```bash
find storage bootstrap/cache -type d -exec chmod 775 {} \;
```

Atur permission file:

```bash
find storage bootstrap/cache -type f -exec chmod 664 {} \;
```

Jangan memakai `chmod -R 777`. Dokumen karyawan disimpan di
`storage/app/private` dan dilayani melalui controller berotorisasi, bukan
melalui public storage.

## 7. Migration dan Seeder Instalasi Pertama

Periksa target database sebelum migration:

```bash
/www/server/php/83/bin/php artisan about
```

```bash
/www/server/php/83/bin/php artisan migrate:status
```

Jalankan migration non-destruktif:

```bash
/www/server/php/83/bin/php artisan migrate --force --no-interaction
```

Untuk **instalasi pertama dengan database kosong**, isi role, permission, dan
konfigurasi domain awal:

```bash
/www/server/php/83/bin/php artisan db:seed --force --no-interaction
```

Seeder domain dapat memperbarui konfigurasi KPI bawaan. Jangan menjalankannya
berulang pada production yang sudah dikustomisasi kecuali release note memang
memerintahkannya.

Jangan pernah menjalankan perintah berikut di production:

```text
php artisan migrate:fresh
php artisan migrate:reset
php artisan db:seed --class=LocalRoleAccountSeeder
```

## 8. Membuat Super Admin Pertama

Bagian ini hanya untuk instalasi baru yang belum mempunyai pengguna.

Pastikan role sudah tersedia:

```bash
/www/server/php/83/bin/php artisan db:seed --class=AccessControlSeeder --force --no-interaction
```

Masukkan password tanpa menampilkannya di terminal:

```bash
read -s -p "Password Super Admin: " KPI_ADMIN_PASSWORD && echo
```

Ekspor password hanya untuk proses berikutnya:

```bash
export KPI_ADMIN_PASSWORD
```

Ganti nama dan email pada perintah berikut sebelum dijalankan:

```bash
/www/server/php/83/bin/php artisan tinker --execute='$user = App\Models\User::create(["name" => "Administrator KPI", "email" => "admin@example.com", "password" => env("KPI_ADMIN_PASSWORD"), "is_active" => true]); $user->forceFill(["email_verified_at" => now()])->save(); $user->assignRole("Super Admin");'
```

Hapus password dari environment shell:

```bash
unset KPI_ADMIN_PASSWORD
```

Verifikasi akun tanpa menampilkan hash password:

```bash
/www/server/php/83/bin/php artisan tinker --execute='dump(App\Models\User::role("Super Admin")->get(["id", "name", "email", "is_active"])->toArray());'
```

Jika database berasal dari backup instalasi lama, jangan membuat akun baru;
verifikasi akun Super Admin yang sudah ada.

## 9. Cache Production

Bersihkan cache lama:

```bash
/www/server/php/83/bin/php artisan optimize:clear
```

Buat cache production:

```bash
/www/server/php/83/bin/php artisan optimize
```

Setelah setiap perubahan `.env`, jalankan kembali kedua perintah tersebut.

## 10. Konfigurasi Nginx aaPanel

Document root website wajib menunjuk ke:

```text
/www/wwwroot/kpi-kepegawaian/public
```

Contoh bagian utama konfigurasi Nginx:

```nginx
server {
    listen 80;
    server_name kpi.example.com;
    root /www/wwwroot/kpi-kepegawaian/public;
    index index.php;

    client_max_body_size 8m;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /sw.js {
        try_files $uri =404;
        add_header Cache-Control "no-cache, no-store, must-revalidate";
    }

    location = /manifest.webmanifest {
        try_files $uri =404;
        add_header Cache-Control "no-cache";
    }

    location /build/assets/ {
        try_files $uri =404;
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    location ~ \.php$ {
        try_files $uri =404;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param HTTPS $https if_not_empty;
        fastcgi_pass unix:/tmp/php-cgi-83.sock;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Sesuaikan socket PHP dengan konfigurasi aaPanel. Aktifkan SSL melalui aaPanel,
paksa redirect HTTP ke HTTPS, lalu gunakan mode **Full (strict)** jika memakai
Cloudflare.

Jangan menambahkan CSP berbeda di Nginx tanpa review karena aplikasi sudah
mengirim security headers sendiri.

## 11. Queue Worker Supervisor

Buat program Supervisor:

```ini
[program:kpi-kepegawaian-queue]
process_name=%(program_name)s_%(process_num)02d
command=/www/server/php/83/bin/php /www/wwwroot/kpi-kepegawaian/artisan queue:work database --queue=default --sleep=3 --tries=3 --timeout=240 --max-time=3600
directory=/www/wwwroot/kpi-kepegawaian
user=www
numprocs=1
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
redirect_stderr=true
stdout_logfile=/www/wwwroot/kpi-kepegawaian/storage/logs/queue-worker.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
stopwaitsecs=300
```

Nilai tersebut sengaja mengikuti urutan `DB_QUEUE_RETRY_AFTER=300` lebih besar
dari worker `--timeout=240`, dan worker lebih besar dari batas job export
(`180` detik). Urutan ini mencegah job diambil ulang saat proses lama masih
berjalan.

Muat ulang Supervisor:

```bash
supervisorctl reread
```

```bash
supervisorctl update
```

```bash
supervisorctl start kpi-kepegawaian-queue:*
```

Periksa status:

```bash
supervisorctl status kpi-kepegawaian-queue:*
```

## 12. Laravel Scheduler

Tambahkan cron aaPanel setiap satu menit:

```cron
* * * * * cd /www/wwwroot/kpi-kepegawaian && /www/server/php/83/bin/php artisan schedule:run >> /dev/null 2>&1
```

Verifikasi task Laravel:

```bash
/www/server/php/83/bin/php artisan schedule:list
```

Task `reports:cleanup-expired` harus terlihat pada pukul `02:30`.

Supervisor queue tidak menggantikan cron scheduler. Keduanya wajib aktif.

## 13. Backup Wajib

Buat direktori backup di luar document root:

```bash
mkdir -p /www/backup/kpi-kepegawaian
```

Backup database sebelum migration/update:

```bash
mysqldump --single-transaction --quick --routines --triggers -u kpi_app -p kpi_kepegawaian > /www/backup/kpi-kepegawaian/kpi-$(date +%F-%H%M%S).sql
```

Backup private storage:

```bash
tar -czf /www/backup/kpi-kepegawaian/private-$(date +%F-%H%M%S).tar.gz -C /www/wwwroot/kpi-kepegawaian/storage/app private
```

Simpan backup di lokasi terpisah dari server aplikasi dan lakukan uji restore
secara berkala. Keberadaan file backup saja belum membuktikan backup dapat
dipulihkan.

## 14. Smoke Test Sebelum Go-Live

Periksa status aplikasi:

```bash
curl -I https://kpi.example.com/up
```

Periksa migration:

```bash
/www/server/php/83/bin/php artisan migrate:status
```

Periksa queue gagal:

```bash
/www/server/php/83/bin/php artisan queue:failed
```

Periksa scheduler:

```bash
/www/server/php/83/bin/php artisan schedule:list
```

Periksa konfigurasi runtime:

```bash
/www/server/php/83/bin/php artisan about
```

Pastikan hasil menunjukkan:

- environment `production`
- debug mode `OFF`
- database `mysql`
- cache, queue, dan session menggunakan `database`
- config/routes/views sudah cached

Uji melalui browser:

1. Login sebagai Super Admin.
2. Pastikan scope dan menu sesuai role.
3. Buat satu data karyawan uji yang disetujui.
4. Uji penilaian dari draf sampai status yang diperlukan.
5. Uji catatan masalah dan notifikasi.
6. Jalankan satu ekspor XLSX dan pastikan worker menyelesaikannya.
7. Unduh dokumen private hanya dengan akun berizin.
8. Pastikan direct URL data di luar scope menghasilkan `403`.
9. Periksa Console dan Network browser tanpa error.

Periksa log:

```bash
tail -n 100 storage/logs/laravel.log
```

```bash
tail -n 100 storage/logs/queue-worker.log
```

## 15. Prosedur Update Berikutnya

Masuk ke aplikasi:

```bash
cd /www/wwwroot/kpi-kepegawaian
```

Aktifkan maintenance mode:

```bash
/www/server/php/83/bin/php artisan down --retry=60
```

Buat backup database dan private storage seperti bagian sebelumnya.

Ambil kode terbaru:

```bash
git pull --ff-only
```

Pasang dependency PHP:

```bash
/www/server/php/83/bin/php /usr/bin/composer install --no-dev --optimize-autoloader --no-interaction
```

Pasang dependency dan build frontend:

```bash
npm ci
```

```bash
npm run build
```

Jalankan migration non-destruktif:

```bash
/www/server/php/83/bin/php artisan migrate --force --no-interaction
```

Bangun ulang cache:

```bash
/www/server/php/83/bin/php artisan optimize:clear
```

```bash
/www/server/php/83/bin/php artisan optimize
```

Restart worker agar memakai kode baru:

```bash
/www/server/php/83/bin/php artisan queue:restart
```

Aktifkan kembali aplikasi:

```bash
/www/server/php/83/bin/php artisan up
```

Lakukan smoke test sebelum mengumumkan deployment selesai.

Jika salah satu langkah gagal, jangan jalankan `artisan up` sampai penyebabnya
dipahami atau release sebelumnya dipulihkan.

## 16. Checklist Go-Live

- [ ] Domain mengarah ke server yang benar.
- [ ] Document root mengarah ke folder `public`.
- [ ] HTTPS valid dan redirect HTTP aktif.
- [ ] `APP_ENV=production`.
- [ ] `APP_DEBUG=false`.
- [ ] `APP_KEY` tersimpan aman dan tidak berubah saat update.
- [ ] `TRUSTED_PROXIES` hanya berisi proxy tepercaya.
- [ ] Port MySQL tidak terbuka ke publik.
- [ ] Seluruh migration berstatus `Ran`.
- [ ] Seeder awal hanya dijalankan pada instalasi pertama.
- [ ] Tidak ada akun/data `LocalRoleAccountSeeder` di production.
- [ ] Super Admin production dapat login.
- [ ] Permission `storage` dan `bootstrap/cache` benar.
- [ ] Queue Supervisor berstatus `RUNNING`.
- [ ] Cron scheduler berjalan setiap menit.
- [ ] `failed_jobs` kosong.
- [ ] Ekspor XLSX selesai melalui queue.
- [ ] Dokumen private tidak dapat diakses tanpa izin.
- [ ] Backup database dan private storage tersedia.
- [ ] Restore backup pernah diuji.
- [ ] UAT scope, form utama, mobile, dan PWA selesai.
- [ ] Log aplikasi dan worker tidak memiliki error baru.

Production baru boleh dibuka setelah seluruh item kritis di atas lulus.
