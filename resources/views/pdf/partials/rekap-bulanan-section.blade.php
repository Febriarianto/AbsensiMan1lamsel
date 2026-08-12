<div class="keep-together">
    <div class="section-title text-center">{{ $section['title'] ?? 'ABSENSI SISWA' }}</div>
    @if (!empty($section['subtitle'] ?? ''))
        <div class="section-note text-center">{{ $section['subtitle'] }}</div>
    @endif

    <table class="report-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 28px;">No</th>
                <th rowspan="2" style="width: 72px;">NISN</th>
                <th rowspan="2" style="width: 160px;">Nama Siswa</th>
                <th colspan="{{ count($section['day_numbers'] ?? []) }}">Tanggal</th>
                <th rowspan="2" style="width: 30px;">H</th>
                <th rowspan="2" style="width: 30px;">S</th>
                <th rowspan="2" style="width: 30px;">I</th>
                <th rowspan="2" style="width: 30px;">A</th>
            </tr>
            <tr>
                @foreach (($section['day_numbers'] ?? []) as $dayNumber)
                    <th style="width: 18px;">{{ $dayNumber }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse (($section['students'] ?? []) as $row)
                <tr>
                    <td class="text-center">{{ $row['no'] }}</td>
                    <td class="text-center">{{ $row['nisn'] }}</td>
                    <td>{{ $row['nama'] }}</td>
                    @foreach (($row['daily_codes'] ?? []) as $code)
                        @php
                            $normalizedCode = strtoupper(trim((string) $code));
                            $codeClass = match ($normalizedCode) {
                                'H' => 'code-H',
                                'M' => 'code-M',
                                'S' => 'code-S',
                                'I' => 'code-I',
                                'A' => 'code-A',
                                'L' => 'code-L',
                                default => '',
                            };
                        @endphp
                        <td class="code {{ $codeClass }}">{{ $normalizedCode !== '' ? $normalizedCode : '-' }}</td>
                    @endforeach
                    <td class="text-center">{{ $row['hadir'] }}</td>
                    <td class="text-center">{{ $row['sakit'] }}</td>
                    <td class="text-center">{{ $row['izin'] }}</td>
                    <td class="text-center">{{ $row['alfa'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 7 + count($section['day_numbers'] ?? []) }}" class="text-center text-muted">Tidak ada data siswa.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
