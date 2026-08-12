@extends('pdf.layout')

@section('content')
    @php
        $kelasOption = collect($options['kelas'] ?? [])->firstWhere('id', $filters['kelas_id'] ?? 0);
        $guruOption = collect($options['guru'] ?? [])->firstWhere('id', $filters['guru_id'] ?? 0);
        $statusMap = [
            'closed' => 'Sesi Ditutup',
            'open' => 'Sesi Berjalan',
            'all' => 'Semua Sesi',
        ];
    @endphp

    <table class="meta-table">
        <tr>
            <th>Periode</th>
            <td>{{ $filters['periode'] ?? '-' }}</td>
            <th>Status Sesi</th>
            <td>{{ $statusMap[$filters['status_sesi'] ?? 'closed'] ?? 'Sesi Ditutup' }}</td>
        </tr>
        <tr>
            <th>Kelas</th>
            <td>{{ is_array($kelasOption) ? ($kelasOption['nama'] ?? 'Semua Kelas') : 'Semua Kelas' }}</td>
            <th>Guru</th>
            <td>{{ is_array($guruOption) ? ($guruOption['nama'] ?? 'Semua Guru') : 'Semua Guru' }}</td>
        </tr>
        <tr>
            <th>Mapel</th>
            <td>{{ ($filters['mapel'] ?? '') !== '' ? $filters['mapel'] : 'Semua Mapel' }}</td>
            <th>Tahun Pelajaran</th>
            <td>{{ ($settings['student_card_academic_year'] ?? '') !== '' ? $settings['student_card_academic_year'] : '-' }}</td>
        </tr>
        <tr>
            <th>Pencarian</th>
            <td>{{ ($filters['search'] ?? '') !== '' ? $filters['search'] : '-' }}</td>
            <th>Total Sesi</th>
            <td>{{ count($sessions ?? []) }}</td>
        </tr>
    </table>

    <div class="section-title">Ringkasan Statistik</div>
    <table class="stats-table">
        <tr>
            <th>Total Sesi</th>
            <th>Sesi Ditutup</th>
            <th>Sesi Berjalan</th>
            <th>Rekap Siswa</th>
            <th>Hadir</th>
            <th>Terlambat</th>
            <th>Izin</th>
            <th>Sakit</th>
            <th>Alfa</th>
            <th>Belum</th>
        </tr>
        <tr class="text-center">
            <td>{{ (int) ($stats['total_sessions'] ?? 0) }}</td>
            <td>{{ (int) ($stats['closed_sessions'] ?? 0) }}</td>
            <td>{{ (int) ($stats['open_sessions'] ?? 0) }}</td>
            <td>{{ (int) ($stats['students'] ?? 0) }}</td>
            <td>{{ (int) ($stats['hadir'] ?? 0) }}</td>
            <td>{{ (int) ($stats['terlambat'] ?? 0) }}</td>
            <td>{{ (int) ($stats['izin'] ?? 0) }}</td>
            <td>{{ (int) ($stats['sakit'] ?? 0) }}</td>
            <td>{{ (int) ($stats['alfa'] ?? 0) }}</td>
            <td>{{ (int) ($stats['belum'] ?? 0) }}</td>
        </tr>
    </table>

    <div class="section-title">Ringkasan Sesi</div>
    <table class="report-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Kelas</th>
                <th>Mapel</th>
                <th>Guru</th>
                <th>Jam</th>
                <th>Status</th>
                <th>Total</th>
                <th>H</th>
                <th>T</th>
                <th>I</th>
                <th>S</th>
                <th>A</th>
                <th>Belum</th>
                <th>%</th>
            </tr>
        </thead>
        <tbody>
            @forelse (($sessions ?? []) as $index => $row)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $row['tanggal_label'] ?? ($row['tanggal'] ?? '-') }}</td>
                    <td>{{ $row['kelas_nama'] ?? '-' }}</td>
                    <td>{{ $row['mata_pelajaran'] ?? '-' }}</td>
                    <td>{{ $row['guru_nama'] ?? '-' }}</td>
                    <td class="text-center">{{ ($row['jam_mulai'] ?? '-') . (($row['jam_selesai'] ?? '') !== '' ? ' - ' . $row['jam_selesai'] : '') }}</td>
                    <td class="text-center">{{ $row['status_label'] ?? '-' }}</td>
                    <td class="text-center">{{ (int) ($row['total_siswa'] ?? 0) }}</td>
                    <td class="text-center">{{ (int) ($row['hadir'] ?? 0) }}</td>
                    <td class="text-center">{{ (int) ($row['terlambat'] ?? 0) }}</td>
                    <td class="text-center">{{ (int) ($row['izin'] ?? 0) }}</td>
                    <td class="text-center">{{ (int) ($row['sakit'] ?? 0) }}</td>
                    <td class="text-center">{{ (int) ($row['alfa'] ?? 0) }}</td>
                    <td class="text-center">{{ (int) ($row['belum'] ?? 0) }}</td>
                    <td class="text-center">{{ number_format((float) ($row['kehadiran_rate'] ?? 0), 1) }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="15" class="text-center text-muted">Tidak ada data sesi.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Rekap Siswa</div>
    <table class="report-table">
        <thead>
            <tr>
                <th>No</th>
                <th>NISN</th>
                <th>Nama</th>
                <th>Kelas</th>
                <th>Total Sesi</th>
                <th>H</th>
                <th>T</th>
                <th>I</th>
                <th>S</th>
                <th>A</th>
                <th>Belum</th>
                <th>% Hadir</th>
            </tr>
        </thead>
        <tbody>
            @forelse (($students ?? []) as $index => $row)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $row['nisn'] ?? '-' }}</td>
                    <td>{{ $row['nama'] ?? '-' }}</td>
                    <td>{{ $row['kelas_nama'] ?? '-' }}</td>
                    <td class="text-center">{{ (int) ($row['total_sesi'] ?? 0) }}</td>
                    <td class="text-center">{{ (int) ($row['hadir'] ?? 0) }}</td>
                    <td class="text-center">{{ (int) ($row['terlambat'] ?? 0) }}</td>
                    <td class="text-center">{{ (int) ($row['izin'] ?? 0) }}</td>
                    <td class="text-center">{{ (int) ($row['sakit'] ?? 0) }}</td>
                    <td class="text-center">{{ (int) ($row['alfa'] ?? 0) }}</td>
                    <td class="text-center">{{ (int) ($row['belum'] ?? 0) }}</td>
                    <td class="text-center">{{ number_format((float) ($row['kehadiran_rate'] ?? 0), 1) }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="text-center text-muted">Tidak ada data siswa.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @foreach (($sessionDetails ?? []) as $detailIndex => $detail)
        <div class="page-break"></div>
        @php
            $session = $detail['session'] ?? [];
            $sessionMeta = is_array($session) ? $session : [];
            $sessionClass = $sessionMeta['kelas']['nama'] ?? '-';
            $sessionTeacher = $sessionMeta['guru']['nama'] ?? '-';
            $sessionTime = trim(((string) ($sessionMeta['jam_mulai'] ?? '')) . (((string) ($sessionMeta['jam_selesai'] ?? '')) !== '' ? ' - ' . $sessionMeta['jam_selesai'] : ''));
        @endphp

        <div class="section-title">Detail Sesi {{ $detailIndex + 1 }}</div>
        <table class="meta-table">
            <tr>
                <th>Tanggal</th>
                <td>{{ $sessionMeta['tanggal_label'] ?? ($sessionMeta['tanggal'] ?? '-') }}</td>
                <th>Kelas</th>
                <td>{{ $sessionClass }}</td>
            </tr>
            <tr>
                <th>Mapel</th>
                <td>{{ $sessionMeta['mata_pelajaran'] ?? '-' }}</td>
                <th>Guru</th>
                <td>{{ $sessionTeacher }}</td>
            </tr>
            <tr>
                <th>Jam</th>
                <td>{{ $sessionTime !== '' ? $sessionTime : '-' }}</td>
                <th>Status</th>
                <td>{{ ucfirst($sessionMeta['status'] ?? '-') }}</td>
            </tr>
        </table>

        <table class="report-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>NISN</th>
                    <th>Nama</th>
                    <th>Status</th>
                    <th>Metode</th>
                    <th>Jam Catat</th>
                </tr>
            </thead>
            <tbody>
                @forelse (($detail['students'] ?? []) as $studentIndex => $student)
                    <tr>
                        <td class="text-center">{{ $studentIndex + 1 }}</td>
                        <td class="text-center">{{ $student['nisn'] ?? '-' }}</td>
                        <td>{{ $student['nama'] ?? '-' }}</td>
                        <td class="text-center">{{ ($student['status'] ?? '') !== '' ? $student['status'] : 'Belum Absen' }}</td>
                        <td class="text-center">{{ $student['method'] ?? '-' }}</td>
                        <td class="text-center">{{ $student['recorded_at'] ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">Tidak ada detail siswa.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endforeach
@endsection
