@extends('pdf.layout')

@section('content')
    <table class="meta-table">
        <tr>
            <th>Bulan</th>
            <td>{{ $filters['bulan'] ?? '-' }}</td>
            <th>Tahun</th>
            <td>{{ $filters['tahun'] ?? '-' }}</td>
        </tr>
        <tr>
            <th>Kelas</th>
            <td>{{ $filters['kelas'] ?? 'Semua Kelas' }}</td>
            <th>Tahun Pelajaran</th>
            <td>{{ ($settings['student_card_academic_year'] ?? '') !== '' ? $settings['student_card_academic_year'] : '-' }}</td>
        </tr>
    </table>

    @forelse (($sections ?? []) as $index => $section)
        @if ($index > 0)
            <div class="page-break"></div>
        @endif
        @include('pdf.partials.rekap-bulanan-section', ['section' => $section])
    @empty
        <div class="text-muted">Tidak ada data rekap bulanan untuk filter ini.</div>
    @endforelse

    <div class="footer-note">
        Kode: H = Hadir, M = Masuk, S = Sakit, I = Izin, A = Alfa, L = Libur.
        @if (($settings['student_card_academic_year'] ?? '') !== '')
            Tahun pelajaran aktif: {{ $settings['student_card_academic_year'] }}.
        @endif
    </div>
@endsection
