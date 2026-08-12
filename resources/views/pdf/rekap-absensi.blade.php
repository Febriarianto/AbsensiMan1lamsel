@extends('pdf.layout')

@section('content')
    <table class="meta-table">
        <tr>
            <th>Periode</th>
            <td>{{ $filters['periode'] ?? '-' }}</td>
            <th>Tahun Pelajaran</th>
            <td>{{ ($settings['student_card_academic_year'] ?? '') !== '' ? $settings['student_card_academic_year'] : '-' }}</td>
        </tr>
        <tr>
            <th>Kelas</th>
            <td>{{ $filters['kelas'] ?? 'Semua Kelas' }}</td>
            <th>Total Baris</th>
            <td>{{ count($rows ?? []) }}</td>
        </tr>
    </table>

    <table class="report-table">
        <thead>
            <tr>
                @foreach (($headers ?? []) as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse (($rows ?? []) as $row)
                <tr>
                    @foreach ((array) $row as $index => $cell)
                        <td class="{{ in_array($index, [0, 4, 5, 7], true) ? 'text-center' : '' }}">
                            {{ $cell !== '' ? $cell : '-' }}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ max(1, count($headers ?? [])) }}" class="text-center text-muted">Tidak ada data untuk periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer-note">
        Keterangan status mengikuti data absensi harian yang tersimpan pada sistem.
    </div>
@endsection
