@extends('pdf.layout')

@section('content')
    @php
        $statusMap = [
            'draft' => 'Draft',
            'selesai' => 'Selesai',
        ];

        $periodeLabel = '-';
        if (($filters['tanggal_dari'] ?? '') !== '' || ($filters['tanggal_sampai'] ?? '') !== '') {
            $tanggalDari = trim((string) ($filters['tanggal_dari'] ?? ''));
            $tanggalSampai = trim((string) ($filters['tanggal_sampai'] ?? ''));
            if ($tanggalDari === '') {
                $tanggalDari = $tanggalSampai;
            }
            if ($tanggalSampai === '') {
                $tanggalSampai = $tanggalDari;
            }

            try {
                $periodeLabel = \Carbon\Carbon::parse($tanggalDari)->translatedFormat('d M Y');
                if ($tanggalSampai !== $tanggalDari) {
                    $periodeLabel .= ' s/d ' . \Carbon\Carbon::parse($tanggalSampai)->translatedFormat('d M Y');
                }
            } catch (\Throwable $e) {
                $periodeLabel = trim($tanggalDari . ' s/d ' . $tanggalSampai);
            }
        }
    @endphp

    <table class="meta-table">
        <tr>
            <th>Periode</th>
            <td>{{ $periodeLabel }}</td>
            <th>Tahun Pelajaran</th>
            <td>{{ ($settings['student_card_academic_year'] ?? '') !== '' ? $settings['student_card_academic_year'] : '-' }}</td>
        </tr>
        <tr>
            <th>Kelas</th>
            <td>{{ $kelasLabel }}</td>
            <th>Status</th>
            <td>{{ $statusMap[$filters['status'] ?? ''] ?? 'Semua Status' }}</td>
        </tr>
        <tr>
            <th>Pencarian</th>
            <td>{{ ($filters['q'] ?? '') !== '' ? $filters['q'] : '-' }}</td>
            <th>Dicetak Oleh</th>
            <td>{{ $printedBy !== '' ? $printedBy : '-' }}</td>
        </tr>
    </table>

    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 28px;">No</th>
                <th style="width: 74px;">Tanggal</th>
                <th style="width: 70px;">Kelas</th>
                <th style="width: 90px;">Guru</th>
                <th style="width: 85px;">Mapel</th>
                <th>Topik Materi</th>
                <th>Ringkasan</th>
                <th>Tugas</th>
                <th>Catatan</th>
                <th style="width: 52px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse (($rows ?? []) as $index => $row)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ ($row['tanggal'] ?? '') !== '' ? \Carbon\Carbon::parse($row['tanggal'])->translatedFormat('d M Y') : '-' }}</td>
                    <td>{{ $row['kelas_nama'] ?? '-' }}</td>
                    <td>{{ $row['guru_nama'] ?? '-' }}</td>
                    <td>{{ $row['mata_pelajaran'] ?? '-' }}</td>
                    <td>{{ ($row['topik_materi'] ?? '') !== '' ? $row['topik_materi'] : '-' }}</td>
                    <td>{{ ($row['ringkasan_pembelajaran'] ?? '') !== '' ? $row['ringkasan_pembelajaran'] : '-' }}</td>
                    <td>{{ ($row['tugas_siswa'] ?? '') !== '' ? $row['tugas_siswa'] : '-' }}</td>
                    <td>{{ ($row['catatan'] ?? '') !== '' ? $row['catatan'] : '-' }}</td>
                    <td class="text-center">{{ $statusMap[$row['status'] ?? ''] ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center text-muted">Tidak ada data jurnal untuk filter ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="signature-block keep-together">
        <div>{{ ($printedAt ?? now())->translatedFormat('d F Y') }}</div>
        <div style="margin-top: 4px; font-weight: 700;">Mengetahui,</div>
        <div>{{ ($settings['report_signer_position'] ?? '') !== '' ? $settings['report_signer_position'] : 'Kepala Sekolah' }}</div>
        <div class="signature-space"></div>
        <div style="font-weight: 700;">
            {{ ($settings['report_signer_name'] ?? '') !== '' ? $settings['report_signer_name'] : '(...................................)' }}
        </div>
    </div>
    <div style="clear: both;"></div>
@endsection
