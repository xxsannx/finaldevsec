<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode OTP Pembayaran</title>
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
        .booking-details {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .total-row {
            background-color: #dcfce7;
            margin-top: 10px;
            padding: 12px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 18px;
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
            <p>Konfirmasi Pembayaran Booking</p>
        </div>

        <div class="content">
            <h2>Verifikasi Pembayaran</h2>
            
            <p>Halo, <strong>{{ $booking->user->name }}</strong>!</p>
            
            <p>Terima kasih telah melakukan booking di Pineus Tilu. Untuk menyelesaikan pembayaran booking Anda, silakan gunakan kode OTP berikut:</p>

            <div class="otp-box">
                <p style="margin: 0; font-size: 14px;">Kode OTP Pembayaran:</p>
                <div class="otp-code">{{ $otp }}</div>
                <p style="margin: 0; font-size: 12px;">Berlaku selama 10 menit</p>
            </div>

            <h3>Detail Booking Anda:</h3>
            <div class="booking-details">
                <div class="detail-row">
                    <span>Booking ID:</span>
                    <span><strong>#{{ str_pad($booking->id, 6, '0', STR_PAD_LEFT) }}</strong></span>
                </div>
                <div class="detail-row">
                    <span>Spot Camping:</span>
                    <span><strong>{{ $booking->room->name }}</strong></span>
                </div>
                <div class="detail-row">
                    <span>Tipe:</span>
                    <span>{{ $booking->room->type }}</span>
                </div>
                <div class="detail-row">
                    <span>Check-in:</span>
                    <span>{{ $booking->check_in->format('d M Y') }}</span>
                </div>
                <div class="detail-row">
                    <span>Check-out:</span>
                    <span>{{ $booking->check_out->format('d M Y') }}</span>
                </div>
                <div class="detail-row">
                    <span>Durasi:</span>
                    <span>{{ $booking->duration }} malam</span>
                </div>
                <div class="total-row">
                    <div class="detail-row" style="border: none;">
                        <span>Total Pembayaran:</span>
                        <span style="color: #16a34a;">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <p><strong>Langkah selanjutnya:</strong></p>
            <ol>
                <li>Kembali ke halaman pembayaran</li>
                <li>Masukkan kode OTP di atas</li>
                <li>Klik "Konfirmasi Pembayaran"</li>
                <li>Pembayaran Anda akan segera diproses</li>
            </ol>

            <div class="warning">
                <strong>⚠️ Penting:</strong>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>Kode OTP ini bersifat rahasia dan hanya untuk Anda</li>
                    <li>Jangan bagikan kode ini kepada siapapun termasuk staff kami</li>
                    <li>Kode akan kadaluarsa dalam 10 menit</li>
                    <li>Jika Anda tidak melakukan transaksi ini, segera hubungi kami</li>
                </ul>
            </div>

            <p style="background-color: #dcfce7; padding: 15px; border-radius: 6px; border-left: 4px solid #22c55e;">
                <strong>💡 Tips:</strong> Pastikan Anda sudah menyiapkan peralatan camping dan memeriksa cuaca sebelum tanggal check-in. Selamat berpetualang! 🏕️
            </p>

            <p>Butuh bantuan? Hubungi kami di:</p>
            <ul style="list-style: none; padding: 0;">
                <li>📧 Email: <a href="mailto:support@pineustilu.com" style="color: #22c55e;">support@pineustilu.com</a></li>
                <li>📱 WhatsApp: <a href="tel:+6281234567890" style="color: #22c55e;">+62 812-3456-7890</a></li>
                <li>🕐 Jam operasional: 08:00 - 20:00 WIB</li>
            </ul>
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