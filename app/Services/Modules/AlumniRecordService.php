<?php

namespace App\Services\Modules;

use App\Models\Alumni;
use App\Models\Siswa;
use Illuminate\Support\Facades\DB;

class AlumniRecordService extends BaseActionService
{
    public function getAlumniList(array $args, $auth): array
    {
        $role = $this->getRoleFromAuth($auth);
        if (!$auth || !in_array($role, ['super-admin', 'admin', 'kepsek', 'wakasek', 'wakel'], true)) {
            return ['success' => false, 'message' => 'Akses Ditolak: Anda tidak memiliki izin.'];
        }

        $payload = $this->extractPaginatedPayload($args);
        $isPaginatedRequest = $payload !== null;
        $filterKelas = $this->normalizeKelasValue($isPaginatedRequest ? ($payload['kelas'] ?? null) : ($args[0] ?? null));
        $filterYear = $this->normalizeYearFilter($isPaginatedRequest ? ($payload['tahun_lulus'] ?? null) : ($args[1] ?? null));

        $query = Alumni::query();
        $classOptionsQuery = Alumni::query();
        $yearOptionsQuery = Alumni::query();

        if ($role === 'wakel') {
            $wakelKelas = $this->getWakelKelasFromAuth($auth);
            if ($wakelKelas === null) {
                return ['success' => false, 'message' => 'Akun wali kelas belum ditautkan ke kelas.'];
            }

            $query->where('kelas_terakhir', $wakelKelas);
            $classOptionsQuery->where('kelas_terakhir', $wakelKelas);
            $yearOptionsQuery->where('kelas_terakhir', $wakelKelas);
        } elseif ($filterKelas) {
            $query->where('kelas_terakhir', $filterKelas);
        }

        if ($filterYear !== null) {
            $query->where('tahun_lulus', $filterYear);
        }

        $classOptions = [];
        $yearOptions = [];
        if ($isPaginatedRequest) {
            $classOptions = (clone $classOptionsQuery)
                ->whereNotNull('kelas_terakhir')
                ->where('kelas_terakhir', '!=', '')
                ->distinct()
                ->orderBy('kelas_terakhir')
                ->pluck('kelas_terakhir')
                ->values()
                ->all();

            $yearOptions = (clone $yearOptionsQuery)
                ->whereNotNull('tahun_lulus')
                ->distinct()
                ->orderByDesc('tahun_lulus')
                ->pluck('tahun_lulus')
                ->map(static fn ($year) => (int) $year)
                ->values()
                ->all();
        }

        if ($isPaginatedRequest) {
            $search = $this->normalizeSearchTerm($payload['search'] ?? null);
            if ($search !== '') {
                $query->where(function ($builder) use ($search): void {
                    $builder
                        ->where('nama', 'like', "%{$search}%")
                        ->orWhere('nisn', 'like', "%{$search}%")
                        ->orWhere('kelas_terakhir', 'like', "%{$search}%")
                        ->orWhere('kontak', 'like', "%{$search}%")
                        ->orWhere('agama', 'like', "%{$search}%");
                });
            }
        }

        $selectColumns = [
            'id',
            'nama',
            'nisn',
            'jenis_kelamin',
            'tanggal_lahir',
            'agama',
            'nama_ayah',
            'nama_ibu',
            'kelas_terakhir',
            'tahun_lulus',
            'kontak',
            'alamat',
            'created_at',
            'updated_at',
        ];

        if (!$isPaginatedRequest) {
            $rows = $query
                ->orderByDesc('tahun_lulus')
                ->orderBy('kelas_terakhir')
                ->orderBy('nama')
                ->get($selectColumns);

            return ['success' => true, 'data' => $this->mapAlumniRows($rows)];
        }

        $fetchAll = !empty($payload['fetch_all']);
        $perPage = $this->resolveRequestedPerPage($payload['per_page'] ?? 10);
        $page = $this->resolveRequestedPage($payload['page'] ?? 1);

        if ($fetchAll || $perPage === 'all') {
            $rows = $query
                ->orderByDesc('tahun_lulus')
                ->orderBy('kelas_terakhir')
                ->orderBy('nama')
                ->get($selectColumns);

            $data = $this->mapAlumniRows($rows);
            $meta = $this->buildPaginationMeta(count($data), 1, max(count($data), 1));
            $meta['classes'] = $classOptions;
            $meta['years'] = $yearOptions;

            return [
                'success' => true,
                'data' => $data,
                'meta' => $meta,
            ];
        }

        $total = (clone $query)->count();
        $meta = $this->buildPaginationMeta($total, $page, $perPage);
        $rows = $query
            ->orderByDesc('tahun_lulus')
            ->orderBy('kelas_terakhir')
            ->orderBy('nama')
            ->forPage($meta['page'], $meta['per_page'])
            ->get($selectColumns);
        $meta['classes'] = $classOptions;
        $meta['years'] = $yearOptions;

        return [
            'success' => true,
            'data' => $this->mapAlumniRows($rows),
            'meta' => $meta,
        ];
    }

    private function mapAlumniRows($rows): array
    {
        return $rows->map(function (Alumni $alumni): array {
            return [
                'id' => (int) $alumni->id,
                'nama' => $alumni->nama,
                'nisn' => $alumni->nisn,
                'jenisKelamin' => $alumni->jenis_kelamin,
                'tanggalLahir' => optional($alumni->tanggal_lahir)->format('Y-m-d'),
                'agama' => $alumni->agama,
                'namaAyah' => $alumni->nama_ayah,
                'namaIbu' => $alumni->nama_ibu,
                'kelasTerakhir' => $alumni->kelas_terakhir,
                'tahunLulus' => $alumni->tahun_lulus !== null ? (int) $alumni->tahun_lulus : null,
                'kontak' => $alumni->kontak,
                'alamat' => $alumni->alamat,
                'createdAt' => optional($alumni->created_at)->toDateTimeString(),
                'updatedAt' => optional($alumni->updated_at)->toDateTimeString(),
            ];
        })->values()->all();
    }

    private function normalizeYearFilter($value): ?int
    {
        $year = filter_var($value, FILTER_VALIDATE_INT);
        if ($year === false || $year < 1900 || $year > 9999) {
            return null;
        }

        return $year;
    }

    public function deleteAlumni(array $args, $auth): array
    {
        $role = $this->getRoleFromAuth($auth);
        if (!$auth || !in_array($role, ['super-admin', 'admin', 'kepsek', 'wakel'], true)) {
            return ['success' => false, 'message' => 'Akses Ditolak: Anda tidak memiliki izin.'];
        }

        $payload = $args[0] ?? null;
        $alumniId = is_array($payload)
            ? (int) ($payload['id'] ?? 0)
            : (int) $payload;

        if ($alumniId <= 0) {
            return ['success' => false, 'message' => 'ID alumni tidak valid.'];
        }

        $alumni = $this->findManageableAlumni($alumniId, $auth);
        if (!$alumni) {
            return ['success' => false, 'message' => 'Data alumni tidak ditemukan.'];
        }

        $alumni->delete();

        return ['success' => true, 'message' => 'Data alumni berhasil dihapus.'];
    }

    public function restoreToSiswa(array $args, $auth): array
    {
        $role = $this->getRoleFromAuth($auth);
        if (!$auth || !in_array($role, ['super-admin', 'admin', 'kepsek', 'wakel'], true)) {
            return ['success' => false, 'message' => 'Akses Ditolak: Anda tidak memiliki izin.'];
        }

        $payload = $args[0] ?? [];
        if (!is_array($payload)) {
            $payload = ['id' => $payload];
        }

        $alumniId = (int) ($payload['id'] ?? 0);
        if ($alumniId <= 0) {
            return ['success' => false, 'message' => 'ID alumni tidak valid.'];
        }

        $alumni = $this->findManageableAlumni($alumniId, $auth);
        if (!$alumni) {
            return ['success' => false, 'message' => 'Data alumni tidak ditemukan.'];
        }

        $nisn = trim((string) $alumni->nisn);
        if ($nisn === '') {
            return ['success' => false, 'message' => 'NISN alumni kosong. Data tidak bisa direstore ke siswa.'];
        }

        if (Siswa::query()->where('nisn', $nisn)->exists()) {
            return ['success' => false, 'message' => 'NISN sudah ada di data siswa. Restore dibatalkan.'];
        }

        $targetKelas = $this->normalizeKelasValue($payload['kelas'] ?? $alumni->kelas_terakhir);
        if ($targetKelas === null) {
            return ['success' => false, 'message' => 'Kelas siswa tujuan wajib diisi sebelum restore.'];
        }

        if ($role === 'wakel') {
            $wakelKelas = $this->getWakelKelasFromAuth($auth);
            if ($wakelKelas === null) {
                return ['success' => false, 'message' => 'Akun wali kelas belum ditautkan ke kelas.'];
            }

            if ($targetKelas !== $wakelKelas) {
                return ['success' => false, 'message' => 'Wali kelas hanya boleh merestore alumni ke kelasnya sendiri.'];
            }
        }

        $targetKelas = $this->syncKelasValue($targetKelas);
        if ($targetKelas === null) {
            return ['success' => false, 'message' => 'Kelas siswa tujuan tidak valid.'];
        }

        $restoredSiswa = null;

        DB::transaction(function () use ($alumni, $nisn, $targetKelas, &$restoredSiswa): void {
            $restoredSiswa = Siswa::query()->create($this->buildSiswaPayloadFromAlumni($alumni, $targetKelas));
            $this->syncSiswaUserFromSiswa($nisn, $restoredSiswa);
            $alumni->delete();
        });

        return [
            'success' => true,
            'message' => 'Data alumni berhasil direstore ke data siswa.',
            'data' => [
                'nisn' => $restoredSiswa?->nisn,
                'kelas' => $restoredSiswa?->kelas,
            ],
        ];
    }

    private function buildSiswaPayloadFromAlumni(Alumni $alumni, string $kelas): array
    {
        return [
            'nama' => trim((string) $alumni->nama),
            'nisn' => trim((string) $alumni->nisn),
            'jenis_kelamin' => $this->normalizeOptionalString($alumni->jenis_kelamin),
            'tanggal_lahir' => $this->normalizeDateValue($alumni->tanggal_lahir),
            'agama' => $this->normalizeOptionalString($alumni->agama),
            'nama_ayah' => $this->normalizeOptionalString($alumni->nama_ayah),
            'nama_ibu' => $this->normalizeOptionalString($alumni->nama_ibu),
            'no_hp' => $this->normalizeOptionalString($alumni->kontak),
            'kelas' => $kelas,
            'alamat' => $this->normalizeOptionalString($alumni->alamat),
        ];
    }

    private function findManageableAlumni(int $alumniId, $auth): ?Alumni
    {
        $query = Alumni::query()->whereKey($alumniId);
        if ($this->getRoleFromAuth($auth) === 'wakel') {
            $wakelKelas = $this->getWakelKelasFromAuth($auth);
            if ($wakelKelas === null) {
                return null;
            }

            $query->where('kelas_terakhir', $wakelKelas);
        }

        return $query->first();
    }
}
