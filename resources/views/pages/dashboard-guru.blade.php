@extends('layouts.page')

@section('title', 'Dashboard Guru')

@section('content')
<div id="view-guru-dashboard" class="view-section active animate-fade-in">
                  
                  <div class="flex flex-col md:flex-row justify-between items-end mb-8 gap-4">
                      <div>
                           <h2 class="text-2xl font-bold text-gray-800 tracking-tight">Dashboard Guru</h2>
                           <p class="text-sm text-gray-500 mt-1">Ringkasan aktivitas siswa hari ini.</p>
                      </div>
                      <div class="flex items-center gap-3">
                          <span class="text-xs font-bold bg-indigo-50 text-indigo-700 px-3 py-1.5 rounded-lg border border-indigo-100">
                              <i class="far fa-calendar-alt mr-2"></i> <span id="guruDashboardDate">...</span>
                          </span>
                          <button onclick="refreshData('dashboard')" class="flex items-center space-x-2 text-xs font-bold text-gray-600 bg-white border border-gray-200 px-4 py-2 rounded-lg shadow-sm hover:bg-gray-50 transition">
                              <i class="fas fa-sync-alt"></i> <span>Refresh</span>
                          </button>
                      </div>
                  </div>

                  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
                      <div class="bg-white p-5 rounded-xl shadow-sm border border-indigo-100 flex flex-col justify-between relative overflow-hidden group">
                          <div class="absolute right-0 top-0 w-16 h-16 bg-indigo-50 rounded-bl-full -mr-2 -mt-2 transition-transform group-hover:scale-110"></div>
                          <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest relative z-10">Total Siswa</p>
                          <div class="flex items-center justify-between mt-2 relative z-10">
                              <h3 id="statGuruTotal" class="text-2xl font-bold text-gray-800">-</h3>
                              <div class="text-indigo-500 bg-indigo-50 p-2 rounded-lg"><i class="fas fa-user-graduate"></i></div>
                          </div>
                      </div>

                      <div class="bg-white p-5 rounded-xl shadow-sm border border-emerald-100 flex flex-col justify-between relative overflow-hidden group">
                          <div class="absolute right-0 top-0 w-16 h-16 bg-emerald-50 rounded-bl-full -mr-2 -mt-2 transition-transform group-hover:scale-110"></div>
                          <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest relative z-10">Hadir</p>
                          <div class="flex items-center justify-between mt-2 relative z-10">
                              <h3 id="statGuruHadir" class="text-2xl font-bold text-gray-800">-</h3>
                              <div class="text-emerald-500 bg-emerald-50 p-2 rounded-lg"><i class="fas fa-check"></i></div>
                          </div>
                      </div>

                      <div class="bg-white p-5 rounded-xl shadow-sm border border-yellow-100 flex flex-col justify-between relative overflow-hidden group">
                          <div class="absolute right-0 top-0 w-16 h-16 bg-yellow-50 rounded-bl-full -mr-2 -mt-2 transition-transform group-hover:scale-110"></div>
                          <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest relative z-10">Sakit</p>
                          <div class="flex items-center justify-between mt-2 relative z-10">
                              <h3 id="statGuruSakit" class="text-2xl font-bold text-gray-800">-</h3>
                              <div class="text-yellow-500 bg-yellow-50 p-2 rounded-lg"><i class="fas fa-procedures"></i></div>
                          </div>
                      </div>

                      <div class="bg-white p-5 rounded-xl shadow-sm border border-blue-100 flex flex-col justify-between relative overflow-hidden group">
                          <div class="absolute right-0 top-0 w-16 h-16 bg-blue-50 rounded-bl-full -mr-2 -mt-2 transition-transform group-hover:scale-110"></div>
                          <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest relative z-10">Izin</p>
                          <div class="flex items-center justify-between mt-2 relative z-10">
                              <h3 id="statGuruIzin" class="text-2xl font-bold text-gray-800">-</h3>
                              <div class="text-blue-500 bg-blue-50 p-2 rounded-lg"><i class="fas fa-paper-plane"></i></div>
                          </div>
                      </div>

                      <div class="bg-white p-5 rounded-xl shadow-sm border border-red-100 flex flex-col justify-between relative overflow-hidden group">
                          <div class="absolute right-0 top-0 w-16 h-16 bg-red-50 rounded-bl-full -mr-2 -mt-2 transition-transform group-hover:scale-110"></div>
                          <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest relative z-10">Alpa</p>
                          <div class="flex items-center justify-between mt-2 relative z-10">
                              <h3 id="statGuruAlpa" class="text-2xl font-bold text-gray-800">-</h3>
                              <div class="text-red-500 bg-red-50 p-2 rounded-lg"><i class="fas fa-times-circle"></i></div>
                          </div>
                      </div>
                  </div>

                  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                       <div class="lg:col-span-2 bg-white rounded-2xl p-6 border border-gray-100 shadow-sm h-full flex flex-col">
                            <div class="mb-6 flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-sm font-bold text-gray-700 flex items-center">
                                        <i class="fas fa-chart-area text-indigo-500 mr-2"></i> Statistik Kehadiran Hari Ini
                                    </h3>
                                    <p class="mt-1 text-[11px] text-gray-500">Klik titik chart untuk membuka monitoring sesuai status.</p>
                                </div>
                                <span class="text-[10px] bg-gray-100 text-gray-500 px-2 py-1 rounded">Realtime</span>
                            </div>
                            <div class="relative w-full flex-1 min-h-[240px] lg:min-h-[280px]">
                                <canvas id="guruAttendanceChart"></canvas>
                            </div>
                            <div class="mt-5 grid grid-cols-1 gap-3 border-t border-gray-100 pt-4 sm:grid-cols-3">
                                <div class="rounded-xl border border-indigo-100 bg-indigo-50/70 px-4 py-3">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-indigo-500">Sudah Tercatat</p>
                                    <p id="guruChartRecordedCount" class="mt-2 text-xl font-bold text-gray-900">0</p>
                                    <p id="guruChartRecordedHint" class="mt-1 text-[11px] leading-relaxed text-gray-600">Menunggu pembaruan data kehadiran.</p>
                                </div>
                                <div class="rounded-xl border border-emerald-100 bg-emerald-50/70 px-4 py-3">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-emerald-500">Status Dominan</p>
                                    <p id="guruChartDominantLabel" class="mt-2 text-base font-bold text-gray-900">Memuat...</p>
                                    <p id="guruChartDominantHint" class="mt-1 text-[11px] leading-relaxed text-gray-600">Sistem sedang merangkum status terbanyak.</p>
                                </div>
                                <div class="rounded-xl border border-amber-100 bg-amber-50/80 px-4 py-3">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-amber-600">Perlu Perhatian</p>
                                    <p id="guruChartAttentionCount" class="mt-2 text-xl font-bold text-gray-900">0</p>
                                    <p id="guruChartAttentionHint" class="mt-1 text-[11px] leading-relaxed text-gray-600">Menghitung siswa yang belum atau perlu ditindaklanjuti.</p>
                                </div>
                            </div>
                       </div>

                      <div class="flex flex-col gap-4">
                          <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
                              <div class="flex items-center justify-between gap-3 mb-4">
                                  <h3 class="font-bold text-gray-800 text-sm flex items-center">
                                      <i class="fas fa-sliders-h text-sky-500 mr-2"></i> Status Operasional
                                  </h3>
                                  <span id="guruAttendanceModePill" class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-[10px] font-bold uppercase tracking-wide text-sky-700">
                                      Memuat
                                  </span>
                              </div>

                              <div class="grid grid-cols-1 gap-3">
                                  <div class="rounded-xl border border-indigo-100 bg-indigo-50/80 p-4">
                                      <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-indigo-500">Mode Absensi Aktif</p>
                                      <p id="guruAttendanceModeLabel" class="mt-2 text-base font-bold text-gray-900">Memuat...</p>
                                      <p id="guruAttendanceModeHint" class="mt-1 text-xs leading-relaxed text-gray-600">Mengambil konfigurasi absensi terbaru.</p>
                                  </div>

                                  <div class="rounded-xl border border-sky-100 bg-sky-50/80 p-4">
                                      <div class="flex items-start justify-between gap-3">
                                          <div>
                                              <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-sky-500">Belum Pulang Hari Ini</p>
                                              <p id="guruPendingCheckoutHint" class="mt-2 text-xs leading-relaxed text-gray-600">Memeriksa siswa yang masih menunggu absensi pulang.</p>
                                          </div>
                                          <div class="text-right">
                                              <p id="guruPendingCheckoutCount" class="text-3xl font-bold tracking-tight text-sky-700">0</p>
                                              <p class="text-[10px] font-semibold uppercase tracking-wide text-sky-500">Siswa</p>
                                          </div>
                                      </div>
                                  </div>
                              </div>
                          </div>

                          <div class="bg-gradient-to-br from-indigo-600 to-blue-700 rounded-2xl p-6 text-white shadow-xl shadow-indigo-200 relative overflow-hidden flex flex-col justify-center items-center text-center">
                              <div class="absolute top-0 left-0 w-full h-full bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10"></div>
                              <div class="relative z-10">
                                  <div class="w-16 h-16 bg-white/20 backdrop-blur-md rounded-full flex items-center justify-center text-2xl mb-4 mx-auto border border-white/20">
                                      <i class="fas fa-qrcode"></i>
                                  </div>
                                  <h3 class="text-lg font-bold mb-2">Mulai Absensi</h3>
                                  <p class="text-indigo-100 text-xs mb-6 px-4">Buka pemindai kamera untuk melakukan absensi siswa secara cepat.</p>
                                  <a href="{{ route('scanner') }}" class="bg-white text-indigo-700 px-6 py-3 rounded-xl font-bold text-sm shadow-lg hover:bg-gray-50 transition transform active:scale-95 w-full text-center">
                                      Buka Scanner
                                  </a>
                              </div>
                          </div>
                      </div>
                  </div>
              </div>
@endsection

@push('scripts')
@include('partials.page-script', ['name' => 'dashboard-guru'])
@endpush
