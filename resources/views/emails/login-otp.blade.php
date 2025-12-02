<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode OTP Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 2px solid #22c55e;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #22c55e;
        }
        .content {
            margin: 30px 0;
        }
        .otp-box {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
        }
        .otp-code {
            font-size: 36px;
            font-weight: bold;
            letter-spacing: 8px;
            margin: 10px 0;
        }
        .warning {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🏕️ Pineus Tilu</div>
            <p>Sistem Booking Camping Terpercaya</p>
        </div>

        <div class="content">
            <h2>Halo, {{ $user->name }}!</h2>
            
            <p>Anda telah meminta untuk login ke akun Pineus Tilu Anda. Gunakan kode OTP berikut untuk menyelesaikan proses login:</p>

            <div class="otp-box">
                <p style="margin: 0; font-size: 14px;">Kode OTP Login Anda:</p>
                <div class="otp-code">{{ $otp }}</div>
                <p style="margin: 0; font-size: 12px;">Berlaku selama 10 menit</p>
            </div>

            <p><strong>Cara menggunakan OTP:</strong></p>
            <ol>
                <li>Kembali ke halaman login Pineus Tilu</li>
                <li>Masukkan kode OTP di atas</li>
                <li>Klik tombol "Verifikasi"</li>
            </ol>

            <div class="warning">
                <strong>⚠️ Peringatan Keamanan:</strong>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>Jangan bagikan kode OTP ini kepada siapapun</li>
                    <li>Tim Pineus Tilu tidak akan pernah meminta kode OTP Anda</li>
                    <li>Kode ini akan kadaluarsa dalam 10 menit</li>
                    <li>Jika Anda tidak melakukan permintaan login ini, abaikan email ini</li>
                </ul>
            </div>

            <p>Jika Anda mengalami kesulitan, silakan hubungi tim support kami di <a href="mailto:support@pineustilu.com" style="color: #22c55e;">support@pineustilu.com</a></p>
        </div>

        <div class="footer">
            <p>Email ini dikirim secara otomatis, mohon tidak membalas email ini.</p>
            <p>&copy; {{ date('Y') }} Pineus Tilu. All rights reserved.</p>
            <p style="margin-top: 10px;">
                <a href="#" style="color: #22c55e; text-decoration: none; margin: 0 10px;">Website</a> |
                <a href="#" style="color: #22c55e; text-decoration: none; margin: 0 10px;">Bantuan</a> |
                <a href="#" style="color: #22c55e; text-decoration: none; margin: 0 10px;">Kontak</a>
            </p>
        </div>
    </div>
</body>
</html>