@extends('layouts.page')

@section('title', 'Rekapitulasi Bulanan')

@section('content')
<div id="view-rekap-bulanan" class="view-section active animate-fade-in">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        
        <div class="p-4 border-b border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4 bg-indigo-50/30">
            <div>
                <h3 class="font-bold text-gray-800">Rekapitulasi Bulanan</h3>
                <p class="text-xs text-gray-500">Matriks kehadiran siswa bulan berjalan</p>
            </div>

            <div class="flex flex-wrap items-center gap-2 justify-end w-full md:w-auto">
                <div class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-indigo-100 bg-indigo-50 text-indigo-700 text-xs font-bold w-full md:w-auto">
                    <i class="fas fa-calendar-day text-[11px]"></i>
                    <span>Bulan:</span>
                    <span id="rekap-bulan-berjalan-label" class="font-extrabold">-</span>
                </div>

                <select id="rekapKelas" class="bg-white border border-gray-200 text-gray-700 text-xs rounded-lg p-2 font-bold focus:ring-indigo-500 shadow-sm cursor-pointer w-full md:w-auto">
                    @if (!auth()->user()?->hasRole('wakel'))
                        <option value="">Semua Kelas</option>
                    @endif
                </select>

                <button onclick="exportRekapBulananExcel()" id="btnExportRekap" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-emerald-700 transition shadow-sm flex items-center gap-2">
                    <i class="fas fa-file-excel"></i> <span class="hidden sm:inline">Export</span>
                </button>
                <button onclick="exportRekapBulananPdf()" id="btnExportRekapPdf" class="bg-rose-600 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-rose-700 transition shadow-sm flex items-center gap-2">
                    <i class="fas fa-file-pdf"></i> <span class="hidden sm:inline">PDF</span>
                </button>
            </div>
        </div>

        <div class="overflow-x-auto relative">
            <table class="w-full text-left border-collapse text-xs">
                <thead class="bg-gray-100 text-gray-600 uppercase font-bold sticky top-0 z-10">
                    <tr id="thead-rekap-bulanan">
                        </tr>
                </thead>
                <tbody id="tbody-rekap-bulanan" class="divide-y divide-gray-100 bg-white">
                    <tr><td class="p-8 text-center text-gray-500" colspan="10">Silakan pilih filter untuk menampilkan data.</td></tr>
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-gray-100 bg-gray-50/30 flex flex-col md:flex-row md:items-center md:justify-between gap-3 text-xs text-gray-500">
            <div class="flex items-center gap-2">
                <span class="font-bold">Show</span>
                <select id="rekapBulananLimit" onchange="setRekapBulananLimit(this.value)" class="bg-white border border-gray-200 text-gray-700 text-xs rounded-lg p-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="all">Semua</option>
                </select>
            </div>
            <div class="flex items-center justify-between md:justify-end gap-3">
                <span id="info-rekap-bulanan">Menampilkan 0 data</span>
                <div class="flex gap-1">
                    <button id="btn-prev-rekap-bulanan" onclick="changeRekapBulananPage(-1)" class="px-3 py-1 bg-white border border-gray-200 rounded hover:bg-gray-100 disabled:opacity-50 transition">Prev</button>
                    <button id="btn-next-rekap-bulanan" onclick="changeRekapBulananPage(1)" class="px-3 py-1 bg-white border border-gray-200 rounded hover:bg-gray-100 disabled:opacity-50 transition">Next</button>
                </div>
            </div>
        </div>
        
        <div class="p-3 bg-gray-50 border-t border-gray-100 flex flex-wrap gap-4 text-[10px] font-bold text-gray-500 justify-center">
            <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-green-500"></span> H: Hadir</span>
            <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-sky-500"></span> M: Masuk</span>
            <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-yellow-400"></span> S: Sakit</span>
            <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-blue-400"></span> I: Izin</span>
            <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-red-500"></span> A: Alpha</span>
            <span class="flex items-center gap-1"><span class="w-2 h-2 rounded bg-red-100 border border-red-200"></span> Libur</span>
        </div>

        <div class="p-4 bg-indigo-50 border-t border-indigo-100 text-xs text-indigo-900">
            <div class="flex flex-col md:flex-row gap-6">
                <div class="flex-1">
                    <h4 class="font-bold mb-2 flex items-center gap-2 text-indigo-700">
                        <i class="fas fa-calculator"></i> Rumus Persentase
                    </h4>
                    <div class="bg-white p-3 rounded-lg border border-indigo-100 shadow-sm">
                        <code class="font-mono text-indigo-600 font-bold text-sm block text-center mb-1">
                            (Jumlah Hadir / Hari Efektif) x 100%
                        </code>
                        <p class="text-[10px] text-center text-gray-400">Contoh: (1 Hadir / 10 Hari Efektif) x 100 = 10%</p>
                    </div>
                </div>

                <div class="flex-[2]">
                    <h4 class="font-bold mb-2 flex items-center gap-2 text-indigo-700">
                        <i class="fas fa-info-circle"></i> Penjelasan Istilah
                    </h4>
                    <ul class="space-y-1.5 text-gray-600">
                        <li class="flex gap-2">
                            <span class="font-bold text-indigo-600 w-24 shrink-0">Hari Efektif :</span>
                            <span>Jumlah total hari dalam bulan berjalan yang <b>sudah berlalu</b> dikurangi Hari Minggu dan Hari Libur Nasional. Hari masa depan (besok dst) tidak dihitung.</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="font-bold text-indigo-600 w-24 shrink-0">Status Alpha :</span>
                            <span>Siswa dianggap Alpha jika hari tersebut adalah <b>Hari Efektif</b> (sekolah masuk) namun siswa tidak melakukan scan kehadiran.</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="font-bold text-indigo-600 w-24 shrink-0">Status Masuk :</span>
                            <span>Khusus mode <b>Masuk + Pulang</b>, siswa yang baru scan datang tetapi belum scan pulang akan ditandai <b>M</b> dan belum dihitung hadir penuh.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
@include('partials.page-script', ['name' => 'rekap-bulanan'])
<script>
    function exportRekapBulananPdf() {
        const btn = document.getElementById('btnExportRekapPdf');
        const originalContent = btn ? btn.innerHTML : '';
        const notifier = window.showAlert
            ? (type, message) => window.showAlert(type, message)
            : (_type, message) => console.error(message);

        const currentFilters = (typeof getCurrentRekapFilterSelection === 'function')
            ? getCurrentRekapFilterSelection()
            : {
                bulan: new Date().getMonth(),
                tahun: new Date().getFullYear(),
                kelas: String(document.getElementById('rekapKelas')?.value || '').trim(),
            };

        const endpoint = (window.APP_AJAX_ACTIONS || {}).generatePdf || '';
        if (!endpoint) {
            notifier('error', 'Endpoint export PDF belum tersedia.');
            return;
        }

        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Loading...';
        }

        fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                args: ['laporan_bulanan', {
                    bulan: parseInt(currentFilters.bulan, 10),
                    tahun: parseInt(currentFilters.tahun, 10),
                    kelas: String(currentFilters.kelas || '').trim(),
                }],
            }),
        })
            .then(async (response) => {
                const result = await response.json().catch(() => ({}));
                if (!response.ok || !result?.success || !result?.url) {
                    throw new Error(result?.message || 'Gagal menyiapkan PDF.');
                }
                const link = document.createElement('a');
                link.href = result.url;
                link.setAttribute('download', '');
                document.body.appendChild(link);
                link.click();
                link.remove();
            })
            .catch((error) => notifier('error', error.message || String(error)))
            .finally(() => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalContent;
                }
            });
    }
</script>
@endpush
