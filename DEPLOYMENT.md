# DEPLOYMENT — aaPanel + Nginx

Panduan instalasi production yang lengkap dan langkah per langkah tersedia di
[`PANDUAN_INSTALASI_PRODUCTION.md`](PANDUAN_INSTALASI_PRODUCTION.md).

## 1. Production Stack

- Ubuntu
- aaPanel
- Nginx
- PHP-FPM
- MySQL 8
- Supervisor
- Cron
- HTTPS
- Cloudflare optional

## 2. Directory

```text
/www/wwwroot/kpi-kepegawaian/
├── app
├── bootstrap
├── config
├── database
├── public
├── resources
├── routes
├── storage
├── vendor
└── .env
```

Nginx root:
```text
/www/wwwroot/kpi-kepegawaian/public
```

## 3. Build

Backend:
```bash
composer install --no-dev --optimize-autoloader
php artisan optimize
```

Frontend:
```bash
npm ci
npm run build
```

## 4. Environment

Production:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://...
SESSION_SECURE_COOKIE=true
TRUSTED_PROXIES=127.0.0.1,::1
```

`TRUSTED_PROXIES` wajib berisi IP atau CIDR proxy yang benar-benar meneruskan
request ke aplikasi. Nilai di atas hanya contoh untuk reverse proxy yang berada
di host yang sama. Jangan menggunakan wildcard `*`; jika Cloudflare digunakan,
batasi origin agar hanya menerima trafik dari proxy yang disetujui dan masukkan
alamat proxy aktual sesuai topologi server.

Secrets jangan commit.

## 5. Permissions

Writable:
- storage
- bootstrap/cache

Jangan `chmod -R 777`.

## 6. Scheduler

Cron:
```cron
* * * * * /usr/bin/php /www/wwwroot/kpi-kepegawaian/artisan schedule:run >> /dev/null 2>&1
```

Sesuaikan path PHP aaPanel jika menggunakan binary khusus.

## 7. Supervisor

Contoh:
```ini
[program:kpi-kepegawaian-queue]
process_name=%(program_name)s_%(process_num)02d
command=/www/server/php/83/bin/php /www/wwwroot/kpi-kepegawaian/artisan queue:work database --sleep=3 --tries=3 --timeout=240 --max-time=3600
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

Pastikan environment memuat `DB_QUEUE_RETRY_AFTER=300` agar nilai retry lebih
besar daripada timeout worker dan timeout job export.

## 8. Nginx Security

- block dotfiles
- HTTPS
- security headers
- correct index
- Laravel try_files
- no access to root project
- teruskan `X-Forwarded-For`, `X-Forwarded-Host`, `X-Forwarded-Port`, dan
  `X-Forwarded-Proto` hanya dari proxy yang tercantum pada `TRUSTED_PROXIES`

## 9. Cloudflare

Optional:
- Proxy
- WAF
- DDoS
- Full Strict
- Origin Certificate

## 10. Database

If local:
bind localhost.
Do not expose 3306 public.

## 11. Backup

Before deploy/migration:
- DB backup
- private storage backup if needed

Automate daily backup.

## 12. Deployment Flow

1. maintenance window if required
2. backup
3. pull artifact/code
4. composer install
5. npm ci/build if build on server
6. migrate `--force`
7. optimize
8. restart queue
9. verify scheduler
10. smoke test
11. monitor logs

## 13. Rollback

Have:
- previous release
- DB backup
- migration rollback strategy

## 14. Smoke Test Setelah Deploy

1. Buka `/up` dan pastikan respons sehat.
2. Login dengan akun operator, lalu cek scope karyawan.
3. Jalankan satu ekspor XLSX dan pastikan worker Supervisor menyelesaikannya.
4. Jalankan `php artisan schedule:list` dan pastikan cron aktif.
5. Periksa `storage/logs/laravel.log` serta `queue-worker.log` tanpa error baru.
