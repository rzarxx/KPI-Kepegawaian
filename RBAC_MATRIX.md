# Matriks RBAC KPI Kepegawaian

Matriks ini menjadi acuan menu, tindakan, dan cakupan organisasi. Otorisasi tetap
dijalankan di backend melalui permission, Policy, scope organisasi, validasi,
aturan bisnis, dan audit; menu hanya mencerminkan akses yang sudah diberikan.

| Role | Menu utama | Tindakan khusus | Cakupan |
| --- | --- | --- | --- |
| Super Admin | Semua menu | Seluruh tindakan, role, pengaturan, audit, dan impersonasi | Seluruh organisasi |
| HR Admin | Beranda, Karyawan, Karyawan Bermasalah, Penilaian, Laporan, Notifikasi, Organisasi, Pengguna dan Akses | Kelola identitas/status/mutasi karyawan, catatan masalah, draf penilaian, dokumen, laporan, konfigurasi, organisasi, dan akun non-Super Admin | Seluruh organisasi atau scope yang ditetapkan |
| HR Manager | Beranda, Karyawan, Karyawan Bermasalah, Penilaian, Laporan, Audit Aktivitas, Notifikasi, Organisasi | Tinjau data, selesaikan masalah, setujui/finalisasi penilaian, ekspor laporan, audit hanya-baca | Seluruh organisasi; audit hanya tersedia untuk scope organisasi penuh |
| Branch Head | Beranda, Karyawan, Karyawan Bermasalah, Penilaian, Laporan, Notifikasi, Organisasi | Perbarui data terbatas, kelola/selesaikan masalah, setujui penilaian, ekspor laporan | Cabang yang ditetapkan |
| Division Head | Beranda, Karyawan, Karyawan Bermasalah, Penilaian, Laporan, Notifikasi, Organisasi | Catat masalah, buat/kirim/setujui penilaian, lihat laporan | Divisi yang ditetapkan |
| Sub Division Head | Beranda, Karyawan, Karyawan Bermasalah, Penilaian, Notifikasi, Organisasi | Catat masalah serta buat/kirim penilaian | Sub divisi yang ditetapkan |
| Auditor | Beranda, Karyawan, Karyawan Bermasalah, Penilaian, Laporan, Audit Aktivitas, Notifikasi, Organisasi | Seluruh data bersifat hanya-baca, kecuali pembuatan berkas ekspor laporan | Seluruh organisasi; audit hanya tersedia untuk scope organisasi penuh |
| Employee | Belum tersedia | Tidak dapat diprovision sebagai akun portal sampai relasi `users` ke `employees` dan Beranda self-service tersedia | Belum berlaku |

## Keputusan Role Employee

Role `Employee` tetap dipertahankan sebagai kontrak masa depan, tetapi tidak
dibuat sebagai akun login UAT dan tidak tersedia pada pilihan provisioning.
Database saat ini belum memiliki relasi eksplisit antara akun pengguna dan
identitas karyawan. Menghubungkan keduanya berdasarkan email berisiko membuka
data karyawan yang salah. Implementasi self-service berikutnya wajib menambah
relasi tersebut, Policy akses diri sendiri, serta halaman Beranda khusus.

## Data UAT Lokal

`LocalRoleAccountSeeder` membuat empat karyawan pada dua cabang:

- Cabang UAT Jakarta: tiga karyawan pada dua divisi.
- Divisi SDM Jakarta: dua karyawan pada dua sub divisi.
- Sub Divisi Administrasi SDM: satu karyawan.
- Cabang UAT Surabaya: satu karyawan.

Dengan demikian HR/Super Admin/Auditor melihat empat data, Branch Head melihat
tiga, Division Head melihat dua, dan Sub Division Head melihat satu.

Scope kepala organisasi divalidasi saat provisioning dan kembali disaring saat
otorisasi dibaca: Branch Head wajib memiliki tepat satu scope cabang, Division
Head tepat satu scope divisi, dan Sub Division Head tepat satu scope sub divisi.
Record scope global yang keliru tidak dapat memperluas akses ketiga role ini.
