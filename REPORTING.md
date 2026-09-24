# REPORTING — KPI Kepegawaian

## 1. Report Filters

- start_date
- end_date
- branch
- division
- sub division
- employee status
- criteria
- problem status

## 2. Required XLSX Columns

Minimal:
- No
- NIK / ID Karyawan
- Nama
- Cabang
- Divisi
- Sub Divisi
- Jabatan
- Tanggal Masuk
- Masa Kerja %
- Tanggung Jawab %
- Absensi %
- Inisiatif %
- Sikap %
- Komunikasi %
- Kerjasama Tim %
- Total %
- Kriteria Penilaian
- Keterangan
- Status
- Penilai
- Tanggal Penilaian

## 3. Custom Period

Report tidak dibatasi per bulan.

Support:
- 1–30 Sep
- 15 Sep–15 Okt
- range custom lain

## 4. Authorization

Query export:
requested filter ∩ authorized scope.

Tidak boleh hanya mengandalkan filter frontend.

## 5. Large Export

Gunakan queue jika row count besar.

Flow:
request
→ validate
→ authorize
→ create export job
→ generate
→ notify
→ temporary private download

## 6. Audit

Catat:
- user
- filters
- scope snapshot
- row count
- created time
- completed time

## 7. Temporary File

Jika export disimpan:
- private storage
- expiry
- authorized download
- scheduled cleanup

## 8. XLSX Format

- freeze header
- bold header
- percentage number format
- date number format
- auto filter
- sane column widths
- no hidden sensitive metadata
