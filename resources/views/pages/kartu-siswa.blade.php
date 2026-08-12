@extends('layouts.page')

@section('title', 'Kartu Identitas Murid')

@section('content')
<div id="view-kartu-siswa" class="view-section active animate-fade-in">
    <div id="kartuSiswaContainer"></div>
</div>
@endsection

@push('scripts')
@include('partials.page-script', ['name' => 'kartu-siswa'])
@endpush

@php
    $nisn = request()->query('nisn');
    $nama = request()->query('nama');
    $kelas = request()->query('kelas');
@endphp

@if($nisn)
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof loadQRCodeSiswa === 'function') {
                    loadQRCodeSiswa(@json($nisn), @json($nama), @json($kelas));
                }
            });
        </script>
    @endpush
@endif
