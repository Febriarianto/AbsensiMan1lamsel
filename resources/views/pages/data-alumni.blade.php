@extends('layouts.page')

@section('title', 'Direktori Alumni')

@section('content')
<div id="view-data-alumni" class="view-section active animate-fade-in">
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="p-4 border-b border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4 bg-gray-50/30">
        <div>
            <h3 class="font-bold text-sm text-gray-800">Direktori Alumni</h3>
            <p class="text-xs text-gray-500">Lihat arsip lulusan beserta biodata lengkapnya.</p>
        </div>

        <div class="flex items-center gap-2 flex-wrap justify-end">
            <button type="button" id="refreshAlumniBtn" class="bg-white text-gray-600 border border-gray-200 px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm hover:bg-gray-50 hover:text-indigo-600 transition" title="Perbarui Data">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
      </div>

      <div class="p-4 bg-white border-b border-gray-100 flex flex-col xl:flex-row justify-between items-center gap-4">
          <div class="flex flex-col sm:flex-row items-center gap-3 text-xs w-full xl:w-auto">
              <div class="flex items-center gap-2 w-full sm:w-auto">
                  <span class="text-gray-500 font-bold whitespace-nowrap">Show</span>
                  <select id="alumniPerPage" class="bg-gray-50 border border-gray-200 text-gray-700 text-xs rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2 w-full sm:w-auto cursor-pointer">
                      <option value="10">10</option>
                      <option value="25">25</option>
                      <option value="50">50</option>
                      <option value="100">100</option>
                      <option value="all">Semua</option>
                  </select>
              </div>

              <select id="filterKelasAlumni" class="bg-gray-50 border border-gray-200 text-gray-700 text-xs rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2 w-full sm:w-44 font-bold shadow-sm cursor-pointer">
                    <option value="">Semua Kelas Terakhir</option>
              </select>

              <select id="filterTahunAlumni" class="bg-gray-50 border border-gray-200 text-gray-700 text-xs rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2 w-full sm:w-36 font-bold shadow-sm cursor-pointer">
                    <option value="">Semua Tahun</option>
              </select>
          </div>

          <div class="relative w-full xl:w-72">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <i class="fas fa-search text-gray-400 text-xs"></i>
              </div>
              <input type="text" id="searchAlumniInput" class="bg-gray-50 border border-gray-200 text-gray-900 text-xs rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-10 p-2 transition-all" placeholder="Cari nama / NISN / kontak...">
          </div>
      </div>

      <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
              <thead class="bg-gray-50 text-gray-500 text-[10px] uppercase font-semibold border-b border-gray-200">
                  <tr>
                      <th class="p-3 text-center w-12">No</th>
                      <th class="p-3">Nama Lengkap</th>
                      <th class="p-3 hidden md:table-cell">NISN</th>
                      <th class="p-3 hidden sm:table-cell">Kelas Terakhir</th>
                      <th class="p-3 hidden lg:table-cell">Tahun Lulus</th>
                      <th class="p-3 hidden xl:table-cell">Kontak</th>
                      <th class="p-3 text-center w-28">Aksi</th>
                  </tr>
              </thead>
              <tbody id="tbody-alumni" class="divide-y divide-gray-50 bg-white text-xs text-gray-700"></tbody>
          </table>
      </div>

      <div id="footer-alumni" class="p-4 border-t border-gray-100 bg-gray-50/30 flex justify-between items-center text-xs text-gray-500">
          <span id="info-alumni">Menampilkan 0 data</span>
          <div class="flex gap-1">
              <button type="button" id="btn-prev-alumni" class="px-3 py-1 bg-white border border-gray-200 rounded hover:bg-gray-100 disabled:opacity-50 transition shadow-sm">Prev</button>
              <button type="button" id="btn-next-alumni" class="px-3 py-1 bg-white border border-gray-200 rounded hover:bg-gray-100 disabled:opacity-50 transition shadow-sm">Next</button>
          </div>
      </div>
  </div>
</div>
@endsection

@push('scripts')
@include('partials.page-script', ['name' => 'data-alumni'])
@endpush
