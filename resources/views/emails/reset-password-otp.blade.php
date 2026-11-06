<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode OTP Pemulihan Kata Sandi</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f1f5f9; color: #1e293b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f1f5f9; padding: 30px 15px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width: 560px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); border: 1px solid #e2e8f0;">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #1d4ed8 0%, #3730a3 100%); padding: 32px 24px; text-align: center; color: #ffffff;">
                            <div style="display: inline-block; padding: 4px 12px; background-color: rgba(255, 255, 255, 0.2); border-radius: 9999px; font-size: 11px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; margin-bottom: 8px;">
                                RAPI-Sekolah
                            </div>
                            <h1 style="margin: 0; font-size: 22px; font-weight: 800; letter-spacing: -0.02em;">Kode Verifikasi OTP</h1>
                            <p style="margin: 6px 0 0 0; font-size: 13px; color: #bfdbfe;">Permintaan Pemulihan Kata Sandi Akun</p>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 32px 28px;">
                            <p style="margin: 0 0 16px 0; font-size: 15px; line-height: 1.5; color: #334155;">
                                Halo <strong>{{ $user->fullname ?? $user->username }}</strong>,
                            </p>
                            <p style="margin: 0 0 20px 0; font-size: 14px; line-height: 1.6; color: #475569;">
                                Kami menerima permintaan untuk mengatur ulang kata sandi pada akun <strong>{{ $user->username }}</strong>. Masukkan kode One-Time Password (OTP) berikut untuk melanjutkan proses pemulihan:
                            </p>

                            <!-- OTP Box -->
                            <div style="background-color: #eff6ff; border: 2px dashed #3b82f6; border-radius: 12px; padding: 20px; text-align: center; margin: 24px 0;">
                                <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #3b82f6; margin-bottom: 6px;">
                                    KODE VERIFIKASI OTP (6 DIGIT)
                                </div>
                                <div style="font-family: 'Courier New', Courier, monospace; font-size: 34px; font-weight: 800; letter-spacing: 8px; color: #1e40af;">
                                    {{ $otp }}
                                </div>
                                <div style="font-size: 12px; color: #64748b; margin-top: 8px;">
                                    Berlaku selama <strong>{{ $expiresInMinutes }} menit</strong> sejak email ini dikirim.
                                </div>
                            </div>

                            @if (!empty($resetUrl))
                                <div style="text-align: center; margin: 24px 0 16px 0;">
                                    <a href="{{ $resetUrl }}" style="display: inline-block; padding: 12px 28px; background-color: #2563eb; color: #ffffff; text-decoration: none; font-size: 13px; font-weight: 700; border-radius: 10px; box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);">
                                        Buka Formulir Atur Ulang Sandi &rarr;
                                    </a>
                                </div>
                            @endif

                            <div style="margin-top: 28px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                                <p style="margin: 0 0 8px 0; font-size: 12px; font-weight: 700; color: #e11d48;">
                                    Penting & Keamanan Akun:
                                </p>
                                <p style="margin: 0; font-size: 12px; line-height: 1.5; color: #64748b;">
                                    Jangan berikan kode ini kepada siapa pun, termasuk pihak sekolah atau pengelola sistem. Jika Anda tidak merasa melakukan permohonan ini, Anda dapat mengabaikan email ini dengan aman.
                                </p>
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 20px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 11px; color: #94a3b8;">
                            Email ini dibuat otomatis oleh sistem RAPI-Sekolah.<br>
                            &copy; {{ date('Y') }} RAPI-Sekolah. Hak Cipta Dilindungi.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

