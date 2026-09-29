# BUSINESS RULES — KPI Kepegawaian

## 1. Employee Identity

BR-EMP-001:
Satu orang memiliki satu identity employee permanen.

BR-EMP-002:
Mutasi tidak membuat employee baru.

BR-EMP-003:
Resign tidak menghapus employee.

BR-EMP-004:
Rehire harus menghubungkan identity employee lama jika identifier cocok dan telah diverifikasi.

## 2. Assignment

BR-ASG-001:
Setiap penempatan disimpan sebagai assignment.

BR-ASG-002:
Assignment baru menutup assignment aktif sebelumnya jika terjadi mutasi.

BR-ASG-003:
Sistem tidak boleh kehilangan histori cabang/divisi/jabatan.

## 3. Employee Creation

BR-CRT-001:
Kepala Divisi hanya dapat membuat employee dalam organizational scope-nya.

BR-CRT-002:
Jika user hanya memiliki satu branch/division scope, field tersebut dikunci otomatis.

BR-CRT-003:
Jika user memiliki beberapa scope, pilihan hanya berasal dari allowed scope.

## 4. Employee Status

Status:
- ACTIVE
- PROBATION
- MUTATED
- RESIGNED
- TERMINATED
- INACTIVE

BR-STS-001:
Resigned/terminated tidak tampil sebagai employee aktif.

BR-STS-002:
History tetap accessible sesuai permission.

## 5. Evaluation Period

BR-PER-001:
Period mempunyai start_date dan end_date custom.

BR-PER-002:
Period Draft dapat diedit.

BR-PER-003:
Period Finalized/Closed tidak boleh berubah sembarangan.

## 6. Evaluation

BR-EVL-001:
Komponen penilaian berasal dari konfigurasi.

BR-EVL-002:
Bobot aktif wajib 100%.

BR-EVL-003:
Total dihitung backend.

BR-EVL-004:
Criteria dihitung backend.

BR-EVL-005:
Draft boleh diperbarui.

BR-EVL-006:
Finalized evaluation read-only kecuali correction flow authorized.

BR-EVL-007:
Perubahan score penting dicatat audit.

## 7. Masa Kerja

BR-TEN-001:
Masa kerja dihitung dari join date/assignment rule sesuai konfigurasi.

BR-TEN-002:
Mapping score configurable.

Contoh default:
- <3 bulan = 40
- 3–6 bulan = 60
- 6–12 bulan = 75
- 1–2 tahun = 85
- >2 tahun = 100

Contoh hanya default awal dan dapat diubah.

## 8. Attendance

MVP:
manual percentage atau import.

Jika import:
- identifier utama employee number/NIK
- duplicate dalam file → record pertama valid, berikutnya ditandai duplicate
- row invalid tidak menghentikan seluruh import bila configured partial processing
- hasil import memiliki summary

## 9. Incident

BR-INC-001:
Problem employee tidak direpresentasikan dengan boolean sederhana.

BR-INC-002:
Incident memiliki severity dan lifecycle.

BR-INC-003:
Resolved wajib memiliki resolution.

BR-INC-004:
High/Critical dapat memicu notifikasi.

## 10. Problem Detection

Rule configurable.

Contoh default:
- attendance < 70
- total evaluation < 65
- >=1 high incident
- >=3 medium incidents

Threshold tidak hard-coded di source.

## 11. Report

BR-REP-001:
Report mengikuti date range.

BR-REP-002:
Report mengikuti allowed organizational scope.

BR-REP-003:
Requested filter tidak boleh memperluas authorization.

BR-REP-004:
Export dicatat di audit/export log.

## 12. Audit

Wajib audit:
- create/update employee
- mutation
- resign
- terminate
- incident create/resolve
- evaluation submit/finalize
- role change
- permission change
- report export

## 13. Sensitive Actions

Harus confirmation:
- resign
- terminate
- transfer
- deactivate user
- finalize evaluation
- change role/permission

## 14. Riwayat Kerja

BR-RK-001:
Riwayat kerja (track record) hanya dapat diisi dan dikelola oleh pengguna
dengan role HR Admin atau HR Manager (SDM).

BR-RK-002:
Kepala Divisi, Kepala Sub Divisi, dan Kepala Cabang hanya dapat melihat
riwayat kerja karyawan dalam scope organisasi mereka, tidak dapat mengubah.

BR-RK-003:
Riwayat kerja mencakup timeline masuk, perubahan jabatan, mutasi, incident,
penilaian, resign, terminasi, dan rehire.

## 15. Impersonasi Super Admin

BR-IMP-001:
Impersonasi hanya tersedia untuk Super Admin dengan permission
`impersonation.start`.

BR-IMP-002:
Selama impersonasi, authorization sepenuhnya menggunakan role, permission,
dan scope pengguna target. Hak Super Admin asal tidak boleh terbawa.

BR-IMP-003:
Aksi terlarang selama impersonasi:
- ubah password/MFA
- ubah role/permission/scope
- pengaturan sistem dan branding
- buat pengguna
- impersonasi berantai

BR-IMP-004:
Sesi impersonasi otomatis berakhir bila:
- timeout tercapai (default 30 menit)
- akun asal atau target menjadi tidak aktif
- permission impersonation.start dicabut

BR-IMP-005:
Setiap event impersonasi (mulai, selesai, timeout, pencabutan) wajib
menghasilkan audit log berisi pelaku asal, target, alasan, waktu, IP,
dan user agent.

## 16. Branding dan Tampilan Sistem

BR-BRD-001:
Super Admin dapat mengelola branding aplikasi melalui halaman pengaturan:
- Logo (sidebar, login, PWA)
- Aksen warna utama (primary color)
- Teks footer
- Nama aplikasi

BR-BRD-002:
Branding disimpan di tabel `app_settings` dan berlaku global untuk semua
pengguna setelah halaman dimuat ulang.

BR-BRD-003:
Perubahan branding dicatat pada audit log.

BR-BRD-004:
Jika branding belum dikonfigurasi, aplikasi menggunakan default:
- Logo: logo bawaan aplikasi
- Warna: hijau (#16A34A)
- Footer: kosong
- Nama: KPI Kepegawaian

## 17. Copywriting UI

BR-CPW-001:
Seluruh teks yang dilihat pengguna wajib Bahasa Indonesia yang konsisten,
singkat, dan operasional.

BR-CPW-002:
Heading, label, tombol, placeholder, pesan error, pesan sukses, empty state,
dan tooltip harus diaudit untuk konsistensi bahasa dan istilah.

BR-CPW-003:
Istilah yang wajib digunakan (lihat AGENTS.md bagian 4. Bahasa UI):
- Beranda (bukan Dashboard)
- Karyawan (bukan Employee)
- Penilaian (bukan Performance/Review)
- Riwayat Kerja (bukan Track Record)
- Karyawan Bermasalah (bukan Problem Employee)
- Laporan (bukan Report)
- Pengaturan (bukan Settings)

## 18. Dashboard Pie Chart

BR-PIE-001:
Dashboard menampilkan pie chart untuk visualisasi distribusi karyawan:
- Distribusi per divisi
- Distribusi per cabang
- Distribusi per status karyawan (aktif, percobaan, resign, dll.)

BR-PIE-002:
Data pie chart mengikuti organizational scope dan filter aktif pengguna.

BR-PIE-003:
Pie chart menggunakan Recharts (sudah menjadi dependency project).
