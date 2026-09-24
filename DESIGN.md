# DESIGN — KPI Kepegawaian

## 1. Design Direction

Gaya:

**Clean · Modern · Corporate · Minimal · Data Focused**

Target pengguna:
22–35 tahun.

Aplikasi tidak boleh terasa seperti template admin lama atau ERP penuh yang padat.

## 2. Bahasa

UI wajib Bahasa Indonesia.

Gunakan kalimat pendek, jelas, dan operasional.

Contoh:
- `Beranda`
- `Tambah Karyawan`
- `Simpan Draf`
- `Selesaikan Penilaian`
- `Karyawan Perlu Perhatian`
- `Unduh XLSX`

## 3. Figma

Source of truth:

https://www.figma.com/design/EmIOtdlEaEnJR1f4PryW4m

Sebelum implementasi halaman:
- inspect frame terkait via MCP
- gunakan spacing/hierarchy dari desain
- gunakan design token dari Figma/dokumen ini

## 4. Warna

### Primary
- Green: `#16A34A`
- Green Dark: `#15803D`
- Green Soft: `#DCFCE7`
- Green Very Soft: `#F0FDF4`

### Neutral
- App Background: `#F8FAFC`
- Surface: `#FFFFFF`
- Secondary Surface: `#F1F5F9`
- Heading: `#0F172A`
- Primary Text: `#1E293B`
- Secondary Text: `#64748B`
- Muted: `#94A3B8`
- Border: `#E2E8F0`
- Border Strong: `#CBD5E1`

### Status
- Success: `#16A34A`
- Warning: `#D97706`
- Danger: `#DC2626`
- Info: `#2563EB`
- Neutral: `#64748B`

## 5. Typography

Font:
**Inter**, self-hosted WOFF2.

Weights:
- 400 Regular
- 500 Medium
- 600 SemiBold
- 700 Bold

Scale:
- Page title: 26px / 700
- Section title: 16–18px / 600
- Card title: 14–16px / 600
- Body: 14px / 400
- Table: 13–14px
- Label: 12–13px / 600
- Caption: 11–12px

## 6. Font Hosting

Dilarang:
- Google Fonts CDN
- fonts.gstatic.com
- third-party font runtime

Contoh struktur:
```text
resources/fonts/inter/
  Inter-Regular.woff2
  Inter-Medium.woff2
  Inter-SemiBold.woff2
  Inter-Bold.woff2
```

## 7. Icon System

Gunakan:
`lucide-react`

Dilarang:
- emoji
- FontAwesome + Lucide campur
- CDN icon
- remote SVG runtime

Default sizes:
- Sidebar: 18px
- Button: 16px
- Input: 16px
- Empty state: 32–40px

## 8. Layout Desktop

Sidebar:
- width 240–244px
- white
- border kanan tipis
- sticky/full height

Header:
- 60–64px
- white
- bottom border
- notification + profile

Content:
- padding 24–32px
- max width sekitar 1600px

## 9. Sidebar

Grouping:
- UTAMA
- PEGAWAI
- PENILAIAN
- LAPORAN
- SISTEM

Active:
- green soft background
- green dark text/icon

## 10. Mobile

Mobile tidak boleh hanya mengecilkan desktop.

Gunakan:
- drawer/sidebar mobile
- bottom navigation untuk menu utama
- card list untuk tabel kompleks
- bottom sheet untuk filter

Recommended bottom nav:
- Beranda
- Karyawan
- Penilaian
- Laporan

## 11. Breakpoints

- Mobile: `<640px`
- Tablet: `640–1023px`
- Desktop: `>=1024px`
- Large Desktop: `>=1440px`

## 12. Spacing

Scale:
- 4
- 8
- 12
- 16
- 24
- 32

Page:
- Desktop 28–32
- Tablet 20–24
- Mobile 16

## 13. Radius

- Input: 8–9px
- Button: 8–9px
- Card: 12px
- Large panel: 12–16px
- Badge: pill

## 14. Shadows

Gunakan sangat ringan.

Contoh:
```css
box-shadow:
  0 1px 2px rgba(15,23,42,.04),
  0 8px 28px rgba(15,23,42,.04);
```

## 15. Buttons

Variants:
- Primary
- Secondary
- Ghost
- Danger

Default height:
40px.

Destructive action tidak menggunakan primary green.

## 16. Inputs

Default:
- 40px height
- border neutral
- green focus ring
- visible label
- validation message

Jangan mengandalkan placeholder sebagai label.

## 17. Table

Desktop:
- header soft gray
- horizontal border
- row 52–56px
- minimal vertical border
- server-side pagination

Mobile:
- card/list

## 18. Status

Satu status harus memiliki warna yang sama di seluruh aplikasi.

Contoh:
- Aktif → hijau
- Masa Percobaan → neutral/info
- Ditinjau → warning
- Bermasalah Tinggi → red
- Selesai → green

## 19. Login

Desktop:
dua kolom.

Kanan:
- title
- email
- password
- Eye/EyeOff
- masuk

Password:
- last typed char reveal ±500ms
- native password semantics
- autofill compatible
- paste/autofill langsung masked

## 20. Dashboard

Urutan:
1. heading + filter
2. summary
3. trend
4. perlu perhatian
5. list/table karyawan relevan
6. recent activity

Jangan menampilkan terlalu banyak chart.

## 21. Detail Karyawan

Header:
- nama
- ID
- divisi
- jabatan
- status

Tabs:
- Ringkasan
- Penilaian
- Riwayat Kerja
- Catatan Masalah
- Dokumen
- Aktivitas

## 22. Penilaian

Input numerik lebih disukai daripada slider.

Masa Kerja:
read-only/auto.

Panel kanan:
- Total
- Kriteria
- Status

Autosave draft direkomendasikan.

## 23. Dialog

Untuk:
- resign
- transfer
- finalize
- deactivate
- role change

Dialog harus menjelaskan dampak.

## 23.1 Impersonasi Pengguna

Saat Super Admin sedang masuk sebagai pengguna lain, tampilkan banner status
yang selalu terlihat di header. Banner menyebutkan nama pengguna target dan
menyediakan satu tombol sekunder dengan icon Lucide: **Kembali ke akun Super
Admin**.

- Banner hanya tampil bila konteks impersonasi tervalidasi dari backend.
- Tombol mengirim permintaan aman untuk mengakhiri sesi; tidak mengandalkan
  perpindahan route atau data yang tersimpan di browser.
- Gunakan warna info yang kontras, teks singkat, dan target tombol minimal
  40px. Jangan gunakan emoji.
- Ketika sesi berakhir, banner menghilang setelah halaman telah dipulihkan ke
  akun asal.

## 24. Empty State

Hindari hanya:
`No data`.

Gunakan:
- judul
- penjelasan
- action bila relevan

## 25. Accessibility

- minimum target 40px
- visible focus
- label input
- aria-label icon buttons
- contrast AA
- keyboard navigation

## 26. UX Safety

Jika form dirty:
- warning sebelum leave

Submit:
- button loading
- prevent double submit

Update PWA:
- jangan force reload saat form aktif.
