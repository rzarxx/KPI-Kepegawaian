# AGENTS.md — KPI Kepegawaian

Dokumen ini adalah instruksi project untuk Codex atau coding agent lain.

## 1. Identitas Project

Nama project:

**KPI Kepegawaian — Sistem Penilaian Karyawan**

Tujuan:

Membangun aplikasi internal perusahaan untuk mengelola data karyawan, penempatan organisasi, penilaian kinerja, catatan masalah, riwayat kerja, notifikasi, dan laporan periodik.

## 2. Technology Stack

Wajib gunakan:

- Laravel 13
- PHP 8.3+
- React
- TypeScript
- Inertia.js
- Tailwind CSS
- shadcn/ui
- Lucide React
- TanStack Table
- React Hook Form
- Zod
- Recharts
- date-fns
- MySQL 8
- Laravel Queue
- Laravel Scheduler
- Spatie Laravel Permission
- Vite
- PWA service worker

Jangan mengganti stack inti tanpa persetujuan eksplisit.

## 3. Figma Source of Truth

Primary Figma file:

https://www.figma.com/design/EmIOtdlEaEnJR1f4PryW4m

Nama:

**KPI Kepegawaian — Sistem Penilaian Karyawan**

### Wajib sebelum mengubah UI

Sebelum membuat atau mengubah halaman frontend:

1. Gunakan Figma MCP.
2. Inspect file/node/frame terkait.
3. Baca design context.
4. Baca variable/style yang tersedia.
5. Jangan membuat layout baru berdasarkan asumsi jika desain sudah ada.
6. Jangan mengubah Figma kecuali prompt meminta perubahan desain.
7. Jika link node spesifik diberikan, node tersebut memiliki prioritas tertinggi.
8. Implementasi harus mempertahankan hierarchy, spacing, typography, status colors, responsive behavior, dan intent desain.

## 4. Bahasa UI

Semua teks yang dilihat pengguna menggunakan Bahasa Indonesia sederhana.

Gunakan:
- Beranda
- Karyawan
- Penilaian
- Riwayat Kerja
- Karyawan Bermasalah
- Laporan
- Pengaturan
- Masa Percobaan
- Ditinjau
- Perlu Perhatian

Hindari:
- Employee
- Performance
- Dashboard
- Report
- Track Record
- Review

Istilah teknis boleh digunakan di kode/dokumentasi internal.

## 5. Icon

- Emoji dilarang sebagai icon UI.
- Gunakan `lucide-react`.
- Icon harus di-import dari dependency lokal.
- Dilarang menggunakan CDN icon.
- Jangan campur banyak icon library.

## 6. Font

Gunakan Inter self-hosted/local WOFF2.

Dilarang:
- Google Fonts CDN
- jsDelivr font
- unpkg font
- remote font dependency

## 7. Security First

Frontend bukan security boundary.

Setiap request sensitif wajib melewati:

Authentication
→ Permission
→ Laravel Policy
→ Organizational Scope
→ Validation
→ Business Rule
→ Audit

Dilarang mengandalkan:
```ts
if (user.role === 'division_head') ...
```
sebagai authorization utama.

## 8. RBAC

Gunakan:

**Role + Permission + Organizational Scope**

Contoh:
Kepala Divisi TI Cirebon boleh mengakses karyawan dalam scope TI Cirebon saja.

Scope wajib diterapkan pada:
- list
- detail
- edit
- evaluation
- incident
- documents
- export
- dashboard
- report

Direct URL access ke data di luar scope harus menghasilkan 403.

### Impersonasi Pengguna

Fitur impersonasi hanya tersedia untuk pengguna dengan role **Super Admin** dan
permission `impersonation.start`. Permission, Policy, dan pemeriksaan status
akun tetap wajib dijalankan di backend; tampilan frontend tidak menjadi dasar
otorisasi.

- Super Admin dapat masuk sementara sebagai pengguna aktif yang bukan dirinya
  sendiri dan bukan Super Admin lain.
- Selama impersonasi, authorization dan organizational scope harus sepenuhnya
  menggunakan role, permission, dan scope pengguna target. Hak Super Admin
  asal tidak boleh terbawa.
- Simpan konteks akun asal di server-side session serta catat sesi pada
  `impersonation_sessions`. Jangan simpan identitas akun asal atau session ID
  mentah di local storage, cookie buatan sendiri, maupun query string.
- Setiap mulai, selesai, timeout, atau pencabutan sesi wajib menghasilkan audit
  log berisi pelaku asal, target, alasan, waktu, IP, dan user agent.
- Regenerasi session ID saat memulai dan saat kembali ke akun asal. Akhiri sesi
  bila Super Admin asal atau target menjadi tidak aktif, izin asal dicabut,
  atau batas waktu sesi tercapai.
- Saat impersonasi, blokir perubahan password/MFA, role, permission, scope,
  pengaturan sistem, pembuatan pengguna, dan impersonasi berantai.
- Sediakan endpoint khusus untuk mengakhiri sesi yang memvalidasi konteks
  server-side dan memulihkan akun Super Admin asal.

## 9. Data Karyawan

Satu karyawan memiliki satu identitas permanen.

Jangan menyimpan current branch/division/position sebagai satu-satunya histori.

Gunakan `employee_assignments`.

Resign, mutasi, terminasi tidak menghapus histori.

## 10. Penilaian

Komponen penilaian harus configurable.

Dilarang membuat database seperti:
- responsibility_score
- attendance_score
- attitude_score

Gunakan:
- evaluation_components
- employee_evaluations
- employee_evaluation_scores

## 11. PWA

PWA wajib.

Cache:
- JS
- CSS
- font
- icon
- logo
- static assets

Jangan cache secara agresif:
- data personal karyawan
- catatan masalah
- laporan
- token/session
- dokumen private

## 12. Asset

Tidak boleh menggunakan CDN runtime untuk:
- CSS
- JS
- font
- icon
- UI library

Semua dibundle atau dilayani dari domain aplikasi.

## 13. UX

Target pengguna: usia 22–35 tahun.

Karakter:
- simple
- modern
- profesional
- ringan
- responsive
- hierarchy jelas
- tidak ramai

Setiap state wajib memiliki:
- loading
- empty
- success
- error
- disabled
- validation

## 14. Password UX

Login:
- Eye/EyeOff wajib.
- Native password semantics tetap digunakan.
- `autocomplete="current-password"`.
- karakter terakhir yang diketik dapat terlihat ±500 ms sebelum dimasking.
- autofill/paste tidak menggunakan temporary reveal.
- blur/page hidden/submit → seluruh password masked.

## 15. Coding Rules

- Controller tipis.
- Gunakan FormRequest.
- Gunakan Action/Service untuk business use case.
- Gunakan Policy.
- Query harus scoped.
- Gunakan DTO/Resource untuk data keluar.
- Tidak menggunakan `$guarded = []` pada model sensitif.
- Raw SQL harus direview.
- Jangan pakai `dangerouslySetInnerHTML` tanpa alasan keamanan jelas.

## 16. Database

Migration harus:
- reversible jika memungkinkan
- memakai foreign key
- index untuk kolom filter/search penting
- unique constraint untuk identifier penting
- timestamps
- soft delete hanya bila memang dibutuhkan

## 17. Test Sebelum Selesai

Minimal jalankan:
- unit test
- feature test
- policy test
- scope test
- validation test
- export test
- PWA build
- TypeScript check
- formatter/linter
- production build

Jangan menyatakan pekerjaan selesai bila test terkait gagal.

## 18. Dokumentasi

Jika ada perubahan:
- update dokumen terkait
- update `CHANGELOG.md`
- jangan membiarkan kode dan dokumentasi berbeda

## 19. Larangan

Dilarang:
- emoji UI
- CDN frontend runtime
- authorization frontend-only
- data employee tanpa scope
- hardcode KPI
- hapus histori employee karena resign
- expose `.env`
- expose database port publik
- production `APP_DEBUG=true`
- private file di public storage tanpa authorization
