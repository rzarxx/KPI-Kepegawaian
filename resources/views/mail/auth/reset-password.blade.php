<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Atur Ulang Kata Sandi</title>
</head>
<body style="margin:0;background:#f8fafc;color:#0f172a;font-family:Inter,Arial,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;padding:32px 16px;">
    <tr><td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;overflow:hidden;border:1px solid #e2e8f0;border-radius:12px;background:#ffffff;">
            <tr><td style="background:#16a34a;padding:28px 32px;color:#ffffff;">
                <div style="font-size:13px;font-weight:700;letter-spacing:.08em;color:#dcfce7;">KPI KEPEGAWAIAN</div>
                <div style="margin-top:8px;font-size:24px;font-weight:700;line-height:1.3;">Atur ulang kata sandi</div>
            </td></tr>
            <tr><td style="padding:32px;">
                <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">Halo, {{ $name }}.</p>
                <p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:#475569;">Kami menerima permintaan untuk membuat kata sandi baru pada akun Anda. Gunakan tombol berikut untuk melanjutkan.</p>
                <p style="margin:0 0 24px;text-align:center;"><a href="{{ $resetUrl }}" style="display:inline-block;border-radius:9px;background:#16a34a;padding:13px 22px;color:#ffffff;font-size:15px;font-weight:700;text-decoration:none;">Buat Kata Sandi Baru</a></p>
                <div style="margin:0 0 24px;border:1px solid #bbf7d0;border-radius:9px;background:#f0fdf4;padding:14px 16px;color:#166534;font-size:14px;line-height:1.6;">Tautan ini berlaku selama {{ $expiresIn }} menit dan hanya dapat digunakan untuk akun yang menerima email ini.</div>
                <p style="margin:0 0 8px;font-size:14px;line-height:1.6;color:#475569;">Jika Anda tidak meminta perubahan kata sandi, abaikan email ini. Kata sandi Anda tidak akan berubah.</p>
                <p style="margin:20px 0 8px;font-size:12px;color:#64748b;">Jika tombol tidak dapat dibuka, salin tautan berikut ke browser:</p>
                <p style="margin:0;word-break:break-all;font-size:12px;line-height:1.6;color:#16a34a;">{{ $resetUrl }}</p>
            </td></tr>
            <tr><td style="border-top:1px solid #e2e8f0;padding:20px 32px;font-size:12px;line-height:1.6;color:#64748b;">Email otomatis dari Sistem Penilaian Karyawan. Jangan membalas email ini atau membagikan tautannya kepada siapa pun.</td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
