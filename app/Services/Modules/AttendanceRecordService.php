<?php

namespace App\Services\Modules;


use App\Jobs\SendWaAttendanceNotificationJob;
use App\Models\Absensi;
use App\Models\AbsensiPelajaran;
use App\Models\AuthToken;
use App\Models\HariLibur;
use App\Models\IzinSakitRequest;
use App\Models\JadwalHarian;
use App\Models\JadwalPelajaran;
use App\Models\KartuAbsensi;
use App\Models\Kelas;
use App\Models\Konfigurasi;
use App\Models\SesiPelajaran;
use App\Models\Siswa;
use App\Models\User;
use App\Services\AttendanceCardService;
use App\Services\StudentAttendanceService;
use App\Services\WaGatewayService;
use App\Support\AttendanceMode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Role;

class AttendanceRecordService extends BaseActionService
{
    public function getMonitoringRealtime(array $args, $auth): array
    {
        $role = $this->getRoleFromAuth($auth);
        if (!$auth || !in_array($role, ['admin', 'kepsek', 'wakel', 'wakasek', 'piket', 'siswa'], true)) {
            return ['success' => false, 'message' => 'Akses Ditolak: Anda tidak memiliki izin.'];
        }

        $attendanceMode = $this->getAttendanceMode();

        $payload = $this->extractPaginatedPayload($args);
        $isPaginatedRequest = $payload !== null;
        $filterKelas = $this->normalizeKelasValue($isPaginatedRequest ? ($payload['kelas'] ?? null) : ($args[0] ?? null));
        $hasFixedRoleKelas = false;
        if ($role === 'wakel') {
            $wakelKelas = $this->getWakelKelasFromAuth($auth);
            if ($wakelKelas === null) {
                return ['success' => false, 'message' => 'Akun wali kelas belum ditautkan ke kelas.'];
            }

            $filterKelas = $wakelKelas;
            $hasFixedRoleKelas = true;
        } elseif ($role === 'piket') {
            $piketKelas = $this->getPiketKelasFromAuth($auth);
            // Jika kelas piket kosong/null, monitoring boleh semua kelas.
            if ($piketKelas !== null) {
                $filterKelas = $piketKelas;
                $hasFixedRoleKelas = true;
            }
        } elseif ($role === 'siswa') {
            $siswaKelas = $this->getSiswaKelasFromAuth($auth);
            if ($siswaKelas === null) {
                return ['success' => false, 'message' => 'Data kelas siswa tidak ditemukan.'];
            }

            $filterKelas = $siswaKelas;
            $hasFixedRoleKelas = true;
        }

        $today = Carbon::today()->toDateString();
        $classOptionsQuery = Siswa::query();
        if ($hasFixedRoleKelas && $filterKelas) {
            $classOptionsQuery->where('kelas', $filterKelas);
        }

        $siswaQuery = Siswa::query();
        if ($filterKelas) {
            $siswaQuery->where('kelas', $filterKelas);
        }

        if (!$isPaginatedRequest) {
            $siswaList = $siswaQuery
                ->orderBy('kelas')
                ->orderBy('nama')
                ->get(['id', 'nama', 'nisn', 'kelas']);

            return [
                'success' => true,
                'data' => $this->buildMonitoringRealtimeRows($siswaList, $today),
            ];
        }

        $classOptions = (clone $classOptionsQuery)
            ->whereNotNull('kelas')
            ->where('kelas', '!=', '')
            ->distinct()
            ->orderBy('kelas')
            ->pluck('kelas')
            ->values()
            ->all();

        $search = $this->normalizeSearchTerm($payload['search'] ?? null);
        if ($search !== '') {
            $siswaQuery->where(function ($builder) use ($search): void {
                $builder
                    ->where('nama', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%")
                    ->orWhere('kelas', 'like', "%{$search}%");
            });
        }

        $statusFilter = $this->normalizeMonitoringStatusFilterValue($payload['status'] ?? null);
        $fetchAll = !empty($payload['fetch_all']);
        $perPage = $this->resolveRequestedPerPage($payload['per_page'] ?? 10);
        $page = $this->resolveRequestedPage($payload['page'] ?? 1);

        if ($statusFilter !== null || $fetchAll || $perPage === 'all') {
            $rows = $this->buildMonitoringRealtimeRows(
                $siswaQuery
                    ->orderBy('kelas')
                    ->orderBy('nama')
                    ->get(['id', 'nama', 'nisn', 'kelas']),
                $today
            );

            if ($statusFilter !== null) {
                $rows = array_values(array_filter($rows, function (array $row) use ($statusFilter): bool {
                    return $this->normalizeMonitoringStatusFilterValue($row['status'] ?? null) === $statusFilter;
                }));
            }

            if ($fetchAll || $perPage === 'all') {
                $meta = $this->buildPaginationMeta(count($rows), 1, max(count($rows), 1));
                $meta['classes'] = $classOptions;
                $meta['attendanceMode'] = $attendanceMode;

                return [
                    'success' => true,
                    'data' => $rows,
                    'meta' => $meta,
                ];
            }

            $meta = $this->buildPaginationMeta(count($rows), $page, $perPage);
            $meta['attendanceMode'] = $attendanceMode;

            return [
                'success' => true,
                'data' => array_slice($rows, $meta['from'] > 0 ? $meta['from'] - 1 : 0, $meta['per_page']),
                'meta' => $meta + ['classes' => $classOptions],
            ];
        }

        $total = (clone $siswaQuery)->count();
        $meta = $this->buildPaginationMeta($total, $page, $perPage);
        $siswaList = $siswaQuery
            ->orderBy('kelas')
            ->orderBy('nama')
            ->forPage($meta['page'], $meta['per_page'])
            ->get(['id', 'nama', 'nisn', 'kelas']);
        $meta['classes'] = $classOptions;
        $meta['attendanceMode'] = $attendanceMode;

        return [
            'success' => true,
            'data' => $this->buildMonitoringRealtimeRows($siswaList, $today),
            'meta' => $meta,
        ];
    }


    public function getDashboardSummary(array $args, $auth): array
    {
        $role = $this->getRoleFromAuth($auth);
        if (!$auth || !in_array($role, ['admin', 'kepsek', 'wakel', 'wakasek', 'piket', 'siswa'], true)) {
            return ['success' => false, 'message' => 'Akses Ditolak: Anda tidak memiliki izin.'];
        }

        $payload = is_array($args[0] ?? null) ? $args[0] : [];
        $filterKelas = $this->normalizeKelasValue($payload['kelas'] ?? null);
        if ($filterKelas === null && !is_array($args[0] ?? null)) {
            $filterKelas = $this->normalizeKelasValue($args[0] ?? null);
        }
        if ($role === 'wakel') {
            $wakelKelas = $this->getWakelKelasFromAuth($auth);
            if ($wakelKelas === null) {
                return ['success' => false, 'message' => 'Akun wali kelas belum ditautkan ke kelas.'];
            }

            $filterKelas = $wakelKelas;
        } elseif ($role === 'piket') {
            $piketKelas = $this->getPiketKelasFromAuth($auth);
            if ($piketKelas !== null) {
                $filterKelas = $piketKelas;
            }
        } elseif ($role === 'siswa') {
            $siswaKelas = $this->getSiswaKelasFromAuth($auth);
            if ($siswaKelas === null) {
                return ['success' => false, 'message' => 'Data kelas siswa tidak ditemukan.'];
            }

            $filterKelas = $siswaKelas;
        }

        $today = Carbon::today()->toDateString();
        $requiresStudentSnapshot = $role === 'siswa';
        $targetNisn = $requiresStudentSnapshot
            ? trim((string) ($this->getSiswaFromAuth($auth)?->nisn ?? ''))
            : '';
        $siswaQuery = Siswa::query();
        if ($filterKelas) {
            $siswaQuery->where('kelas', $filterKelas);
        }

        $selectColumns = $requiresStudentSnapshot
            ? ['id', 'nama', 'nisn', 'kelas']
            : ['id', 'nisn', 'kelas'];
        $siswaList = $siswaQuery
            ->orderBy('kelas')
            ->orderBy('nama')
            ->get($selectColumns);

        $summary = [
            'total' => 0,
            'totalAktif' => 0,
            'hadir' => 0,
            'masuk' => 0,
            'sakit' => 0,
            'izin' => 0,
            'alpa' => 0,
            'belum' => 0,
            'libur' => 0,
        ];
        $studentSnapshot = null;

        if ($siswaList->isNotEmpty()) {
            $support = $this->buildMonitoringRealtimeSupport($siswaList, $today);

            foreach ($siswaList as $siswa) {
                $absenRow = $support['absensiRaw']->get($siswa->nisn);
                $status = $this->resolveMonitoringRealtimeDisplayStatus(
                    $siswa,
                    $absenRow,
                    $today,
                    $support['holidayRanges'],
                    $support['jadwalLiburMap']
                );
                $summary['total']++;

                if ($status === 'Libur') {
                    $summary['libur']++;
                } else {
                    $summary['totalAktif']++;
                }

                if ($status === 'Hadir') {
                    $summary['hadir']++;
                } elseif ($status === 'Masuk') {
                    $summary['masuk']++;
                } elseif ($status === 'Sakit') {
                    $summary['sakit']++;
                } elseif ($status === 'Izin') {
                    $summary['izin']++;
                } elseif ($status === 'Alpa') {
                    $summary['alpa']++;
                } elseif ($status === 'Belum Absen') {
                    $summary['belum']++;
                }

                if ($targetNisn !== '' && trim((string) $siswa->nisn) === $targetNisn) {
                    $studentSnapshot = $this->buildMonitoringRealtimeRow(
                        $siswa,
                        $absenRow,
                        $today,
                        $support['holidayRanges'],
                        $support['jadwalLiburMap']
                    );
                }
            }
        }

        return [
            'success' => true,
            'data' => [
                'attendanceMode' => $this->getAttendanceMode(),
                'summary' => $summary,
                'student' => $studentSnapshot,
            ],
        ];
    }


    private function buildMonitoringRealtimeRows($siswaList, string $date): array
    {
        if ($siswaList->isEmpty()) {
            return [];
        }

        $support = $this->buildMonitoringRealtimeSupport($siswaList, $date);
        $result = [];

        foreach ($siswaList as $siswa) {
            $result[] = $this->buildMonitoringRealtimeRow(
                $siswa,
                $support['absensiRaw']->get($siswa->nisn),
                $date,
                $support['holidayRanges'],
                $support['jadwalLiburMap']
            );
        }

        return $result;
    }


    private function buildMonitoringRealtimeSupport($siswaList, string $date): array
    {
        $holidayRanges = $this->getHolidayRanges($date, $date);
        $nisnList = $siswaList
            ->pluck('nisn')
            ->filter(fn ($nisn) => trim((string) $nisn) !== '')
            ->values()
            ->all();

        $absensiQuery = Absensi::query()->where('tanggal', $date);
        if (!empty($nisnList)) {
            $absensiQuery->whereIn('nisn', $nisnList);
        } else {
            $absensiQuery->whereRaw('1 = 0');
        }

        $absensiRaw = $absensiQuery
            ->get([
                'nisn',
                'status',
                'keterangan',
                'jam_datang',
                'jam_pulang',
            ])
            ->keyBy('nisn');
        return [
            'holidayRanges' => $holidayRanges,
            'absensiRaw' => $absensiRaw,
            'jadwalLiburMap' => $this->getJadwalLiburMapByKelas($siswaList->pluck('kelas')->all()),
        ];
    }


    private function resolveMonitoringRealtimeDisplayStatus(Siswa $siswa, $absen, string $date, array $holidayRanges, array $jadwalLiburMap): string
    {
        if ($absen) {
            return $this->normalizeMonitoringStatusFilterValue($absen->status) ?? 'Belum Absen';
        }

        $holidayName = $this->resolveHolidayNameForDate($date, $siswa->kelas, $holidayRanges);
        if ($holidayName === null) {
            $holidayName = $this->resolveJadwalLiburNameForDate($date, $siswa->kelas, $jadwalLiburMap);
        }

        return $holidayName !== null ? 'Libur' : 'Belum Absen';
    }


    private function buildMonitoringRealtimeRow(Siswa $siswa, $absen, string $date, array $holidayRanges, array $jadwalLiburMap): array
    {
        $jamDatang = '-';
        $jamPulang = '-';
        $displayStatus = 'Belum Absen';
        $keteranganWaktu = '-';

        if ($absen) {
            $jamDatang = $absen->jam_datang ? substr((string) $absen->jam_datang, 0, 5) : '-';
            $jamPulang = $absen->jam_pulang ? substr((string) $absen->jam_pulang, 0, 5) : '-';
            $displayStatus = $this->normalizeAttendanceStatusLabel($absen->status);
            $keteranganWaktu = $absen->keterangan ?: '';

            if (!$keteranganWaktu || trim($keteranganWaktu) === '') {
                $keteranganWaktu = $displayStatus === 'Hadir' ? 'Tepat Waktu' : '-';
            }
        } else {
            $holidayName = $this->resolveHolidayNameForDate($date, $siswa->kelas, $holidayRanges);
            if ($holidayName === null) {
                $holidayName = $this->resolveJadwalLiburNameForDate($date, $siswa->kelas, $jadwalLiburMap);
            }
            if ($holidayName !== null) {
                $displayStatus = 'Libur';
                $keteranganWaktu = $holidayName;
            }
        }

        return [
            'nama' => $siswa->nama,
            'nisn' => $siswa->nisn,
            'kelas' => $siswa->kelas,
            'jamDatang' => $jamDatang,
            'jamPulang' => $jamPulang,
            'status' => $displayStatus,
            'keterangan' => $keteranganWaktu,
        ];
    }


    private function normalizeMonitoringStatusFilterValue($status): ?string
    {
        $value = trim((string) ($status ?? ''));
        if ($value === '') {
            return null;
        }

        return $value === 'Alfa' ? 'Alpa' : $value;
    }


    public function getAbsensiList(array $args): array
    {
        $filter = $args[0] ?? [];
        $start = $filter['tanggalMulai'] ?? Carbon::today()->toDateString();
        $end = $filter['tanggalAkhir'] ?? Carbon::today()->toDateString();
        $kelas = $filter['kelas'] ?? null;

        $query = Absensi::query()->whereBetween('tanggal', [$start, $end]);
        if ($kelas) {
            $query->where('kelas', $kelas);
        }

        $data = $query
            ->orderByDesc('tanggal')
            ->orderBy('kelas')
            ->orderBy('nama')
            ->get([
                'tanggal',
                'nisn',
                'nama',
                'kelas',
                'jam_datang',
                'jam_pulang',
                'keterangan',
                'status',
            ])
            ->map(function (Absensi $row) {
                return [
                    'tanggal' => optional($row->tanggal)->format('Y-m-d'),
                    'nisn' => $row->nisn,
                    'nama' => $row->nama,
                    'kelas' => $row->kelas,
                    'jamDatang' => $row->jam_datang ? substr((string) $row->jam_datang, 0, 5) : '-',
                    'jamPulang' => $row->jam_pulang ? substr((string) $row->jam_pulang, 0, 5) : '-',
                    'keterangan' => $row->keterangan ?: '-',
                    'status' => $this->normalizeAttendanceStatusLabel($row->status),
                ];
            })
            ->values()
            ->all();

        return ['success' => true, 'data' => $data];
    }


    public function getMonthlyReportData(array $args, $auth): array
    {
        $role = $this->getRoleFromAuth($auth);
        if (!$auth || !in_array($role, ['admin', 'kepsek', 'wakel', 'wakasek'], true)) {
            return ['success' => false, 'message' => 'Akses Ditolak: Anda tidak memiliki izin.'];
        }

        $bulan = isset($args[0]) ? (int) $args[0] : (int) (now()->month - 1);
        $tahun = isset($args[1]) ? (int) $args[1] : (int) now()->year;
        $kelas = $this->normalizeKelasValue($args[2] ?? null);
        if ($role === 'wakel') {
            $wakelKelas = $this->getWakelKelasFromAuth($auth);
            if ($wakelKelas === null) {
                return ['success' => false, 'message' => 'Akun wali kelas belum ditautkan ke kelas.'];
            }

            $kelas = $wakelKelas;
        }

        $startDate = Carbon::create($tahun, $bulan + 1, 1);
        $endDate = $startDate->copy()->endOfMonth();
        $daysInMonth = $endDate->day;
        $today = Carbon::today()->toDateString();

        $holidayRanges = $this->getHolidayRanges(
            $startDate->toDateString(),
            $endDate->toDateString()
        );

        $siswaQuery = Siswa::query();
        if ($kelas !== null) {
            $siswaQuery->where('kelas', $kelas);
        }

        $siswaList = $siswaQuery
            ->orderBy('nama')
            ->get(['id', 'nama', 'nisn', 'kelas']);
        $jadwalLiburMap = $this->getJadwalLiburMapByKelas($siswaList->pluck('kelas')->all());
        $absensi = Absensi::query()
            ->whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()])
            ->get(['tanggal', 'nisn', 'status'])
            ->keyBy(fn ($row) => $row->tanggal->format('Y-m-d') . '_' . $row->nisn);

        $students = [];
        foreach ($siswaList as $siswa) {
            $stats = ['h' => 0, 'm' => 0, 's' => 0, 'i' => 0, 'a' => 0, 'effective' => 0];
            $dailyCodes = [];

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $currentDate = Carbon::create($tahun, $bulan + 1, $d);
                $dateStr = $currentDate->toDateString();
                $holidayName = $this->resolveHolidayNameForDate($dateStr, $siswa->kelas, $holidayRanges);
                if ($holidayName === null) {
                    $holidayName = $this->resolveJadwalLiburNameForDate($dateStr, $siswa->kelas, $jadwalLiburMap);
                }
                $isHoliday = $holidayName !== null;

                if ($isHoliday) {
                    $dailyCodes[] = ['code' => 'L', 'isHoliday' => true];
                    continue;
                }

                $row = $absensi->get($dateStr . '_' . $siswa->nisn);
                if ($dateStr > $today && !$row) {
                    $dailyCodes[] = ['code' => '', 'isHoliday' => false];
                    continue;
                }

                $stats['effective']++;
                $status = $this->normalizeAttendanceStatusLabel($row?->status);
                $code = 'A';

                if ($status === 'Hadir') {
                    $code = 'H';
                    $stats['h']++;
                } elseif ($status === 'Masuk') {
                    $code = 'M';
                    $stats['m']++;
                } elseif ($status === 'Sakit') {
                    $code = 'S';
                    $stats['s']++;
                } elseif ($status === 'Izin') {
                    $code = 'I';
                    $stats['i']++;
                } else {
                    $stats['a']++;
                }

                $dailyCodes[] = ['code' => $code, 'isHoliday' => false];
            }

            $percent = $stats['effective'] > 0 ? (int) round(($stats['h'] / $stats['effective']) * 100) : 0;
            $students[] = [
                'nama' => $siswa->nama,
                'nisn' => $siswa->nisn,
                'kelas' => $siswa->kelas,
                'dailyCodes' => $dailyCodes,
                'stats' => [
                    'h' => $stats['h'],
                    'm' => $stats['m'],
                    's' => $stats['s'],
                    'i' => $stats['i'],
                    'a' => $stats['a'],
                    'percent' => $percent,
                ],
            ];
        }

        return [
            'success' => true,
            'data' => [
                'daysInMonth' => $daysInMonth,
                'students' => $students,
            ],
        ];
    }


    public function batchScanAbsensi(array $args, $auth): array
    {
        $scannedCodes = $args[0] ?? [];
        $role = $this->getRoleFromAuth($auth);
        if (!$auth || !in_array($role, ['admin', 'kepsek', 'wakasek', 'wakel', 'piket'], true)) {
            return ['success' => false, 'message' => 'Akses Ditolak: Anda tidak memiliki izin scan.'];
        }

        $wakelKelas = $role === 'wakel' ? $this->getWakelKelasFromAuth($auth) : null;
        if ($role === 'wakel' && $wakelKelas === null) {
            return ['success' => false, 'message' => 'Akun wali kelas belum ditautkan ke kelas.'];
        }

        $piketKelas = $role === 'piket' ? $this->getPiketKelasFromAuth($auth) : null;

        if (!is_array($scannedCodes) || count($scannedCodes) === 0) {
            return ['success' => false, 'message' => 'Tidak ada kode scan yang dikirim.'];
        }

        $normalizedCodes = [];
        foreach ($scannedCodes as $rawCode) {
            $nisn = trim((string) $rawCode);
            if ($nisn !== '') {
                $normalizedCodes[] = $nisn;
            }
        }

        if ($normalizedCodes === []) {
            return ['success' => false, 'message' => 'Tidak ada kode scan yang valid.'];
        }

        $attendanceService = app(StudentAttendanceService::class);
        $studentsByNisn = Siswa::query()
            ->whereIn('nisn', array_values(array_unique($normalizedCodes)))
            ->get(['id', 'nisn', 'nama', 'kelas'])
            ->keyBy(fn (Siswa $siswa) => trim((string) $siswa->nisn));
        $results = [];
        foreach ($normalizedCodes as $nisn) {
            $siswa = $studentsByNisn->get($nisn);
            if (!$siswa) {
                $results[] = [
                    'success' => false,
                    'nisn' => $nisn,
                    'message' => 'NISN tidak ditemukan.',
                ];

                continue;
            }

            $siswaKelas = $this->normalizeKelasValue($siswa->kelas);
            if ($role === 'wakel' && $wakelKelas !== null && $siswaKelas !== $wakelKelas) {
                $results[] = [
                    'success' => false,
                    'nisn' => $siswa->nisn,
                    'nama' => $siswa->nama,
                    'kelas' => $siswa->kelas,
                    'message' => 'Bukan kelas yang Anda ampu.',
                ];
                continue;
            }

            if ($role === 'piket' && $piketKelas !== null && $siswaKelas !== $piketKelas) {
                $results[] = [
                    'success' => false,
                    'nisn' => $siswa->nisn,
                    'nama' => $siswa->nama,
                    'kelas' => $siswa->kelas,
                    'message' => 'Akun piket ini hanya bisa scan kelas ' . $piketKelas . '.',
                ];
                continue;
            }

            $results[] = $attendanceService->process($siswa);
        }

        return ['success' => true, 'results' => $results];
    }


    public function scanRfidAbsensi(array $args, $auth): array
    {
        $role = $this->getRoleFromAuth($auth);
        if (!$auth || !in_array($role, ['admin', 'kepsek', 'wakasek', 'wakel', 'piket'], true)) {
            return ['success' => false, 'message' => 'Akses Ditolak: Anda tidak memiliki izin scan.'];
        }

        $uid = trim((string) ($args[0] ?? ''));
        if ($uid === '') {
            return ['success' => false, 'message' => 'UID RFID tidak valid.'];
        }

        $wakelKelas = $role === 'wakel' ? $this->getWakelKelasFromAuth($auth) : null;
        if ($role === 'wakel' && $wakelKelas === null) {
            return ['success' => false, 'message' => 'Akun wali kelas belum ditautkan ke kelas.'];
        }

        $piketKelas = $role === 'piket' ? $this->getPiketKelasFromAuth($auth) : null;
        $cardService = app(AttendanceCardService::class);
        $attendanceService = app(StudentAttendanceService::class);

        $resolvedCard = $cardService->resolveFromScan($uid, KartuAbsensi::TYPE_RFID, 'web');
        if (!($resolvedCard['success'] ?? false)) {
            return [
                'success' => true,
                'results' => [[
                    'success' => false,
                    'uid' => strtoupper($uid),
                    'message' => $resolvedCard['message'] ?? 'UID RFID tidak valid.',
                ]],
            ];
        }

        $card = $resolvedCard['card'] ?? null;
        if (!$card) {
            return [
                'success' => true,
                'results' => [[
                    'success' => false,
                    'uid' => strtoupper($uid),
                    'message' => 'Data kartu RFID tidak ditemukan.',
                ]],
            ];
        }

        if (!$card->siswa) {
            $isNewCard = (bool) ($resolvedCard['created'] ?? false);

            return [
                'success' => true,
                'results' => [[
                    'success' => false,
                    'uid' => (string) $card->code,
                    'message' => $isNewCard
                        ? 'Kartu RFID baru terdeteksi. Tautkan kartu ke siswa terlebih dahulu.'
                        : 'Kartu RFID belum ditautkan ke siswa.',
                    'reason' => $isNewCard ? 'new_card_detected' : 'card_not_linked',
                ]],
            ];
        }

        $siswa = $card->siswa;
        $siswaKelas = $this->normalizeKelasValue($siswa->kelas);

        if ($role === 'wakel' && $wakelKelas !== null && $siswaKelas !== $wakelKelas) {
            return [
                'success' => true,
                'results' => [[
                    'success' => false,
                    'uid' => (string) $card->code,
                    'nisn' => $siswa->nisn,
                    'nama' => $siswa->nama,
                    'kelas' => $siswa->kelas,
                    'message' => 'Bukan kelas yang Anda ampu.',
                ]],
            ];
        }

        if ($role === 'piket' && $piketKelas !== null && $siswaKelas !== $piketKelas) {
            return [
                'success' => true,
                'results' => [[
                    'success' => false,
                    'uid' => (string) $card->code,
                    'nisn' => $siswa->nisn,
                    'nama' => $siswa->nama,
                    'kelas' => $siswa->kelas,
                    'message' => 'Akun piket ini hanya bisa scan kelas ' . $piketKelas . '.',
                ]],
            ];
        }

        $attendance = $attendanceService->process($siswa);
        $attendance['uid'] = (string) $card->code;

        return [
            'success' => true,
            'results' => [$attendance],
        ];
    }


    public function updateAbsensiStatus(array $args, $auth): array
    {
        // Kepsek dan wakasek bersifat read-only untuk monitoring absensi.
        if (!$this->authHasAnyRole($auth, ['admin', 'wakel'])) {
            return ['success' => false, 'message' => 'Akses Ditolak: Anda tidak memiliki izin.'];
        }

        $args = $this->stripTokenArg($args);
        $nisn = trim((string) ($args[0] ?? ''));
        $nama = trim((string) ($args[1] ?? ''));
        $kelas = trim((string) ($args[2] ?? ''));
        $newStatus = trim((string) ($args[3] ?? ''));

        if ($nisn === '' || $newStatus === '') {
            return ['success' => false, 'message' => 'Parameter tidak valid.'];
        }

        $siswa = Siswa::query()->where('nisn', $nisn)->first();
        if (!$siswa) {
            return ['success' => false, 'message' => 'Siswa tidak ditemukan.'];
        }

        $today = Carbon::today()->toDateString();
        $row = Absensi::query()
            ->where('tanggal', $today)
            ->where('siswa_id', $siswa->id)
            ->first();
        $previousStatus = $row
            ? $this->normalizeAttendanceStatusLabel($row->status)
            : 'Belum Absen';

        if ($newStatus === 'Belum Absen') {
            if ($row) {
                $row->delete();
            }
            return ['success' => true, 'message' => 'Status direset menjadi Belum Absen.'];
        }

        if ($newStatus === 'Masuk' && $this->getAttendanceMode() !== AttendanceMode::CHECKIN_CHECKOUT) {
            return ['success' => false, 'message' => 'Status Masuk hanya tersedia saat mode absensi Masuk + Pulang aktif.'];
        }

        if (!$row) {
            $row = Absensi::query()->create([
                'tanggal' => $today,
                'siswa_id' => $siswa->id,
                'nisn' => $siswa->nisn,
                'nama' => $nama !== '' ? $nama : $siswa->nama,
                'kelas' => $kelas !== '' ? $kelas : $siswa->kelas,
                'jam_datang' => null,
                'jam_pulang' => null,
                'status' => $newStatus,
                'keterangan' => null,
            ]);
        } else {
            $row->status = $newStatus;
        }

        if ($newStatus === 'Masuk') {
            if (!$row->jam_datang) {
                $row->jam_datang = Carbon::now()->format('H:i:s');
            }
            $row->jam_pulang = null;
            $row->status = 'Masuk';
            $jamKelas = $this->getJamConfigForKelas($siswa->kelas);
            $masukTelat = $jamKelas['jam_masuk_telat'];
            $row->keterangan = ((string) $row->jam_datang) > ($masukTelat . ':00') ? 'Terlambat' : 'Tepat Waktu';
        } elseif ($newStatus === 'Hadir') {
            if (!$row->jam_datang) {
                $row->jam_datang = Carbon::now()->format('H:i:s');
            }
            $row->status = 'Hadir';
            $jamKelas = $this->getJamConfigForKelas($siswa->kelas);
            $masukTelat = $jamKelas['jam_masuk_telat'];
            $row->keterangan = ((string) $row->jam_datang) > ($masukTelat . ':00') ? 'Terlambat' : 'Tepat Waktu';
        } elseif ($newStatus === 'Sakit') {
            $row->keterangan = 'Sakit';
        } elseif ($newStatus === 'Izin') {
            $row->keterangan = 'Izin';
        } elseif ($newStatus === 'Alpa') {
            $row->keterangan = 'Alpa';
        }

        $row->save();

        app(StudentAttendanceService::class)->dispatchManualStatusChangeNotification(
            $siswa,
            $row,
            $previousStatus,
            $newStatus
        );

        return ['success' => true, 'message' => 'Status absensi diperbarui.'];
    }

}
