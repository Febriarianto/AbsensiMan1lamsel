@extends('pdf.layout')

@section('content')
    <table class="meta-table">
        <tr>
            <th>Tahun</th>
            <td>{{ $filters['tahun'] ?? '-' }}</td>
            <th>Kelas</th>
            <td>{{ $filters['kelas'] ?? '-' }}</td>
        </tr>
        <tr>
            <th>Tahun Pelajaran</th>
            <td>{{ ($academicYear ?? '') !== '' ? $academicYear : (($settings['student_card_academic_year'] ?? '') !== '' ? $settings['student_card_academic_year'] : '-') }}</td>
            <th>Total Bulan</th>
            <td>{{ count($months ?? []) }}</td>
        </tr>
    </table>

    @forelse (($months ?? []) as $monthIndex => $month)
        @if ($monthIndex > 0)
            <div class="page-break"></div>
        @endif

        <div class="section-title">Bulan {{ $month['month_label'] ?? '-' }}</div>

        @foreach (($month['sections'] ?? []) as $sectionIndex => $section)
            @if ($sectionIndex > 0)
                <div style="margin-top: 10px;"></div>
            @endif
            @include('pdf.partials.rekap-bulanan-section', ['section' => $section])
        @endforeach
    @empty
        <div class="text-muted">Tidak ada data rekap tahunan untuk filter ini.</div>
    @endforelse

    <div class="footer-note">
        Dokumen ini merangkum rekap kehadiran per bulan untuk satu kelas dalam satu tahun yang dipilih.
        @if (($settings['student_card_academic_year'] ?? '') !== '')
            Tahun pelajaran aktif: {{ $settings['student_card_academic_year'] }}.
        @endif
    </div>
@endsection
