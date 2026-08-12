@extends('layouts.page')

@section('title', 'Sinkronisasi EMIS')

@section('content')
    <div class="p-6">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Sinkronisasi Data Siswa (EMIS)</h2>
                <p class="text-gray-500 text-sm mt-1">Tarik data siswa terbaru dari API EMIS</p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6 p-6">
            <div class="mb-4">
                <p class="text-gray-700">Fitur ini akan mensinkronkan data siswa aktif dan alumni secara bertahap dari EMIS
                    Kemenag. Proses ini mungkin memakan waktu beberapa menit, harap <i><b>jangan menutup halaman ini</b></i> saat
                    sinkronisasi sedang berjalan.</p>

                <div class="mt-4 p-4 bg-blue-50 border border-blue-100 rounded-lg flex items-start">
                    <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
                    <div>
                        <h4 class="font-semibold text-blue-800">Status Sinkronisasi Terakhir</h4>
                        @if($lastLog)
                            <p class="text-sm text-blue-700 mt-1">Selesai pada:
                                <strong>{{ $lastLog->created_at->format('d M Y H:i') }}</strong></p>
                            <p class="text-sm text-blue-700">Total data disinkronkan: <strong>{{ $lastLog->total_synced }}
                                    siswa</strong></p>
                        @else
                            <p class="text-sm text-blue-700 mt-1">Belum pernah melakukan sinkronisasi.</p>
                        @endif
                    </div>
                </div>
            </div>

            <button id="btn-sync"
                class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow transition">
                <i class="fas fa-sync-alt mr-2"></i> Mulai Sinkronisasi
            </button>

            <div id="sync-progress-container" class="mt-6 hidden">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-medium text-gray-700" id="progress-text">Memproses 0 dari 0 Halaman (0 Siswa
                        tersinkronisasi)</span>
                    <span class="text-sm font-semibold text-indigo-600" id="progress-percentage">0%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3.5">
                    <div id="progress-bar" class="bg-indigo-600 h-3.5 rounded-full transition-all duration-300"
                        style="width: 0%"></div>
                </div>
                <p class="text-xs text-gray-500 mt-2" id="sync-status">Status: Menunggu...</p>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const btnSync = document.getElementById('btn-sync');
            const progressContainer = document.getElementById('sync-progress-container');
            const progressBar = document.getElementById('progress-bar');
            const progressText = document.getElementById('progress-text');
            const progressPercentage = document.getElementById('progress-percentage');
            const syncStatus = document.getElementById('sync-status');

            let totalSynced = 0;

            btnSync.addEventListener('click', function () {
                if (!confirm('Anda yakin ingin memulai sinkronisasi? Pastikan koneksi internet stabil.')) {
                    return;
                }

                btnSync.disabled = true;
                btnSync.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Sinkronisasi Berjalan...';
                progressContainer.classList.remove('hidden');
                totalSynced = 0;

                // Start from page 1
                syncPage(1);
            });

            function syncPage(page) {
                syncStatus.textContent = `Status: Mengambil data halaman ${page}...`;

                fetch(`{{ route('settings.emis-sync.student') }}?page=${page}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message || 'Terjadi kesalahan tidak terduga.');
                        }

                        totalSynced += data.synced;

                        const current = data.current_page;
                        const last = data.last_page;
                        const percentage = Math.round((current / last) * 100);

                        // Update UI
                        progressBar.style.width = `${percentage}%`;
                        progressPercentage.textContent = `${percentage}%`;
                        progressText.textContent = `Memproses ${current} dari ${last} Halaman (${totalSynced} Siswa tersinkronisasi)`;
                        syncStatus.textContent = `Status: ${data.message}`;

                        if (current < last) {
                            // Fetch next page automatically
                            syncPage(current + 1);
                        } else {
                            // Finished
                            finishSync(true);
                        }
                    })
                    .catch(error => {
                        syncStatus.textContent = `Error: ${error.message}`;
                        syncStatus.classList.replace('text-gray-500', 'text-red-500');
                        finishSync(false);
                        alert('Proses sinkronisasi terhenti karena terjadi error: ' + error.message);
                    });
            }

            function finishSync(isSuccess) {
                btnSync.disabled = false;
                btnSync.innerHTML = '<i class="fas fa-sync-alt mr-2"></i> Sinkronisasi Selesai';
                if (isSuccess) {
                    syncStatus.textContent = 'Status: Proses sinkronisasi selesai. Menyimpan log...';

                    // Save log
                    fetch(`{{ route('settings.emis-sync.log') }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            total_synced: totalSynced,
                            status: 'success'
                        })
                    }).then(() => {
                        syncStatus.textContent = 'Status: Sinkronisasi dan log berhasil disimpan.';
                        syncStatus.classList.replace('text-gray-500', 'text-green-600');
                        progressBar.classList.replace('bg-indigo-600', 'bg-green-500');
                        setTimeout(() => window.location.reload(), 2000); // reload to show log
                    });
                } else {
                    fetch(`{{ route('settings.emis-sync.log') }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            total_synced: totalSynced,
                            status: 'failed',
                            notes: 'Sync interrupted or failed'
                        })
                    });
                }
            }
        });
    </script>
@endpush