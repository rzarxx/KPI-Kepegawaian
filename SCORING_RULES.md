# SCORING RULES — KPI Kepegawaian

## 1. Tujuan

Mesin scoring harus configurable dan tidak menempel ke source code.

## 2. Komponen Awal

- Masa Kerja
- Tanggung Jawab
- Absensi
- Inisiatif
- Sikap
- Komunikasi
- Kerjasama Tim

## 3. Bobot Default Awal

Contoh:
- Masa Kerja 10%
- Tanggung Jawab 20%
- Absensi 20%
- Inisiatif 15%
- Sikap 15%
- Komunikasi 10%
- Kerjasama Tim 10%

Total = 100%.

Nilai ini dapat diubah administrator berwenang.

## 4. Formula

Untuk percentage component:

```text
weighted_score = raw_score * (weight / 100)
```

Total:
```text
total_score = Σ weighted_score
```

Backend menjadi authority.

Frontend hanya preview.

## 5. Score Range

Default:
0–100.

Input diluar range ditolak kecuali component scoring method menentukan range berbeda.

## 6. Criteria Default

Contoh:
- 90–100 = Sangat Baik
- 80–89.99 = Baik
- 70–79.99 = Cukup
- 60–69.99 = Kurang
- <60 = Sangat Kurang

Boundary disimpan di database/config.

## 7. Scoring Methods

Support:
- higher_is_better
- lower_is_better
- exact_target
- binary
- manual_rating
- rule_based
- auto_tenure

## 8. Masa Kerja

Default mapping:
- <3 bulan → 40
- >=3 dan <6 → 60
- >=6 dan <12 → 75
- >=12 dan <24 → 85
- >=24 → 100

Perhitungan berdasarkan evaluation period end date agar historis konsisten.

## 9. Attendance

MVP manual:
raw score persentase.

Import:
data dapat berupa:
- hadir
- izin
- sakit
- alpha
- terlambat

Formula final harus configurable dan didokumentasikan.

## 10. Rounding

Gunakan precision decimal konsisten.

Recommendation:
- component weighted: 4 decimal internal
- total display: 2 decimal
- XLSX: 2 decimal

## 11. Recalculation

Draft:
boleh recalculation otomatis.

Finalized:
jangan recalc silently jika configuration berubah setelah finalisasi.

Simpan snapshot:
- weight
- raw score
- weighted score

## 12. Calibration Future

Jika calibration diaktifkan:
- original_score
- calibrated_score
- final_score
- reason
- actor
- timestamp

Original score tidak boleh ditimpa.
