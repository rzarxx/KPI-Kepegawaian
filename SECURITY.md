# SECURITY — KPI Kepegawaian

## 1. Security Principle

Secure by default.
Deny by default.
Defense in depth.

## 2. Layers

```text
Cloudflare (optional)
↓
Firewall
↓
Nginx
↓
Laravel Middleware
↓
Authentication
↓
Rate Limit
↓
Permission
↓
Policy
↓
Organizational Scope
↓
Validation
↓
Business Rules
↓
Database
```

## 3. HTTPS

Production HTTPS wajib.

Jika TLS berakhir di reverse proxy, daftar IP/CIDR proxy harus dikonfigurasi
secara eksplisit melalui `TRUSTED_PROXIES`. Header `X-Forwarded-*` dari alamat
lain tidak dipercaya. Wildcard `*` dilarang agar klien langsung tidak dapat
memalsukan skema HTTPS, host, atau alamat asal.

Jika Cloudflare:
Full (Strict).

## 4. Nginx Root

Wajib:
```text
/project/public
```

Jangan:
```text
/project
```

## 5. Sensitive Files

Block:
- .env
- .git
- internal docs bila berada di web root
- composer config bila tidak perlu public
- package config

## 6. Laravel Production

```env
APP_ENV=production
APP_DEBUG=false
SESSION_SECURE_COOKIE=true
```

## 7. Session

- Secure
- HttpOnly
- SameSite sesuai flow
- regenerate after login
- invalidate on logout
- CSRF token regenerate logout

## 8. CSRF

Jangan disable pada route web mutations.

## 9. XSS

React escapes by default.

`dangerouslySetInnerHTML` dilarang kecuali reviewed + sanitized.

## 10. SQL Injection

Gunakan Eloquent/Query Builder/prepared statement.

Raw SQL harus parameterized dan direview.

## 11. Mass Assignment

Jangan gunakan:
```php
protected $guarded = [];
```
pada model sensitif.

Gunakan validated payload.

## 12. Validation

Frontend:
UX validation.

Backend:
security/business validation authority.

## 13. Login

- rate limit
- generic error
- failed attempt log
- optional lockout/backoff
- secure password hashing
- session regeneration

Error:
`Email atau kata sandi tidak sesuai.`

## 14. Password UX

- type=password semantics
- autocomplete current-password
- Eye/EyeOff
- last-char temporary reveal ±500ms
- paste/autofill masked
- blur/page hidden masked

## 15. Authorization

Semua:
- list
- detail
- mutation
- export
- file
- dashboard metric
harus scoped.

## 16. IDOR

Mengetahui ID resource tidak boleh berarti dapat mengaksesnya.

Policy wajib.

## 17. Private File

Employee private files:
`storage/app/private`

Download:
auth → policy → scope → stream.

## 18. Upload

Validate:
- size
- extension
- MIME
- uploader permission

Store random name/UUID.
Original name metadata only.

## 19. CSP

Target:
```text
default-src 'self'
script-src 'self'
style-src 'self'
font-src 'self'
img-src 'self' data: blob:
connect-src 'self'
frame-ancestors 'none'
base-uri 'self'
form-action 'self'
```

Adjust only when justified.

Implementasi saat ini menambahkan `style-src 'unsafe-inline'` khusus untuk
atribut gaya runtime yang dibuat React/Recharts. `script-src` tetap hanya
`'self'` tanpa `unsafe-inline` atau `unsafe-eval`. Pengecualian gaya ini harus
ditinjau kembali bila chart sudah dapat memakai nonce atau stylesheet statis.

## 20. Security Headers

- Content-Security-Policy
- Strict-Transport-Security
- X-Content-Type-Options: nosniff
- Referrer-Policy
- Permissions-Policy

## 21. CDN

Runtime frontend CDN dilarang.

Benefit:
CSP lebih ketat dan privacy lebih baik.

## 22. PWA Security

Jangan cache:
- session
- personal data response
- incident
- report
- private file
- credential

Cache static build asset saja secara default.

## 23. Logging

Jangan log:
- password
- session token
- auth token
- sensitive document body

## 24. Audit

Append-like audit untuk high-impact action.

## 25. Database

Jika satu VPS:
MySQL bind local/private.

Port 3306 jangan public.

## 26. Backup

- automatic DB backup
- encrypted/offsite recommended
- retention
- restore test

## 27. Dependency Security

Commit:
- composer.lock
- package-lock.json

Production:
- `composer install --no-dev --optimize-autoloader`
- `npm ci`
- `npm run build`

Review vulnerabilities before deploy.

## 28. Reauthentication

Optional/Recommended untuk:
- permission change
- role change
- critical user action
- sensitive system setting

## 29. Impersonasi dan Perubahan Akun

- Profil, kata sandi, logout, pengelolaan pengguna, role, permission, scope, dan
  konfigurasi sistem diblokir selama impersonasi.
- Middleware validasi impersonasi dijalankan sebelum middleware pemblokiran agar
  timeout atau pencabutan memulihkan akun Super Admin asal dengan aman.
- Perubahan profil, pembaruan kata sandi, dan penghapusan akun dicatat pada audit
  log; identitas akun asal tetap disimpan pada server-side session.
- Halaman konfigurasi penilaian boleh dibaca berdasarkan permission target,
  tetapi seluruh endpoint mutasinya tetap memakai `not-impersonating`.

## 30. Threat Checklist

Test:
- broken access control
- IDOR
- CSRF
- XSS
- SQL injection
- file upload bypass
- report scope bypass
- cache leakage
- authorization missing
- mass assignment
