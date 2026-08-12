@extends('layouts.page')

@section('title', 'Pelanggaran Saya')

@section('content')
<div id="view-pelanggaran-saya" class="view-section active animate-fade-in space-y-4">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-4 md:p-5 border-b border-gray-100 bg-gray-50/30">
            <h3 class="font-bold text-sm text-gray-800">Pelanggaran Saya</h3>
            <p class="text-xs text-gray-500 mt-1">Halaman ini menampilkan riwayat poin pelanggaran pribadi Anda.</p>
        </div>

        <div class="p-4 md:p-5">
            @if (!$siswa)
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-amber-800 text-sm">
                    Akun siswa belum tertaut ke data siswa (berdasarkan NISN/username). Hubungi admin untuk sinkronisasi data.
                </div>
            @else
                <div class="grid grid-cols-2 xl:grid-cols-4 gap-3 mb-4">
                    <div class="rounded-xl border border-rose-100 bg-rose-50/70 px-4 py-3">
                        <div class="text-[11px] uppercase tracking-wide text-rose-600 font-bold">Total Poin</div>
                        <div class="text-sm font-semibold text-rose-900">{{ $stats['total_poin'] }}</div>
                        <div class="text-xs text-rose-700">Akumulasi seluruh catatan</div>
                    </div>
                    <div class="rounded-xl border border-indigo-100 bg-indigo-50/70 px-4 py-3">
                        <div class="text-[11px] uppercase tracking-wide text-indigo-600 font-bold">Total Pelanggaran</div>
                        <div class="text-sm font-semibold text-indigo-900">{{ $stats['total_pelanggaran'] }}</div>
                        <div class="text-xs text-indigo-700">Jumlah catatan pelanggaran</div>
                    </div>
                    <div class="rounded-xl border border-amber-100 bg-amber-50/70 px-4 py-3">
                        <div class="text-[11px] uppercase tracking-wide text-amber-600 font-bold">Bulan Ini</div>
                        <div class="text-sm font-semibold text-amber-900">{{ $stats['bulan_ini_poin'] }} poin</div>
                        <div class="text-xs text-amber-700">{{ $stats['bulan_ini_pelanggaran'] }} catatan pada {{ $periodeLabel }}</div>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 px-4 py-3">
                        <div class="text-[11px] uppercase tracking-wide text-slate-600 font-bold">Terakhir Dicatat</div>
                        <div class="text-sm font-semibold text-slate-900">{{ $stats['terakhir_label'] }}</div>
                        <div class="text-xs text-slate-700">{{ $siswa->nama }} • {{ $siswa->kelas ?? '-' }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
                    <section class="xl:col-span-1 rounded-2xl border border-gray-200 bg-white p-4">
                        <h4 class="text-sm font-bold text-gray-800 mb-3">Akumulasi per Kategori</h4>
                        @if (($kategoriSummary ?? collect())->isEmpty())
                            <div class="rounded-lg border border-dashed border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-500">
                                Belum ada data kategori pelanggaran.
                            </div>
                        @else
                            <div class="w-full max-w-[280px] mx-auto">
                                <canvas id="pelanggaranKategoriChart" height="240"></canvas>
                            </div>
                            <div class="mt-4 space-y-2">
                                @foreach ($kategoriSummary as $row)
                                    <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2">
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="text-xs font-semibold text-gray-800">{{ $row['kategori'] }}</div>
                                            <div class="text-[11px] font-bold text-rose-600">{{ $row['total_poin'] }} poin</div>
                                        </div>
                                        <div class="text-[11px] text-gray-500 mt-1">{{ $row['total_pelanggaran'] }} catatan</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </section>

                    <section class="xl:col-span-2 rounded-2xl border border-gray-200 bg-white overflow-hidden flex flex-col">
                        <div class="px-4 py-3 border-b border-gray-100 bg-gray-50/40">
                            <h4 class="text-sm font-bold text-gray-800">Riwayat Pelanggaran</h4>
                        </div>
                        <div class="p-4 space-y-3 overflow-y-auto overscroll-contain xl:max-h-[62vh]">
                            @if (($riwayat ?? collect())->isEmpty())
                                <div class="rounded-lg border border-dashed border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-500">
                                    Belum ada riwayat pelanggaran.
                                </div>
                            @else
                                @foreach ($riwayat as $row)
                                    <article class="rounded-xl border border-gray-200 bg-gray-50/40 p-3">
                                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                            <div>
                                                <div class="text-sm font-semibold text-gray-800">{{ $row['nama_pelanggaran'] ?? '-' }}</div>
                                                <div class="text-xs text-gray-500 mt-1">
                                                    {{ $row['tanggal_label'] ?? '-' }} • {{ $row['kategori'] !== '' ? $row['kategori'] : 'Tanpa Kategori' }}
                                                </div>
                                                @if (!empty($row['catatan']))
                                                    <div class="text-xs text-gray-500 mt-1">Catatan: {{ $row['catatan'] }}</div>
                                                @endif
                                                <div class="text-xs text-gray-500 mt-1">Input oleh: {{ $row['input_by'] ?? '-' }}</div>
                                            </div>
                                            <span class="inline-flex self-start items-center px-2 py-1 rounded-full text-[11px] font-bold bg-rose-100 text-rose-700">
                                                {{ (int) ($row['poin'] ?? 0) }} poin
                                            </span>
                                        </div>
                                    </article>
                                @endforeach
                            @endif
                        </div>
                    </section>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const canvas = document.getElementById('pelanggaranKategoriChart');
        if (!canvas || typeof Chart === 'undefined') {
            return;
        }

        const labels = @json(collect($kategoriSummary ?? collect())->pluck('kategori')->values());
        const values = @json(collect($kategoriSummary ?? collect())->pluck('total_poin')->values());
        if (!Array.isArray(labels) || !Array.isArray(values) || labels.length === 0) {
            return;
        }

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Poin',
                    data: values,
                    backgroundColor: ['#fb7185', '#f59e0b', '#6366f1', '#0ea5e9', '#10b981', '#8b5cf6'],
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            font: {
                                size: 10,
                            }
                        },
                        grid: {
                            display: false,
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            font: {
                                size: 10,
                            }
                        }
                    }
                }
            }
        });
    })();
</script>
@endpush
