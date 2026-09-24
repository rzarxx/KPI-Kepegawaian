# PWA — KPI Kepegawaian

## 1. Objective

Aplikasi dapat di-install di desktop/mobile dan berjalan seperti aplikasi standalone.

## 2. Manifest

Implementasi saat ini:
```json
{
  "name": "Sistem Penilaian Karyawan",
  "short_name": "KPI Karyawan",
  "display": "standalone",
  "start_url": "/",
  "theme_color": "#16A34A",
  "background_color": "#F8FAFC"
}
```

## 3. Icons

Local:
- 64
- 192
- 512
- maskable 512
- apple touch icon
- favicon svg/ico

Jangan emoji.

## 4. Offline Strategy

### Cache First
- versioned JS
- CSS
- font
- logo
- PWA icon
- static illustration

### Network Only / strict Network First
- employee data
- incidents
- evaluation
- reports
- documents
- auth/session endpoints

## 5. Offline Page

Bahasa:
`Koneksi tidak tersedia`

Pesan:
`Data karyawan memerlukan koneksi aktif untuk menjaga keamanan dan konsistensi data.`

Action:
`Coba Lagi`

## 6. Update Strategy

Jika service worker update tersedia:
- tampilkan prompt
- jangan paksa reload saat form dirty
- pengguna memilih `Muat ulang`; jika form berubah, browser meminta konfirmasi
  sebelum update diterapkan

## 7. Install Prompt

Jangan spam.

Setelah login dan user sudah memahami aplikasi:
- offer install
- jika ditolak, simpan preferensi selama tujuh hari
- menu Pengaturan dapat menyediakan Install Aplikasi

## 8. Connectivity

Tampilkan indikator kecil jika offline.

## 9. Push Notification

Phase berikutnya.

Jika diaktifkan:
- jangan bocorkan detail sensitif di lock screen
- generic title/message
- permission opt-in

## 10. PWA Security

Tidak boleh:
- localStorage password
- localStorage authorization authority
- cache API employee sensitif
- expose report offline

## 11. Asset Self Hosted

Semua PWA core asset harus lokal:
- font
- icon
- CSS
- JS
- manifest
- logo

## 12. Status Verifikasi

- Manifest, dimensi icon, maskable purpose, dan kebijakan cache statis memiliki
  feature test.
- Production build telah lulus.
- Installability, offline fallback, dan update lifecycle pada browser/perangkat
  nyata masih wajib diuji sebelum production.
