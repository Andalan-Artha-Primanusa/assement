<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Undangan Assessment - Andalan HR</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f3f4f6; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08);">

                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%); padding: 40px 40px 30px 40px; text-align: center;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="text-align: center;">
                                        <div style="display: inline-block; background-color: rgba(255,255,255,0.15); border-radius: 12px; padding: 12px 20px; margin-bottom: 16px;">
                                            <span style="font-size: 20px; font-weight: 700; color: #ffffff; letter-spacing: 2px;">ANDALAN HR</span>
                                        </div>
                                        <br>
                                        <span style="font-size: 24px; font-weight: 700; color: #ffffff; display: block; margin-top: 8px;">Undangan Assessment</span>
                                        <span style="font-size: 14px; color: rgba(255,255,255,0.8); display: block; margin-top: 6px;">Screening Mechanic</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Greeting -->
                    <tr>
                        <td style="padding: 36px 40px 0 40px;">
                            <p style="margin: 0; font-size: 16px; color: #374151;">Halo <strong>{{ $user->name }}</strong>,</p>
                            <p style="margin: 12px 0 0 0; font-size: 15px; color: #6b7280; line-height: 1.7;">
                                Anda diundang untuk mengikuti <strong>Assessment Screening Mechanic</strong> dari Andalan HR.
                                Berikut adalah akun Anda untuk mengakses platform assessment:
                            </p>
                        </td>
                    </tr>

                    <!-- Credentials Card -->
                    <tr>
                        <td style="padding: 24px 40px 0 40px;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden;">
                                <tr>
                                    <td style="padding: 24px 28px;">
                                        <span style="font-size: 12px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 1.5px;">Akun Anda</span>
                                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 16px;">
                                            <tr>
                                                <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; font-size: 14px; color: #6b7280; width: 40%;">Nama</td>
                                                <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; font-size: 14px; color: #111827; font-weight: 600;">{{ $user->name }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; font-size: 14px; color: #6b7280;">Email</td>
                                                <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; font-size: 14px; color: #111827; font-weight: 600;">{{ $user->email }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; font-size: 14px; color: #6b7280;">Password</td>
                                                <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; font-size: 14px; color: #111827;">
                                                    <span style="background-color: #fef3c7; border: 1px solid #f59e0b; border-radius: 6px; padding: 3px 10px; font-family: 'Courier New', monospace; font-size: 14px; font-weight: 700; color: #92400e;">{{ $password }}</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Detail Info -->
                    <tr>
                        <td style="padding: 20px 40px 0 40px;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; overflow: hidden;">
                                <tr>
                                    <td style="padding: 24px 28px;">
                                        <span style="font-size: 12px; font-weight: 700; color: #1e40af; text-transform: uppercase; letter-spacing: 1.5px;">Detail Assessment</span>
                                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 16px;">
                                            <tr>
                                                <td style="padding: 10px 0; border-bottom: 1px solid #c7d9f0; font-size: 14px; color: #1e40af; width: 40%;">Paket Soal</td>
                                                <td style="padding: 10px 0; border-bottom: 1px solid #c7d9f0; font-size: 14px; color: #111827; font-weight: 600;">{{ $user->questionPackage?->name ?? 'Semua paket aktif' }}{{ $user->questionPackage?->level ? ' ('.$user->questionPackage->level.')' : '' }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 10px 0; border-bottom: 1px solid #c7d9f0; font-size: 14px; color: #1e40af;">Akses Sampai</td>
                                                <td style="padding: 10px 0; border-bottom: 1px solid #c7d9f0; font-size: 14px; color: #111827; font-weight: 600;">{{ $user->assessment_access_expires_at?->format('d M Y') }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 10px 0; border-bottom: 1px solid #c7d9f0; font-size: 14px; color: #1e40af;">Sisa Waktu Akses</td>
                                                <td style="padding: 10px 0; border-bottom: 1px solid #c7d9f0; font-size: 14px; font-weight: 700; color: #dc2626;">{{ $accessDays ?? ($user->assessment_access_expires_at ? $user->assessment_access_expires_at->diffForHumans() : '-') }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 10px 0; font-size: 14px; color: #1e40af;">Durasi Pengerjaan</td>
                                                <td style="padding: 10px 0; font-size: 14px; color: #111827; font-weight: 600;">{{ $durationHours ?? round($user->assessment_duration_minutes / 60, 2) }} jam</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- CTA Button -->
                    <tr>
                        <td style="padding: 32px 40px 0 40px; text-align: center;">
                            <table cellpadding="0" cellspacing="0" style="margin: 0 auto;">
                                <tr>
                                    <td style="background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); border-radius: 8px;">
                                        <a href="{{ $loginUrl }}" style="display: inline-block; padding: 14px 48px; font-size: 15px; font-weight: 700; color: #ffffff; text-decoration: none; letter-spacing: 0.5px;">LOGIN SEKARANG</a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin: 14px 0 0 0; font-size: 13px; color: #9ca3af;">{{ $loginUrl }}</p>
                        </td>
                    </tr>

                    <!-- Warning -->
                    <tr>
                        <td style="padding: 28px 40px 0 40px;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 10px;">
                                <tr>
                                    <td style="padding: 16px 20px;">
                                        <table cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="vertical-align: top; padding-right: 10px;">
                                                    <span style="font-size: 16px;">&#9888;</span>
                                                </td>
                                                <td style="font-size: 13px; color: #991b1b; line-height: 1.6;">
                                                    <strong>Peringatan Penting</strong><br>
                                                    Jangan membuka tab, aplikasi, atau halaman lain selama assessment berlangsung. Akses akan terkunci otomatis jika terdeteksi.
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 36px 40px 32px 40px;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="border-top: 1px solid #e5e7eb; padding-top: 24px;">
                                <tr>
                                    <td style="padding-top: 24px; text-align: center;">
                                        <p style="margin: 0; font-size: 13px; color: #9ca3af;">Email ini dikirim oleh <strong>Andalan HR</strong></p>
                                        <p style="margin: 6px 0 0 0; font-size: 12px; color: #d1d5db;">&copy; {{ date('Y') }} Andalan HR. All rights reserved.</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
