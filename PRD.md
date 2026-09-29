# PRD — KPI Kepegawaian

## 1. Ringkasan

Sistem Penilaian Karyawan adalah aplikasi internal perusahaan untuk:

- mengelola data karyawan,
- mengelola struktur cabang/divisi/sub divisi,
- menyimpan riwayat penempatan,
- melakukan penilaian kinerja periodik,
- mencatat masalah/incident,
- mendeteksi karyawan yang memerlukan perhatian,
- menampilkan notifikasi,
- menyusun laporan periodik,
- mengekspor laporan XLSX,
- mempertahankan track record karyawan lintas cabang/divisi.

## 2. Target Pengguna

Rentang usia utama: **22–35 tahun**.

Persona utama:
- HR
- Kepala Cabang
- Kepala Divisi
- Kepala Sub Divisi
- Manajemen
- Auditor
- Administrator sistem

## 3. Sasaran Produk

1. Data karyawan terpusat.
2. Penilaian lebih konsisten dan dapat diaudit.
3. Riwayat masalah tidak hilang saat mutasi/resign.
4. Kepala divisi dapat mengelola karyawan dalam scope-nya.
5. Manajemen dapat melihat laporan berdasarkan periode.
6. Pengguna dapat memasang aplikasi sebagai PWA.
7. UX tetap sederhana meskipun data cukup kompleks.

## 4. Out of Scope MVP

Belum masuk MVP:
- payroll
- BPJS
- recruitment
- reimbursement
- training
- leave management
- asset management
- attendance machine integration penuh
- OKR
- 360 review

## 5. Organisasi

Struktur utama:

Company
→ Branch
→ Division
→ Sub Division
→ Position
→ Employee Assignment

## 6. Modul

### 6.1 Beranda

Menampilkan:
- jumlah karyawan
- karyawan aktif
- perlu perhatian
- rata-rata penilaian
- tren kinerja
- karyawan prioritas
- aktivitas terbaru

Filter sesuai permission:
- periode
- cabang
- divisi
- sub divisi

### 6.2 Karyawan

Fitur:
- daftar karyawan
- search nama/NIK
- filter organisasi/status
- tambah karyawan
- edit data
- detail
- riwayat penempatan
- status kerja
- penilaian
- incident
- aktivitas

### 6.3 Penempatan

Karyawan dapat:
- masuk
- pindah cabang
- pindah divisi
- pindah sub divisi
- pindah jabatan
- resign
- terminated
- inactive

Histori tidak boleh dihapus.

### 6.4 Penilaian

Komponen awal:
- Masa Kerja
- Tanggung Jawab
- Absensi
- Inisiatif
- Sikap
- Komunikasi
- Kerjasama Tim

Komponen dan bobot configurable.

### 6.5 Karyawan Bermasalah

Menampilkan:
- employee
- divisi
- alasan
- severity
- status tindak lanjut
- tanggal
- reporter
- resolution

### 6.6 Riwayat Kerja

Timeline:
- masuk
- perubahan jabatan
- mutasi
- incident
- penilaian
- resign
- terminasi
- rehire jika ada

### 6.7 Laporan

Filter:
- tanggal mulai
- tanggal akhir
- cabang
- divisi
- sub divisi
- status
- kriteria
- status masalah

Export XLSX wajib.

## 7. Functional Requirements

### Authentication

**FR-AUTH-001**
Sistem menyediakan login menggunakan email/username dan kata sandi.

**FR-AUTH-002**
Kolom kata sandi memiliki Eye/EyeOff.

**FR-AUTH-003**
Karakter terakhir yang diketik dapat tampil sekitar 500 ms.

**FR-AUTH-004**
Paste/autofill tidak menggunakan temporary character reveal.

**FR-AUTH-005**
Login error tidak mengungkap apakah akun ditemukan.

### RBAC

**FR-RBAC-001**
Semua resource dilindungi server-side.

**FR-RBAC-002**
Role tidak cukup; scope organisasi wajib.

**FR-RBAC-003**
Kepala Divisi hanya mengakses scope yang diberikan.

**FR-RBAC-004**
Export mengikuti scope yang sama.

**FR-RBAC-005**
Direct URL access di luar scope menghasilkan 403.

### Employee

**FR-EMP-001**
Setiap employee memiliki identifier unik.

**FR-EMP-002**
Penempatan disimpan sebagai histori assignment.

**FR-EMP-003**
Resign tidak menghapus employee.

**FR-EMP-004**
Saat employee ditambahkan oleh kepala divisi, cabang/divisi otomatis mengikuti scope.

**FR-EMP-005**
Jika identifier sudah ada, sistem mendeteksi kemungkinan employee lama/rehire.

### Evaluation

**FR-EVAL-001** *(diperbarui)*
Penilaian bersifat ad-hoc: evaluator dapat membuat penilaian kapan saja tanpa
wajib memilih periode. Periode tetap tersedia sebagai opsional untuk
pengelompokan dan pelaporan.

**FR-EVAL-002**
Komponen penilaian configurable.

**FR-EVAL-003**
Bobot wajib total 100% saat period/template diaktifkan.

**FR-EVAL-004**
Total dihitung server-side.

**FR-EVAL-005**
Criteria dihitung server-side.

**FR-EVAL-006**
Masa kerja dapat dihitung otomatis.

**FR-EVAL-007**
Evaluation memiliki Draft/Submitted/Approved/Finalized/Closed sesuai workflow.

### Riwayat Kerja

**FR-RK-001**
Hanya role HR Admin dan HR Manager yang dapat menambah atau mengubah riwayat
kerja karyawan.

**FR-RK-002**
Kepala Divisi, Kepala Sub Divisi, dan Kepala Cabang hanya dapat melihat
riwayat kerja karyawan dalam scope organisasi mereka.

### Impersonasi

**FR-IMP-001**
Impersonasi hanya tersedia untuk Super Admin dengan permission
`impersonation.start`.

**FR-IMP-002**
Selama impersonasi, seluruh authorization menggunakan role, permission, dan
scope pengguna target. Hak Super Admin asal tidak terbawa.

**FR-IMP-003**
Aksi terlarang selama impersonasi: ubah password/MFA, ubah role/permission/
scope, pengaturan sistem, buat pengguna, impersonasi berantai, dan kelola
branding/tampilan sistem.

**FR-IMP-004**
Timeout, pencabutan izin, dan nonaktifnya akun asal atau target harus
mengakhiri sesi impersonasi secara otomatis.

**FR-IMP-005**
Setiap mulai, selesai, timeout, dan pencabutan wajib menghasilkan audit log.

### Branding dan Tampilan Sistem

**FR-BRD-001**
Super Admin dapat mengelola logo aplikasi (sidebar, login, PWA).

**FR-BRD-002**
Super Admin dapat mengubah aksen warna utama (primary color) yang diterapkan
pada sidebar, tombol, badge, dan elemen aksen lainnya.

**FR-BRD-003**
Super Admin dapat mengelola teks footer aplikasi.

**FR-BRD-004**
Super Admin dapat mengelola nama aplikasi yang ditampilkan di sidebar dan
halaman login.

**FR-BRD-005**
Perubahan branding tersimpan di database dan berlaku secara global untuk semua
pengguna setelah halaman dimuat ulang.

**FR-BRD-006**
Perubahan branding dicatat pada audit log.
### Incident

**FR-INC-001**
Masalah disimpan sebagai record, bukan boolean.

**FR-INC-002**
Severity: Low/Medium/High/Critical.

**FR-INC-003**
Status: Open/Under Review/Resolved/Closed.

**FR-INC-004**
Setiap resolution menyimpan actor dan waktu.

### Notification

**FR-NOTIF-001**
Notifikasi in-app wajib.

**FR-NOTIF-002**
Rule configurable untuk warning.

**FR-NOTIF-003**
Isi push notification tidak boleh mengekspos data sensitif berlebihan.

### Report

**FR-REP-001**
Filter periode custom.

**FR-REP-002**
Export XLSX.

**FR-REP-003**
Export mengikuti organizational scope.

**FR-REP-004**
Export dicatat pada audit log.

### PWA

**FR-PWA-001**
Aplikasi installable sebagai PWA.

**FR-PWA-002**
Standalone mode.

**FR-PWA-003**
Static asset dapat di-cache.

**FR-PWA-004**
Data employee sensitif tidak dicache offline secara agresif.

### UI

**FR-UI-001**
Bahasa Indonesia penuh. Seluruh copy/teks UI harus menggunakan Bahasa Indonesia
yang konsisten, singkat, dan operasional di semua halaman.

**FR-UI-002**
Emoji dilarang sebagai icon.

**FR-UI-003**
Icon menggunakan Lucide React lokal.

**FR-UI-004**
Font Inter self-hosted.

**FR-UI-005**
Tidak ada runtime frontend CDN.

## 8. Non Functional Requirements

### Security
- HTTPS wajib
- RBAC + scope
- CSRF
- rate limit
- CSP
- secure cookie
- private storage
- audit logs
- production debug off

### Performance
- server-side pagination
- server-side filtering
- query index
- lazy loading
- code splitting
- queue untuk export/import besar

### Availability
- error page standar
- queue autorestart
- backup database
- restore procedure

### UX
- responsive
- keyboard accessible
- focus state
- loading skeleton
- empty state
- confirmation action
- prevention accidental data loss

## 9. Acceptance Criteria MVP

MVP dianggap layak apabila:
- user hanya melihat data sesuai scope,
- employee history bertahan saat mutasi/resign,
- penilaian menghasilkan total dan criteria benar,
- penilaian dapat dilakukan tanpa wajib memilih periode,
- dashboard menampilkan pie chart per divisi, per cabang, dan per status karyawan,
- riwayat kerja hanya dapat diisi oleh HRD/SDM,
- impersonasi Super Admin berfungsi dengan benar dan aman,
- copy/teks UI konsisten berbahasa Indonesia di semua halaman,
- branding (logo, warna aksen, footer, nama aplikasi) dapat dikelola Super Admin,
- laporan XLSX sesuai filter,
- PWA dapat di-install,
- tidak ada CDN runtime,
- production build sukses,
- audit log tercatat,
- critical authorization test lulus.
