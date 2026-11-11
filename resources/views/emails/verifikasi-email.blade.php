<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi email MedanVisit</title>
</head>
<body style="margin:0;background:#ece7da;color:#1b2622;font-family:Arial,Helvetica,sans-serif;line-height:1.6;">
    <div style="padding:32px 16px;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;margin:0 auto;background:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 12px 32px rgba(15,59,46,.12);">
            <tr>
                <td style="padding:30px 32px;background:#0f3b2e;text-align:center;border-bottom:5px solid #e3a51c;">
                    <img src="{{ asset('storage/logo_app/svg/MedanVisit_logo_tanpa-tagline_gelap.svg') }}" alt="MedanVisit" width="184" style="display:inline-block;width:184px;height:auto;">
                </td>
            </tr>
            <tr>
                <td style="padding:40px 40px 32px;">
                    <p style="margin:0 0 12px;color:#a06b00;font-size:12px;font-weight:bold;letter-spacing:1.5px;text-transform:uppercase;">Selamat datang di perjalananmu</p>
                    <h1 style="margin:0 0 16px;color:#0f3b2e;font-size:30px;line-height:1.2;">Konfirmasi emailmu</h1>
                    <p style="margin:0 0 24px;color:#3f574e;">Terima kasih sudah mendaftar di MedanVisit. Konfirmasi alamat emailmu untuk mengaktifkan akun dan mulai menjelajahi Kota Medan.</p>
                    <p style="margin:0 0 28px;text-align:center;">
                        <a href="{{ $url }}" style="display:inline-block;padding:14px 26px;border-radius:8px;background:#e3a51c;color:#0f3b2e;font-weight:bold;text-decoration:none;">Konfirmasi email</a>
                    </p>
                    <div style="padding:16px 18px;background:#f6f1e4;border-left:4px solid #e3a51c;border-radius:8px;">
                        <p style="margin:0;color:#52645b;font-size:13px;">Tautan ini berlaku selama 24 jam. Jika tombol tidak dapat dibuka, salin tautan berikut ke browser:</p>
                        <p style="margin:8px 0 0;word-break:break-all;font-size:12px;"><a href="{{ $url }}" style="color:#0f3b2e;">{{ $url }}</a></p>
                    </div>
                </td>
            </tr>
            <tr>
                <td style="padding:20px 40px;background:#f6f1e4;color:#52645b;font-size:12px;">
                    Jika kamu tidak membuat akun MedanVisit, abaikan email ini. Email ini dikirim otomatis, jadi tidak perlu membalas.
                </td>
            </tr>
        </table>
        <p style="margin:18px auto 0;max-width:620px;text-align:center;color:#52645b;font-size:12px;">© 2026 MedanVisit · Kota Medan</p>
    </div>
</body>
</html>
