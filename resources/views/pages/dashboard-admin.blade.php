@extends('layouts.page')

@section('title', 'Dashboard Admin')

@section('content')
<div id="view-admin-dashboard" class="view-section active animate-fade-in">
                  
                  <div class="flex flex-col md:flex-row justify-between items-end mb-8 gap-4">
                      <div>
                           <h2 class="text-2xl font-bold text-gray-800 tracking-tight">Dashboard Admin</h2>
                           <p class="text-sm text-gray-500 mt-1">Pusat kontrol data absensiswa</p>
                      </div>
                      <div class="flex items-center gap-3">
                          <span class="text-xs font-bold bg-white text-gray-600 px-3 py-1.5 rounded-lg border border-gray-200 shadow-sm">
                              <i class="far fa-clock mr-2"></i> <span id="adminDateDisplay">...</span>
                          </span>
                          <button onclick="refreshData('dashboard')" class="flex items-center space-x-2 text-xs font-bold text-white bg-indigo-600 border border-indigo-600 px-4 py-2 rounded-lg shadow-md hover:bg-indigo-700 transition transform active:scale-95">
                              <i class="fas fa-sync-alt"></i> <span>Refresh Data</span>
                          </button>
                      </div>
                  </div>

                  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
                      
                      <div class="bg-white p-5 rounded-xl shadow-sm border border-indigo-100 flex flex-col justify-between relative overflow-hidden group">
                          <div class="absolute right-0 top-0 w-16 h-16 bg-indigo-50 rounded-bl-full -mr-2 -mt-2 transition-transform group-hover:scale-110"></div>
                          <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest relative z-10">Total Siswa</p>
                          <div class="flex items-center justify-between mt-2 relative z-10">
                              <h3 id="admStatTotal" class="text-2xl font-bold text-gray-800">-</h3>
                              <div class="text-indigo-500 bg-indigo-50 p-2 rounded-lg"><i class="fas fa-users"></i></div>
                          </div>
                      </div>

                      <div class="bg-white p-5 rounded-xl shadow-sm border border-emerald-100 flex flex-col justify-between relative overflow-hidden group">
                          <div class="absolute right-0 top-0 w-16 h-16 bg-emerald-50 rounded-bl-full -mr-2 -mt-2 transition-transform group-hover:scale-110"></div>
                          <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest relative z-10">Hadir</p>
                          <div class="flex items-center justify-between mt-2 relative z-10">
                              <h3 id="admStatHadir" class="text-2xl font-bold text-gray-800">-</h3>
                              <div class="text-emerald-500 bg-emerald-50 p-2 rounded-lg"><i class="fas fa-check"></i></div>
                          </div>
                      </div>

                      <div class="bg-white p-5 rounded-xl shadow-sm border border-yellow-100 flex flex-col justify-between relative overflow-hidden group">
                          <div class="absolute right-0 top-0 w-16 h-16 bg-yellow-50 rounded-bl-full -mr-2 -mt-2 transition-transform group-hover:scale-110"></div>
                          <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest relative z-10">Sakit</p>
                          <div class="flex items-center justify-between mt-2 relative z-10">
                              <h3 id="admStatSakit" class="text-2xl font-bold text-gray-800">-</h3>
                              <div class="text-yellow-500 bg-yellow-50 p-2 rounded-lg"><i class="fas fa-procedures"></i></div>
                          </div>
                      </div>

                      <div class="bg-white p-5 rounded-xl shadow-sm border border-blue-100 flex flex-col justify-between relative overflow-hidden group">
                          <div class="absolute right-0 top-0 w-16 h-16 bg-blue-50 rounded-bl-full -mr-2 -mt-2 transition-transform group-hover:scale-110"></div>
                          <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest relative z-10">Izin</p>
                          <div class="flex items-center justify-between mt-2 relative z-10">
                              <h3 id="admStatIzin" class="text-2xl font-bold text-gray-800">-</h3>
                              <div class="text-blue-500 bg-blue-50 p-2 rounded-lg"><i class="fas fa-paper-plane"></i></div>
                          </div>
                      </div>

                      <div class="bg-white p-5 rounded-xl shadow-sm border border-red-100 flex flex-col justify-between relative overflow-hidden group">
                          <div class="absolute right-0 top-0 w-16 h-16 bg-red-50 rounded-bl-full -mr-2 -mt-2 transition-transform group-hover:scale-110"></div>
                          <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest relative z-10">Alpa</p>
                          <div class="flex items-center justify-between mt-2 relative z-10">
                              <h3 id="admStatAlpa" class="text-2xl font-bold text-gray-800">-</h3>
                              <div class="text-red-500 bg-red-50 p-2 rounded-lg"><i class="fas fa-times"></i></div>
                          </div>
                      </div>
                  </div>

                  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                      
                       <div class="lg:col-span-2 bg-white rounded-2xl p-6 border border-gray-100 shadow-sm h-full flex flex-col">
                            <div class="flex justify-between items-center mb-6">
                                <div>
                                    <h3 class="text-sm font-bold text-gray-700 flex items-center">
                                        <i class="fas fa-chart-area text-indigo-500 mr-2"></i> Grafik Statistik Kehadiran
                                    </h3>
                                    <p class="mt-1 text-[11px] text-gray-500">Klik titik chart untuk membuka monitoring sesuai status.</p>
                                </div>
                            </div>
                            <div class="relative w-full flex-1 min-h-[260px] lg:min-h-[300px]">
                                 <canvas id="adminAttendanceChart"></canvas>
                            </div>
                            <div class="mt-5 grid grid-cols-1 gap-3 border-t border-gray-100 pt-4 sm:grid-cols-3">
                                <div class="rounded-xl border border-indigo-100 bg-indigo-50/70 px-4 py-3">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-indigo-500">Sudah Tercatat</p>
                                    <p id="adminChartRecordedCount" class="mt-2 text-xl font-bold text-gray-900">0</p>
                                    <p id="adminChartRecordedHint" class="mt-1 text-[11px] leading-relaxed text-gray-600">Menunggu pembaruan data kehadiran.</p>
                                </div>
                                <div class="rounded-xl border border-emerald-100 bg-emerald-50/70 px-4 py-3">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-emerald-500">Status Dominan</p>
                                    <p id="adminChartDominantLabel" class="mt-2 text-base font-bold text-gray-900">Memuat...</p>
                                    <p id="adminChartDominantHint" class="mt-1 text-[11px] leading-relaxed text-gray-600">Sistem sedang merangkum status terbanyak.</p>
                                </div>
                                <div class="rounded-xl border border-amber-100 bg-amber-50/80 px-4 py-3">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-amber-600">Perlu Perhatian</p>
                                    <p id="adminChartAttentionCount" class="mt-2 text-xl font-bold text-gray-900">0</p>
                                    <p id="adminChartAttentionHint" class="mt-1 text-[11px] leading-relaxed text-gray-600">Menghitung siswa yang belum atau perlu ditindaklanjuti.</p>
                                </div>
                            </div>
                       </div>

                      <div class="flex flex-col gap-4">
                        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
                            <div class="flex items-center justify-between gap-3 mb-4">
                                <h3 class="font-bold text-gray-800 text-sm flex items-center">
                                    <i class="fas fa-sliders-h text-sky-500 mr-2"></i> Status Operasional
                                </h3>
                                <span id="adminAttendanceModePill" class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-[10px] font-bold uppercase tracking-wide text-sky-700">
                                    Memuat
                                </span>
                            </div>

                            <div class="grid grid-cols-1 gap-3">
                                <div class="rounded-xl border border-indigo-100 bg-indigo-50/80 p-4">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-indigo-500">Mode Absensi Aktif</p>
                                    <p id="adminAttendanceModeLabel" class="mt-2 text-base font-bold text-gray-900">Memuat...</p>
                                    <p id="adminAttendanceModeHint" class="mt-1 text-xs leading-relaxed text-gray-600">Mengambil konfigurasi absensi terbaru.</p>
                                </div>

                                <div class="rounded-xl border border-sky-100 bg-sky-50/80 p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-sky-500">Belum Pulang Hari Ini</p>
                                            <p id="adminPendingCheckoutHint" class="mt-2 text-xs leading-relaxed text-gray-600">Memeriksa siswa yang masih menunggu absensi pulang.</p>
                                        </div>
                                        <div class="text-right">
                                            <p id="adminPendingCheckoutCount" class="text-3xl font-bold tracking-tight text-sky-700">0</p>
                                            <p class="text-[10px] font-semibold uppercase tracking-wide text-sky-500">Siswa</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="adminQuickAccess" class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm h-full">
                            <h3 class="font-bold text-gray-800 mb-4 flex items-center text-sm">
                                <i class="fas fa-bolt text-amber-500 mr-2"></i> Akses Cepat
                            </h3>
                              <div class="space-y-3">
                                  <a href="{{ route('scanner') }}" class="w-full flex items-center p-3 rounded-xl border border-gray-100 hover:bg-indigo-50 hover:border-indigo-200 transition-all group text-left">
                                      <div class="w-10 h-10 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center mr-3 group-hover:scale-110 transition"><i class="fas fa-qrcode"></i></div>
                                      <div>
                                          <div class="font-bold text-xs text-gray-700">Scan Absensi</div>
                                          <div class="text-[10px] text-gray-400">Mode scanner kamera</div>
                                      </div>
                                  </a>
                                  
                                  <a href="{{ route('data-siswa') }}" class="w-full flex items-center p-3 rounded-xl border border-gray-100 hover:bg-blue-50 hover:border-blue-200 transition-all group text-left">
                                      <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center mr-3 group-hover:scale-110 transition"><i class="fas fa-user-graduate"></i></div>
                                      <div>
                                          <div class="font-bold text-xs text-gray-700">Data Siswa</div>
                                          <div class="text-[10px] text-gray-400">Kelola database siswa</div>
                                      </div>
                                  </a>

                                  <a href="{{ route('rekap-absensi') }}" class="w-full flex items-center p-3 rounded-xl border border-gray-100 hover:bg-emerald-50 hover:border-emerald-200 transition-all group text-left">
                                      <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center mr-3 group-hover:scale-110 transition"><i class="fas fa-file-alt"></i></div>
                                      <div>
                                          <div class="font-bold text-xs text-gray-700">Laporan Absensi</div>
                                          <div class="text-[10px] text-gray-400">Export & rekap data</div>
                                      </div>
                                  </a>
                                  
                                  <a href="{{ route('kelola-absen') }}" class="w-full flex items-center p-3 rounded-xl border border-gray-100 hover:bg-rose-50 hover:border-rose-200 transition-all group text-left">
                                      <div class="w-10 h-10 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center mr-3 group-hover:scale-110 transition"><i class="fas fa-calendar-times"></i></div>
                                      <div>
                                          <div class="font-bold text-xs text-gray-700">Hari Libur</div>
                                          <div class="text-[10px] text-gray-400">Setting tanggal merah</div>
                                      </div>
                                  </a>
                              </div>
                          </div>
                      </div>

                  </div>
              </div>
@endsection

@push('scripts')
@include('partials.page-script', ['name' => 'dashboard-admin'])
@endpush
