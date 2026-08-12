<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Laporan' }}</title>
    <style>
        @page {
            margin: 20px 24px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #111827;
        }

        h1, h2, h3, h4, h5, h6, p {
            margin: 0;
        }

        .report-header {
            margin-bottom: 14px;
        }

        .letterhead-table,
        .meta-table,
        .report-table,
        .stats-table {
            width: 100%;
            border-collapse: collapse;
        }

        .letterhead-logo-cell,
        .letterhead-spacer-cell {
            width: 76px;
            vertical-align: top;
        }

        .letterhead-body-cell {
            text-align: center;
            vertical-align: top;
        }

        .letterhead-logo {
            width: 60px;
            max-height: 60px;
        }

        .school-name {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .school-slogan {
            font-size: 10px;
            color: #1f2937;
            margin-top: 3px;
        }

        .school-description {
            font-size: 9px;
            color: #374151;
            margin-top: 2px;
        }

        .school-contact {
            font-size: 9px;
            color: #4b5563;
            margin-top: 4px;
        }

        .letterhead-divider-primary {
            border-top: 2.5px solid #111827;
            margin-top: 10px;
        }

        .letterhead-divider-secondary {
            border-top: 1px solid #111827;
            margin-top: 2px;
        }

        .report-title {
            font-size: 14px;
            font-weight: 700;
            margin-top: 12px;
            text-transform: uppercase;
            text-align: center;
            letter-spacing: 0.08em;
        }

        .report-subtitle {
            font-size: 9px;
            color: #374151;
            margin-top: 4px;
            text-align: center;
        }

        .meta-table {
            margin-bottom: 12px;
        }

        .meta-table th,
        .meta-table td {
            padding: 4px 6px;
            border: 1px solid #d1d5db;
            vertical-align: top;
        }

        .meta-table th {
            width: 140px;
            text-align: left;
            background: #f9fafb;
            font-weight: 700;
        }

        .report-table {
            margin-bottom: 12px;
        }

        .report-table th,
        .report-table td,
        .stats-table th,
        .stats-table td {
            border: 1px solid #d1d5db;
            padding: 4px 5px;
            vertical-align: top;
        }

        .report-table th,
        .stats-table th {
            background: #f3f4f6;
            font-weight: 700;
            text-align: center;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-muted {
            color: #6b7280;
        }

        .section-title {
            font-size: 12px;
            font-weight: 700;
            margin: 12px 0 6px;
            text-transform: uppercase;
        }

        .section-note {
            font-size: 9px;
            color: #4b5563;
            margin-bottom: 6px;
        }

        .page-break {
            page-break-before: always;
        }

        .keep-together {
            page-break-inside: avoid;
        }

        .code {
            font-weight: 700;
            text-align: center;
        }

        .code-H {
            color: #15803d;
        }

        .code-M {
            color: #0284c7;
        }

        .code-S {
            color: #a16207;
        }

        .code-I {
            color: #1d4ed8;
        }

        .code-A {
            color: #b91c1c;
        }

        .code-L {
            color: #9ca3af;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: 700;
        }

        .badge-success {
            background: #dcfce7;
            color: #166534;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-info {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .footer-note {
            margin-top: 10px;
            font-size: 9px;
            color: #6b7280;
        }

        .signature-block {
            margin-top: 18px;
            width: 280px;
            float: right;
            text-align: center;
        }

        .signature-space {
            height: 58px;
        }
    </style>
    @yield('extra_styles')
</head>
<body>
    @php
        $schoolName = trim((string) ($settings['website_nama'] ?? 'E-ABSENSI'));
        $schoolSlogan = trim((string) ($settings['website_slogan'] ?? ''));
        $schoolDescription = trim((string) ($settings['website_deskripsi'] ?? ''));
        $schoolContactItems = array_values(array_filter([
            trim((string) ($settings['website_telepon'] ?? '')) !== '' ? 'Telp/WA: ' . trim((string) $settings['website_telepon']) : '',
            trim((string) ($settings['website_email'] ?? '')) !== '' ? 'Email: ' . trim((string) $settings['website_email']) : '',
        ], fn ($value) => $value !== ''));
        $schoolContactLine = implode(' | ', $schoolContactItems);
        $logoDataUri = trim((string) ($settings['website_logo_data_uri'] ?? ''));
    @endphp

    <div class="report-header">
        <table class="letterhead-table">
            <tr>
                <td class="letterhead-logo-cell">
                    @if ($logoDataUri !== '')
                        <img src="{{ $logoDataUri }}" alt="Logo Sekolah" class="letterhead-logo">
                    @endif
                </td>
                <td class="letterhead-body-cell">
                    <div class="school-name">{{ $schoolName !== '' ? $schoolName : 'E-ABSENSI' }}</div>
                    @if ($schoolSlogan !== '')
                        <div class="school-slogan">{{ $schoolSlogan }}</div>
                    @endif
                    @if ($schoolDescription !== '')
                        <div class="school-description">{{ $schoolDescription }}</div>
                    @endif
                    @if ($schoolContactLine !== '')
                        <div class="school-contact">{{ $schoolContactLine }}</div>
                    @endif
                </td>
                <td class="letterhead-spacer-cell"></td>
            </tr>
        </table>
        <div class="letterhead-divider-primary"></div>
        <div class="letterhead-divider-secondary"></div>
        <div class="report-title">{{ $title ?? 'Laporan' }}</div>
        <div class="report-subtitle">Dicetak pada {{ ($printedAt ?? now())->translatedFormat('d M Y H:i') }}</div>
    </div>

    @yield('content')
</body>
</html>
