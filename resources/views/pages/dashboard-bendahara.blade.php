@extends('layouts.page')

@section('title', 'Dashboard Bendahara')

@section('content')
@php
    $dashboard = $bendaharaDashboard ?? [];
    $summary = $dashboard['summary'] ?? [];
    $monthly = $dashboard['monthly_summary'] ?? [];
    $typeChart = $dashboard['type_chart'] ?? [];
    $classChart = $dashboard['class_chart'] ?? [];
    $statusChart = $dashboard['status_chart'] ?? [];
    $recentTransactions = $dashboard['recent_transactions'] ?? [];
    $period = $dashboard['period']['label'] ?? now()->locale('id')->translatedFormat('F Y');

    $formatRupiah = static fn ($value) => 'Rp ' . number_format((int) $value, 0, ',', '.');
    $topType = $typeChart[0] ?? null;
    $averageBalance = (int) ($summary['account_count'] ?? 0) > 0
        ? (int) round(((int) ($summary['saldo_total'] ?? 0)) / max(1, (int) ($summary['account_count'] ?? 0)))
        : 0;
@endphp
<div id="view-bendahara-dashboard" class="view-section active animate-fade-in space-y-5 md:space-y-6">
    <section class="overflow-hidden rounded-[1.75rem] border border-amber-100 bg-white shadow-sm">
        <div class="border-b border-amber-100 bg-amber-50/70 px-5 py-5 md:px-7">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl">
                    <div class="inline-flex items-center gap-2 rounded-full border border-amber-200 bg-white px-3 py-1 text-[11px] font-bold uppercase tracking-[0.18em] text-amber-700">
                        <i class="fas fa-piggy-bank"></i>
                        Dashboard Bendahara
                    </div>
                    <h2 class="mt-3 text-2xl font-bold tracking-tight text-gray-900">Kontrol operasional tabungan siswa</h2>
                    <p class="mt-2 text-sm leading-relaxed text-gray-600">
                        Ringkasan saldo, rekening aktif, transaksi bulan berjalan, dan pintasan ke detail rekening yang sudah terfilter.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-600 shadow-sm">
                        <i class="far fa-calendar-alt text-amber-500"></i>
                        {{ ucfirst($period) }}
                    </span>
                    <a href="{{ route('tabungan-siswa.rekening.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-amber-600">
                        <i class="fas fa-wallet"></i>
                        Buka Rekening
                    </a>
                    <a href="{{ route('tabungan-siswa.jenis.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-bold text-gray-700 shadow-sm transition hover:border-amber-200 hover:text-amber-700">
                        <i class="fas fa-layer-group"></i>
                        Master Jenis
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3 p-5 sm:grid-cols-2 xl:grid-cols-4 md:p-7">
            <article class="rounded-2xl border border-amber-100 bg-amber-50/70 p-4">
                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-amber-600">Total Saldo</p>
                <p class="mt-3 text-2xl font-bold tracking-tight text-gray-900">{{ $formatRupiah($summary['saldo_total'] ?? 0) }}</p>
                <p class="mt-1 text-xs text-gray-500">Akumulasi seluruh saldo rekening tabungan siswa.</p>
            </article>

            <article class="rounded-2xl border border-emerald-100 bg-emerald-50/70 p-4">
                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-emerald-600">Rekening Aktif</p>
                <p class="mt-3 text-2xl font-bold tracking-tight text-gray-900">
                    {{ number_format((int) ($summary['active_count'] ?? 0), 0, ',', '.') }}
                    <span class="text-base font-semibold text-emerald-700">/ {{ number_format((int) ($summary['account_count'] ?? 0), 0, ',', '.') }}</span>
                </p>
                <p class="mt-1 text-xs text-gray-500">Klik chart status untuk melihat rekening aktif atau nonaktif.</p>
            </article>

            <article class="rounded-2xl border border-sky-100 bg-sky-50/70 p-4">
                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-sky-600">Siswa Tercakup</p>
                <p class="mt-3 text-2xl font-bold tracking-tight text-gray-900">{{ number_format((int) ($summary['student_count'] ?? 0), 0, ',', '.') }}</p>
                <p class="mt-1 text-xs text-gray-500">Jumlah siswa yang sudah memiliki rekening tabungan.</p>
            </article>

            <article class="rounded-2xl border border-indigo-100 bg-indigo-50/70 p-4">
                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-indigo-600">Transaksi Bulan Ini</p>
                <p class="mt-3 text-2xl font-bold tracking-tight text-gray-900">{{ number_format((int) ($summary['monthly_transaction_count'] ?? 0), 0, ',', '.') }}</p>
                <p class="mt-1 text-xs text-gray-500">Mutasi selama periode {{ strtolower($period) }}.</p>
            </article>
        </div>
    </section>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <section class="xl:col-span-2 rounded-3xl border border-gray-100 bg-white p-5 shadow-sm md:p-6 h-full flex flex-col">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h3 class="text-sm font-bold text-gray-800">Saldo per jenis tabungan</h3>
                    <p class="mt-1 text-xs text-gray-500">Klik batang chart untuk membuka daftar rekening sesuai jenis tabungan.</p>
                </div>
                <span class="inline-flex items-center gap-2 self-start rounded-full border border-amber-100 bg-amber-50 px-3 py-1 text-[11px] font-semibold text-amber-700">
                    <i class="fas fa-chart-bar"></i>
                    Detail terfilter
                </span>
            </div>

            <div class="mt-5">
                @if (count($typeChart) > 0)
                    <div class="relative flex-1 min-h-[260px] lg:min-h-[300px]">
                        <canvas id="bendaharaTypeChart"></canvas>
                    </div>
                @else
                    <div class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-4 py-12 text-center text-sm text-gray-500">
                        Belum ada rekening tabungan untuk ditampilkan di chart.
                    </div>
                @endif
            </div>
            <div class="mt-5 grid grid-cols-1 gap-3 border-t border-gray-100 pt-4 sm:grid-cols-3">
                <div class="rounded-2xl border border-amber-100 bg-amber-50/70 px-4 py-3">
                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-amber-600">Jenis Teratas</p>
                    <p class="mt-2 text-base font-bold text-gray-900">{{ $topType['label'] ?? 'Belum ada data' }}</p>
                    <p class="mt-1 text-[11px] leading-relaxed text-gray-600">
                        {{ $topType ? $formatRupiah($topType['value'] ?? 0) : 'Saldo jenis tabungan akan tampil di sini.' }}
                    </p>
                </div>
                <div class="rounded-2xl border border-indigo-100 bg-indigo-50/70 px-4 py-3">
                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-indigo-500">Rata-rata Rekening</p>
                    <p class="mt-2 text-base font-bold text-gray-900">{{ $formatRupiah($averageBalance) }}</p>
                    <p class="mt-1 text-[11px] leading-relaxed text-gray-600">Perbandingan saldo total terhadap seluruh rekening aktif dan nonaktif.</p>
                </div>
                <div class="rounded-2xl border border-emerald-100 bg-emerald-50/70 px-4 py-3">
                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-emerald-500">Jenis Tercatat</p>
                    <p class="mt-2 text-xl font-bold text-gray-900">{{ number_format(count($typeChart), 0, ',', '.') }}</p>
                    <p class="mt-1 text-[11px] leading-relaxed text-gray-600">Jumlah jenis tabungan yang saat ini sudah dipakai oleh rekening siswa.</p>
                </div>
            </div>
        </section>

        <div class="space-y-6">
            <section class="rounded-3xl border border-gray-100 bg-white p-5 shadow-sm md:p-6">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-sm font-bold text-gray-800">Mutasi {{ strtolower($period) }}</h3>
                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-gray-500">Bulanan</span>
                </div>

                <div class="mt-4 space-y-3">
                    <div class="rounded-2xl border border-emerald-100 bg-emerald-50/70 p-4">
                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-emerald-600">Setoran Masuk</p>
                        <p class="mt-2 text-xl font-bold text-gray-900">{{ $formatRupiah($monthly['setoran_total'] ?? 0) }}</p>
                    </div>
                    <div class="rounded-2xl border border-rose-100 bg-rose-50/70 p-4">
                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-rose-600">Penarikan Keluar</p>
                        <p class="mt-2 text-xl font-bold text-gray-900">{{ $formatRupiah($monthly['penarikan_total'] ?? 0) }}</p>
                    </div>
                    <div class="rounded-2xl border border-indigo-100 bg-indigo-50/70 p-4">
                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-indigo-600">Mutasi Bersih</p>
                        <p class="mt-2 text-xl font-bold text-gray-900">{{ $formatRupiah($monthly['mutasi_bersih'] ?? 0) }}</p>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-gray-100 bg-white p-5 shadow-sm md:p-6">
                <div class="flex flex-col gap-2">
                    <h3 class="text-sm font-bold text-gray-800">Status rekening</h3>
                    <p class="text-xs text-gray-500">Klik bagian chart untuk buka rekening aktif atau nonaktif.</p>
                </div>

                <div class="mt-4">
                    @if (collect($statusChart)->sum('value') > 0)
                        <div class="relative h-[260px]">
                            <canvas id="bendaharaStatusChart"></canvas>
                        </div>
                    @else
                        <div class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-4 py-10 text-center text-sm text-gray-500">
                            Status rekening belum tersedia.
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <section class="rounded-3xl border border-gray-100 bg-white p-5 shadow-sm md:p-6">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-sm font-bold text-gray-800">Kelas dengan saldo terbesar</h3>
                    <p class="mt-1 text-xs text-gray-500">Klik nama kelas untuk membuka rekening siswa di kelas tersebut.</p>
                </div>
                <span class="text-[11px] font-semibold text-gray-400">Top {{ count($classChart) }}</span>
            </div>

            <div class="mt-4 space-y-3">
                @forelse ($classChart as $row)
                    <a href="{{ route('tabungan-siswa.rekening.index', ['kelas' => $row['label']]) }}" class="flex items-center justify-between gap-3 rounded-2xl border border-gray-100 px-4 py-3 transition hover:border-amber-200 hover:bg-amber-50/40">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-gray-800">{{ $row['label'] }}</p>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ number_format((int) ($row['account_count'] ?? 0), 0, ',', '.') }} rekening
                                -
                                {{ number_format((int) ($row['student_count'] ?? 0), 0, ',', '.') }} siswa
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-amber-700">{{ $formatRupiah($row['value'] ?? 0) }}</p>
                            <p class="text-[11px] text-gray-400">Lihat detail</p>
                        </div>
                    </a>
                @empty
                    <div class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-4 py-10 text-center text-sm text-gray-500">
                        Belum ada data kelas untuk ditampilkan.
                    </div>
                @endforelse
            </div>
        </section>

        <section class="rounded-3xl border border-gray-100 bg-white p-5 shadow-sm md:p-6">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-sm font-bold text-gray-800">Transaksi terbaru</h3>
                    <p class="mt-1 text-xs text-gray-500">Pantau mutasi terakhir agar operasional bendahara tetap rapi.</p>
                </div>
                <a href="{{ route('tabungan-siswa.rekening.index') }}" class="text-xs font-bold text-amber-700 hover:text-amber-800">Lihat rekening</a>
            </div>

            <div class="mt-4 space-y-3">
                @forelse ($recentTransactions as $row)
                    @php
                        $signedNominal = (int) ($row['signed_nominal'] ?? 0);
                        $signedClass = $signedNominal >= 0 ? 'text-emerald-700' : 'text-rose-700';
                        $signedPrefix = $signedNominal >= 0 ? '+' : '-';
                    @endphp
                    <div class="rounded-2xl border border-gray-100 px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-gray-800">{{ $row['siswa_nama'] }}</p>
                                <p class="mt-1 text-xs text-gray-500">
                                    {{ $row['kelas'] }} - {{ $row['jenis_tabungan'] }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-[11px] font-bold uppercase tracking-wide text-gray-500">{{ $row['jenis_transaksi_label'] }}</p>
                                <p class="mt-1 text-sm font-bold {{ $signedClass }}">{{ $signedPrefix }}{{ $formatRupiah(abs($signedNominal)) }}</p>
                            </div>
                        </div>
                        <div class="mt-3 flex flex-col gap-1 text-[11px] text-gray-500 sm:flex-row sm:items-center sm:justify-between">
                            <span>Bukti {{ $row['nomor_bukti'] }}</span>
                            <span>{{ $row['transacted_at'] }}</span>
                            <span>Saldo akhir {{ $formatRupiah($row['saldo_sesudah'] ?? 0) }}</span>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-4 py-10 text-center text-sm text-gray-500">
                        Belum ada transaksi tabungan yang tercatat.
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
@include('partials.page-script', ['name' => 'dashboard-bendahara'])
@endpush
