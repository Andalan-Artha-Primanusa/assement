<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Undangan Screening Mechanic</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.6;">
    <h1 style="font-size: 20px;">Undangan Screening Mechanic</h1>
    <p>Halo {{ $user->name }},</p>
    <p>Anda diundang untuk mengikuti assessment Screening Mechanic. Silakan login melalui link berikut:</p>
    <p>
        <a href="{{ $loginUrl }}" style="color: #4f46e5;">{{ $loginUrl }}</a>
    </p>
    <table cellpadding="8" cellspacing="0" style="border-collapse: collapse; background: #f9fafb;">
        <tr>
            <td style="font-weight: bold;">Nama</td>
            <td>{{ $user->name }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Email</td>
            <td>{{ $user->email }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Password</td>
            <td>{{ $password }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Paket soal</td>
            <td>{{ $user->questionPackage?->name ?? 'Semua paket aktif' }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Akses sampai</td>
            <td>{{ $user->assessment_access_expires_at?->format('d M Y H:i') }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Durasi pengerjaan</td>
            <td>{{ round($user->assessment_duration_minutes / 60, 2) }} jam</td>
        </tr>
    </table>
    <p>Jangan membuka tab, aplikasi, atau halaman lain selama assessment berlangsung karena akses dapat terkunci otomatis.</p>
</body>
</html>
