<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Akun Perpustakaan SMK Negeri 5 Surakarta</title>
</head>
<body style="margin:0;padding:24px 12px;background-color:#f8fafc;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#1e293b;">
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="max-width:520px;margin:0 auto;background-color:#ffffff;border:1px solid #e2e8f0;border-radius:12px;">
        <tr>
            <td style="padding:28px 28px 16px 28px;border-bottom:1px solid #f1f5f9;">
                <div style="font-size:17px;font-weight:700;color:#0f172a;">
                    Perpustakaan SMK Negeri 5 Surakarta
                </div>
                <div style="font-size:12px;color:#64748b;margin-top:2px;">
                    Layanan Peminjaman Buku Warga Sekolah (Pinjam BU-PUS)
                </div>
            </td>
        </tr>
        <tr>
            <td style="padding:24px 28px 12px 28px;font-size:14px;line-height:1.65;color:#334155;">
                Halo <strong>{{ $user->name ?: $user->email }}</strong>,<br><br>
                Kami menerima permintaan pendaftaran akun pembaca perpustakaan menggunakan alamat email <strong>{{ $user->email }}</strong>.<br><br>
                Silakan konfirmasi alamat email Anda dengan menekan tombol di bawah ini agar Anda dapat membuat kata sandi akun:
            </td>
        </tr>
        <tr>
            <td style="padding:12px 28px 20px 28px;text-align:left;">
                <a href="{{ $verificationUrl }}" style="display:inline-block;background-color:#2563eb;color:#ffffff;text-decoration:none;font-size:14px;font-weight:600;padding:12px 24px;border-radius:8px;">
                    Konfirmasi Email Saya
                </a>
            </td>
        </tr>
        <tr>
            <td style="padding:0 28px 24px 28px;font-size:12px;line-height:1.6;color:#64748b;">
                Apabila tombol di atas tidak dapat ditekan, silakan salin dan buka tautan berikut pada peramban Anda:<br>
                <a href="{{ $verificationUrl }}" style="color:#2563eb;word-break:break-all;">{{ $verificationUrl }}</a>
            </td>
        </tr>
        <tr>
            <td style="padding:16px 28px;background-color:#f8fafc;border-top:1px solid #f1f5f9;border-radius:0 0 12px 12px;font-size:11px;line-height:1.5;color:#64748b;">
                Pesan ini dikirim secara otomatis oleh Sistem Perpustakaan SMK Negeri 5 Surakarta (Jl. Adi Sucipto No. 42, Surakarta). Jika Anda tidak merasa mendaftar, Anda dapat mengabaikan email ini.
            </td>
        </tr>
    </table>
</body>
</html>
