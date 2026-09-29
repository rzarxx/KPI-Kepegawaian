# GOALS — KPI Kepegawaian

## Product Goal

Membuat sistem penilaian karyawan yang aman, mudah digunakan, memiliki histori lengkap, dan mampu mendukung keputusan manajemen berbasis data.

## Goal 1 — Secure Access
Tidak ada data employee keluar dari allowed organizational scope.

## Goal 2 — Reliable History
Mutasi/resign tidak menghilangkan riwayat.

## Goal 3 — Configurable Evaluation
KPI/component/weight dapat dikembangkan tanpa redesign database.

## Goal 4 — Useful Reporting
Laporan custom period dan XLSX usable.

## Goal 5 — Simple UX
Pengguna non-teknis memahami UI tanpa training panjang.

## Goal 6 — PWA
User dapat membuka aplikasi seperti aplikasi native tanpa bolak-balik browser.

## Goal 7 — Maintainability
Modular monolith, testable, documented, deployable di aaPanel.

## Goal 8 — Ad-Hoc Evaluation
Penilaian dapat dilakukan kapan saja tanpa wajib memilih periode.

## Goal 9 — Dashboard Pie Chart
Dashboard menampilkan visualisasi pie chart distribusi karyawan per divisi,
per cabang, dan per status.

## Goal 10 — Track Record HRD Only
Riwayat kerja hanya dapat diisi oleh HRD/SDM, bukan kepala divisi/sub divisi.

## Goal 11 — Impersonation Fix
Sistem impersonasi Super Admin berfungsi aman dan lengkap sesuai AGENTS.md.

## Goal 12 — Copywriting Consistency
Seluruh teks UI konsisten berbahasa Indonesia di semua halaman.

## Goal 13 — System Branding
Super Admin dapat mengelola logo, aksen warna, footer, dan nama aplikasi.

## Success Indicators

- critical permission tests pass 100%
- zero known high-severity access-control issue before release
- main pages responsive
- report export reliable
- PWA install works
- all production UI assets self-hosted
- penilaian ad-hoc berhasil tanpa periode
- dashboard pie chart menampilkan data benar
- riwayat kerja hanya dapat diisi HRD/SDM
- impersonasi aman dan audit tercatat
- copywriting konsisten Bahasa Indonesia
- branding dapat dikelola Super Admin
