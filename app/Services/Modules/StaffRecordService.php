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
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Role;

class StaffRecordService extends BaseActionService
{
    public function getGuruList($auth): array
    {
        if ($denied = $this->requireAdminOrKepsek($auth)) {
            return $denied;
        }

        $data = User::role('wakel')
            ->orderBy('username')
            ->get()
            ->map(function (User $user) {
                $tanggalLahir = null;
                if (!empty($user->tanggal_lahir)) {
                    try {
                        $tanggalLahir = Carbon::parse((string) $user->tanggal_lahir)->format('Y-m-d');
                    } catch (\Throwable $e) {
                        $tanggalLahir = null;
                    }
                }

                return [
                    'username' => $user->username,
                    'name' => $user->name ?: $user->username,
                    'email' => $user->email,
                    'kelas' => $user->kelas,
                    'jenisKelamin' => $user->jenis_kelamin,
                    'tanggalLahir' => $tanggalLahir,
                    'agama' => $user->agama,
                    'noHp' => $user->no_hp,
                    'alamat' => $user->alamat,
                    'role' => $this->getPrimaryRoleForUser($user),
                    'password' => '******',
                ];
            })
            ->values()
            ->all();

        return ['success' => true, 'data' => $data];
    }


    public function getPiketList($auth): array
    {
        if ($denied = $this->requireAdminOrKepsek($auth)) {
            return $denied;
        }

        $data = User::role('piket')
            ->orderBy('username')
            ->get()
            ->map(function (User $user) {
                $tanggalLahir = null;
                if (!empty($user->tanggal_lahir)) {
                    try {
                        $tanggalLahir = Carbon::parse((string) $user->tanggal_lahir)->format('Y-m-d');
                    } catch (\Throwable $e) {
                        $tanggalLahir = null;
                    }
                }

                return [
                    'username' => $user->username,
                    'name' => $user->name ?: $user->username,
                    'email' => $user->email,
                    'kelas' => $user->kelas,
                    'jenisKelamin' => $user->jenis_kelamin,
                    'tanggalLahir' => $tanggalLahir,
                    'agama' => $user->agama,
                    'noHp' => $user->no_hp,
                    'alamat' => $user->alamat,
                    'role' => $this->getPrimaryRoleForUser($user),
                    'password' => '******',
                ];
            })
            ->values()
            ->all();

        return ['success' => true, 'data' => $data];
    }


    public function addGuru(array $args, $auth): array
    {
        if ($denied = $this->requireAdminOrKepsek($auth)) {
            return $denied;
        }

        $args = $this->stripTokenArg($args);
        $username = ltrim(trim((string) ($args[0] ?? '')), "'");
        $password = ltrim(trim((string) ($args[1] ?? '')), "'");
        $kelas = $this->syncKelasValue($args[2] ?? null);
        $name = trim((string) ($args[3] ?? ''));
        $email = $this->normalizeEmailValue($args[4] ?? null);
        $jenisKelamin = $this->normalizeOptionalString($args[5] ?? null);
        $tanggalLahirInput = trim((string) ($args[6] ?? ''));
        $agama = $this->normalizeOptionalString($args[7] ?? null);
        $noHp = $this->normalizeOptionalString($args[8] ?? null);
        $alamat = $this->normalizeOptionalString($args[9] ?? null);

        $tanggalLahir = null;
        if ($tanggalLahirInput !== '') {
            try {
                $tanggalLahir = Carbon::parse($tanggalLahirInput)->format('Y-m-d');
            } catch (\Throwable $e) {
                return ['success' => false, 'message' => 'Format tanggal lahir guru tidak valid.'];
            }
        }

        if ($username === '' || $password === '') {
            return ['success' => false, 'message' => 'Username dan password wajib diisi.'];
        }

        if ($name === '') {
            $name = $username;
        }

        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Format email tidak valid.'];
        }

        if (User::query()->where('username', $username)->exists()) {
            return ['success' => false, 'message' => 'Username sudah digunakan.'];
        }

        if ($email !== null && User::query()->where('email', $email)->exists()) {
            return ['success' => false, 'message' => 'Email sudah digunakan.'];
        }

        if ($this->isDuplicateNoHp($noHp)) {
            return ['success' => false, 'message' => 'No HP sudah digunakan.'];
        }

        DB::transaction(function () use ($username, $name, $email, $password, $kelas, $jenisKelamin, $tanggalLahir, $agama, $noHp, $alamat): void {
            $user = User::query()->create([
                'username' => $username,
                'name' => $name,
                'email' => $email,
                'password' => bcrypt($password),
                'kelas' => $kelas,
                'jenis_kelamin' => $jenisKelamin,
                'tanggal_lahir' => $tanggalLahir,
                'agama' => $agama,
                'no_hp' => $noHp,
                'alamat' => $alamat,
            ]);
            $this->syncSpatieRoleForUser($user, 'wakel');
            $this->syncGuruClassBinding($user, $kelas, null);
        });

        return ['success' => true, 'message' => 'Akun guru berhasil ditambahkan.'];
    }


    public function updateGuru(array $args, $auth): array
    {
        if ($denied = $this->requireAdminOrKepsek($auth)) {
            return $denied;
        }

        $args = $this->stripTokenArg($args);
        $oldUsername = ltrim(trim((string) ($args[0] ?? '')), "'");
        $newUsername = ltrim(trim((string) ($args[1] ?? '')), "'");
        $password = ltrim(trim((string) ($args[2] ?? '')), "'");
        $kelas = $this->syncKelasValue($args[3] ?? null);
        $name = trim((string) ($args[4] ?? ''));
        $email = $this->normalizeEmailValue($args[5] ?? null);
        $jenisKelamin = $this->normalizeOptionalString($args[6] ?? null);
        $tanggalLahirInput = trim((string) ($args[7] ?? ''));
        $agama = $this->normalizeOptionalString($args[8] ?? null);
        $noHp = $this->normalizeOptionalString($args[9] ?? null);
        $alamat = $this->normalizeOptionalString($args[10] ?? null);

        $tanggalLahir = null;
        if ($tanggalLahirInput !== '') {
            try {
                $tanggalLahir = Carbon::parse($tanggalLahirInput)->format('Y-m-d');
            } catch (\Throwable $e) {
                return ['success' => false, 'message' => 'Format tanggal lahir guru tidak valid.'];
            }
        }

        if ($oldUsername === '' || $newUsername === '') {
            return ['success' => false, 'message' => 'Username tidak valid.'];
        }

        $user = User::query()->where('username', $oldUsername)->first();
        if (!$user) {
            return ['success' => false, 'message' => 'Akun guru tidak ditemukan.'];
        }
        if (!$user->hasRole('wakel')) {
            return ['success' => false, 'message' => 'Akun ini bukan guru/wali kelas yang dapat dikelola dari menu ini.'];
        }

        if ($newUsername !== $oldUsername && User::query()->where('username', $newUsername)->exists()) {
            return ['success' => false, 'message' => 'Username baru sudah digunakan.'];
        }

        if ($name === '') {
            $name = trim((string) ($user->name ?? ''));
            if ($name === '') {
                $name = $newUsername;
            }
        }

        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Format email tidak valid.'];
        }

        if (
            $email !== null &&
            User::query()
                ->where('email', $email)
                ->where('username', '!=', $oldUsername)
                ->exists()
        ) {
            return ['success' => false, 'message' => 'Email sudah digunakan.'];
        }

        if ($this->isDuplicateNoHp($noHp, (int) $user->id)) {
            return ['success' => false, 'message' => 'No HP sudah digunakan.'];
        }

        $previousKelas = $this->normalizeKelasValue($user->kelas);

        $payload = [
            'username' => $newUsername,
            'name' => $name,
            'email' => $email,
            'kelas' => $kelas,
            'jenis_kelamin' => $jenisKelamin,
            'tanggal_lahir' => $tanggalLahir,
            'agama' => $agama,
            'no_hp' => $noHp,
            'alamat' => $alamat,
        ];

        if ($password !== '') {
            $payload['password'] = bcrypt($password);
        }

        DB::transaction(function () use ($user, $payload, $kelas, $previousKelas): void {
            $user->update($payload);
            $this->syncSpatieRoleForUser($user, 'wakel');
            $this->syncGuruClassBinding($user, $kelas, $previousKelas);
        });

        return ['success' => true, 'message' => 'Akun guru berhasil diperbarui.'];
    }


    public function deleteGuru(array $args, $auth): array
    {
        if ($denied = $this->requireAdminOrKepsek($auth)) {
            return $denied;
        }

        $args = $this->stripTokenArg($args);
        $username = ltrim(trim((string) ($args[0] ?? '')), "'");
        if ($username === '') {
            return ['success' => false, 'message' => 'Username wajib diisi.'];
        }

        $user = User::query()->where('username', $username)->first();
        if (!$user) {
            return ['success' => false, 'message' => 'Akun guru tidak ditemukan.'];
        }
        if (!$user->hasRole('wakel')) {
            return ['success' => false, 'message' => 'Akun ini bukan guru/wali kelas yang dapat dihapus dari menu ini.'];
        }

        if ($user->hasAnyRole(['super-admin', 'admin'])) {
            return ['success' => false, 'message' => 'Akun admin/super-admin tidak dapat dihapus lewat menu ini.'];
        }

        DB::transaction(function () use ($user): void {
            Kelas::query()
                ->where('wali_kelas', (int) $user->id)
                ->update(['wali_kelas' => null]);
            $user->delete();
        });
        return ['success' => true, 'message' => 'Akun guru berhasil dihapus.'];
    }

    public function deleteGuruBulk(array $args, $auth): array
    {
        return $this->deleteStaffBulk($args, $auth, 'wakel', 'guru', true);
    }


    public function addPiket(array $args, $auth): array
    {
        if ($denied = $this->requireAdminOrKepsek($auth)) {
            return $denied;
        }

        $args = $this->stripTokenArg($args);
        $username = ltrim(trim((string) ($args[0] ?? '')), "'");
        $password = ltrim(trim((string) ($args[1] ?? '')), "'");
        $kelas = $this->syncKelasValue($args[2] ?? null);
        $name = trim((string) ($args[3] ?? ''));
        $email = $this->normalizeEmailValue($args[4] ?? null);
        $jenisKelamin = $this->normalizeOptionalString($args[5] ?? null);
        $tanggalLahirInput = trim((string) ($args[6] ?? ''));
        $agama = $this->normalizeOptionalString($args[7] ?? null);
        $noHp = $this->normalizeOptionalString($args[8] ?? null);
        $alamat = $this->normalizeOptionalString($args[9] ?? null);

        $tanggalLahir = null;
        if ($tanggalLahirInput !== '') {
            try {
                $tanggalLahir = Carbon::parse($tanggalLahirInput)->format('Y-m-d');
            } catch (\Throwable $e) {
                return ['success' => false, 'message' => 'Format tanggal lahir piket tidak valid.'];
            }
        }

        if ($username === '' || $password === '') {
            return ['success' => false, 'message' => 'Username dan password wajib diisi.'];
        }

        if ($name === '') {
            $name = $username;
        }

        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Format email tidak valid.'];
        }

        if (User::query()->where('username', $username)->exists()) {
            return ['success' => false, 'message' => 'Username sudah digunakan.'];
        }

        if ($email !== null && User::query()->where('email', $email)->exists()) {
            return ['success' => false, 'message' => 'Email sudah digunakan.'];
        }

        if ($this->isDuplicateNoHp($noHp)) {
            return ['success' => false, 'message' => 'No HP sudah digunakan.'];
        }

        $user = User::query()->create([
            'username' => $username,
            'name' => $name,
            'email' => $email,
            'password' => bcrypt($password),
            'kelas' => $kelas,
            'jenis_kelamin' => $jenisKelamin,
            'tanggal_lahir' => $tanggalLahir,
            'agama' => $agama,
            'no_hp' => $noHp,
            'alamat' => $alamat,
        ]);
        $this->syncSpatieRoleForUser($user, 'piket');

        return ['success' => true, 'message' => 'Akun piket berhasil ditambahkan.'];
    }


    public function updatePiket(array $args, $auth): array
    {
        if ($denied = $this->requireAdminOrKepsek($auth)) {
            return $denied;
        }

        $args = $this->stripTokenArg($args);
        $oldUsername = ltrim(trim((string) ($args[0] ?? '')), "'");
        $newUsername = ltrim(trim((string) ($args[1] ?? '')), "'");
        $password = ltrim(trim((string) ($args[2] ?? '')), "'");
        $kelas = $this->syncKelasValue($args[3] ?? null);
        $name = trim((string) ($args[4] ?? ''));
        $email = $this->normalizeEmailValue($args[5] ?? null);
        $jenisKelamin = $this->normalizeOptionalString($args[6] ?? null);
        $tanggalLahirInput = trim((string) ($args[7] ?? ''));
        $agama = $this->normalizeOptionalString($args[8] ?? null);
        $noHp = $this->normalizeOptionalString($args[9] ?? null);
        $alamat = $this->normalizeOptionalString($args[10] ?? null);

        $tanggalLahir = null;
        if ($tanggalLahirInput !== '') {
            try {
                $tanggalLahir = Carbon::parse($tanggalLahirInput)->format('Y-m-d');
            } catch (\Throwable $e) {
                return ['success' => false, 'message' => 'Format tanggal lahir piket tidak valid.'];
            }
        }

        if ($oldUsername === '' || $newUsername === '') {
            return ['success' => false, 'message' => 'Username tidak valid.'];
        }

        $user = User::query()->where('username', $oldUsername)->first();
        if (!$user) {
            return ['success' => false, 'message' => 'Akun piket tidak ditemukan.'];
        }
        if (!$user->hasRole('piket')) {
            return ['success' => false, 'message' => 'Akun ini bukan akun piket yang dapat dikelola dari menu ini.'];
        }

        if ($newUsername !== $oldUsername && User::query()->where('username', $newUsername)->exists()) {
            return ['success' => false, 'message' => 'Username baru sudah digunakan.'];
        }

        if ($name === '') {
            $name = trim((string) ($user->name ?? ''));
            if ($name === '') {
                $name = $newUsername;
            }
        }

        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Format email tidak valid.'];
        }

        if (
            $email !== null &&
            User::query()
                ->where('email', $email)
                ->where('username', '!=', $oldUsername)
                ->exists()
        ) {
            return ['success' => false, 'message' => 'Email sudah digunakan.'];
        }

        if ($this->isDuplicateNoHp($noHp, (int) $user->id)) {
            return ['success' => false, 'message' => 'No HP sudah digunakan.'];
        }

        $payload = [
            'username' => $newUsername,
            'name' => $name,
            'email' => $email,
            'kelas' => $kelas,
            'jenis_kelamin' => $jenisKelamin,
            'tanggal_lahir' => $tanggalLahir,
            'agama' => $agama,
            'no_hp' => $noHp,
            'alamat' => $alamat,
        ];

        if ($password !== '') {
            $payload['password'] = bcrypt($password);
        }

        $user->update($payload);
        $this->syncSpatieRoleForUser($user, 'piket');

        return ['success' => true, 'message' => 'Akun piket berhasil diperbarui.'];
    }


    public function deletePiket(array $args, $auth): array
    {
        if ($denied = $this->requireAdminOrKepsek($auth)) {
            return $denied;
        }

        $args = $this->stripTokenArg($args);
        $username = ltrim(trim((string) ($args[0] ?? '')), "'");
        if ($username === '') {
            return ['success' => false, 'message' => 'Username wajib diisi.'];
        }

        $user = User::query()->where('username', $username)->first();
        if (!$user) {
            return ['success' => false, 'message' => 'Akun piket tidak ditemukan.'];
        }
        if (!$user->hasRole('piket')) {
            return ['success' => false, 'message' => 'Akun ini bukan akun piket yang dapat dihapus dari menu ini.'];
        }

        if ($user->hasAnyRole(['super-admin', 'admin'])) {
            return ['success' => false, 'message' => 'Akun admin/super-admin tidak dapat dihapus lewat menu ini.'];
        }

        $user->delete();
        return ['success' => true, 'message' => 'Akun piket berhasil dihapus.'];
    }

    public function deletePiketBulk(array $args, $auth): array
    {
        return $this->deleteStaffBulk($args, $auth, 'piket', 'piket', false);
    }

    protected function deleteStaffBulk(array $args, $auth, string $roleName, string $label, bool $clearWaliKelas): array
    {
        if ($denied = $this->requireAdminOrKepsek($auth)) {
            return $denied;
        }

        $args = $this->stripTokenArg($args);
        $usernames = collect($args[0] ?? [])
            ->map(fn ($username) => ltrim(trim((string) $username), "'"))
            ->filter()
            ->unique()
            ->values();

        if ($usernames->isEmpty()) {
            return ['success' => false, 'message' => sprintf('Pilih minimal 1 akun %s yang akan dihapus.', $label)];
        }

        $users = User::query()
            ->whereIn('username', $usernames->all())
            ->get()
            ->filter(function (User $user) use ($roleName) {
                return $user->hasRole($roleName) && !$user->hasAnyRole(['super-admin', 'admin']);
            })
            ->values();

        if ($users->isEmpty()) {
            return ['success' => false, 'message' => sprintf('Tidak ada akun %s yang bisa dihapus.', $label)];
        }

        $userIds = $users->pluck('id')->map(fn ($id) => (int) $id)->all();
        $deletedCount = count($userIds);

        DB::transaction(function () use ($userIds, $clearWaliKelas): void {
            if ($clearWaliKelas && !empty($userIds)) {
                Kelas::query()
                    ->whereIn('wali_kelas', $userIds)
                    ->update(['wali_kelas' => null]);
            }

            if (!empty($userIds)) {
                User::query()->whereIn('id', $userIds)->delete();
            }
        });

        $requestedCount = $usernames->count();
        $skippedCount = max($requestedCount - $deletedCount, 0);
        $message = $deletedCount === 1
            ? sprintf('1 akun %s berhasil dihapus.', $label)
            : sprintf('%d akun %s berhasil dihapus.', $deletedCount, $label);

        if ($skippedCount > 0) {
            $message .= sprintf(' %d data dilewati karena tidak ditemukan atau tidak diizinkan.', $skippedCount);
        }

        return [
            'success' => true,
            'message' => $message,
            'deleted_count' => $deletedCount,
            'skipped_count' => $skippedCount,
        ];
    }
    public function importGuruFromSimpeg($auth): array
    {
        if (!$this->authHasAnyRole($auth, ['admin', 'kepsek'])) {
            return ['success' => false, 'message' => 'Akses Ditolak: Anda tidak memiliki izin.'];
        }

        $url = trim((string) config('services.simpeg_export.url'));
        $token = trim((string) config('services.simpeg_export.token'));
        $consumerDomain = trim((string) config('services.simpeg_export.consumer_domain'));
        $perPage = min(100, max(1, (int) config('services.simpeg_export.per_page', 100)));
        $timeout = max(1, (int) config('services.simpeg_export.timeout', 30));

        if ($url === '' || $token === '') {
            return [
                'success' => false,
                'message' => 'Konfigurasi SIMPEG belum lengkap. Periksa URL dan token API pada file .env.',
            ];
        }

        $employees = [];
        $cursor = null;

        for ($page = 1; $page <= 100; $page++) {
            $query = ['per_page' => $perPage];
            if ($cursor !== null) {
                $query['cursor'] = $cursor;
            }

            try {
                $request = Http::acceptJson()
                    ->timeout($timeout)
                    ->withToken($token);

                if ($consumerDomain !== '') {
                    $request = $request->withHeaders([
                        'X-Consumer-Domain' => $consumerDomain,
                    ]);
                }

                $response = $request->get($url, $query);
            } catch (\Throwable $exception) {
                Log::warning('Import guru SIMPEG gagal terhubung.', [
                    'exception' => $exception->getMessage(),
                ]);

                return [
                    'success' => false,
                    'message' => 'Gagal menghubungi server SIMPEG. Periksa URL layanan dan koneksi server.',
                ];
            }

            if ($response->failed()) {
                Log::warning('Import guru SIMPEG ditolak.', [
                    'status' => $response->status(),
                ]);

                return [
                    'success' => false,
                    'message' => $response->status() === 401 || $response->status() === 403
                        ? 'Akses SIMPEG ditolak. Periksa token API dan consumer domain.'
                        : 'Server SIMPEG tidak dapat memproses permintaan saat ini.',
                ];
            }

            $payload = $response->json();
            $pageEmployees = is_array($payload) ? ($payload['data'] ?? null) : null;

            if (!is_array($pageEmployees)) {
                return [
                    'success' => false,
                    'message' => 'Format data dari SIMPEG tidak sesuai. Data guru tidak diubah.',
                ];
            }

            foreach ($pageEmployees as $employee) {
                if (is_array($employee)) {
                    $employees[] = $employee;
                }
            }

            if (count($employees) > 2000) {
                return [
                    'success' => false,
                    'message' => 'Data SIMPEG melebihi batas 2.000 guru untuk sekali import.',
                ];
            }

            $nextCursor = trim((string) data_get($payload, 'meta.next_cursor', ''));
            if ($nextCursor === '') {
                break;
            }

            if ($nextCursor === $cursor) {
                return [
                    'success' => false,
                    'message' => 'Pagination SIMPEG tidak valid. Data guru tidak diubah.',
                ];
            }

            $cursor = $nextCursor;

            if ($page === 100) {
                return [
                    'success' => false,
                    'message' => 'Pagination SIMPEG terlalu panjang. Batasi data lalu coba kembali.',
                ];
            }
        }

        $rows = [];
        $invalidCount = 0;
        $mappingErrors = [];

        foreach ($employees as $index => $employee) {
            $username = '';
            foreach (['identity_nip', 'nip_baru', 'nip'] as $field) {
                $candidate = preg_replace('/\s+/', '', trim((string) ($employee[$field] ?? ''))) ?? '';
                if ($candidate !== '') {
                    $username = $candidate;
                    break;
                }
            }

            if ($username === '') {
                $invalidCount++;
                $mappingErrors[] = sprintf('Data SIMPEG #%d dilewati karena NIP tidak tersedia.', $index + 1);
                continue;
            }

            $name = trim((string) ($employee['nama_lengkap'] ?? $employee['nama'] ?? ''));
            $rows[] = [
                '_baris' => $index + 1,
                'username' => $username,
                'password' => Str::random(64),
                'name' => $name !== '' ? $name : $username,
            ];
        }

        if (empty($rows)) {
            return [
                'success' => true,
                'added' => 0,
                'skipped' => $invalidCount,
                'errors' => array_slice($mappingErrors, 0, 10),
                'message' => 'Tidak ada data guru SIMPEG yang dapat ditambahkan.',
            ];
        }

        $result = $this->importGuruBulk([$rows], $auth);
        if (!$result['success']) {
            return $result;
        }

        $added = (int) ($result['added'] ?? 0);
        $skipped = (int) ($result['skipped'] ?? 0) + $invalidCount;
        $errors = array_slice(array_merge($mappingErrors, $result['errors'] ?? []), 0, 10);

        return [
            'success' => true,
            'added' => $added,
            'skipped' => $skipped,
            'errors' => $errors,
            'message' => "Import SIMPEG selesai. Berhasil: {$added}, Duplikat/Gagal: {$skipped}. Akun baru dapat masuk melalui SSO Kemenag.",
        ];
    }




    public function importGuruBulk(array $args, $auth): array
    {
        if (!$this->authHasAnyRole($auth, ['admin', 'kepsek'])) {
            return ['success' => false, 'message' => 'Akses Ditolak: Anda tidak memiliki izin.'];
        }

        $dataArray = $args[0] ?? [];
        if (!is_array($dataArray) || empty($dataArray)) {
            return ['success' => false, 'message' => 'Tidak ada data guru yang dapat diimport.'];
        }

        if (count($dataArray) > 2000) {
            return ['success' => false, 'message' => 'Maksimal 2.000 baris data untuk sekali import.'];
        }

        $existing = User::query()
            ->pluck('username')
            ->map(fn ($username) => strtolower(trim((string) $username)))
            ->flip();
        $existingEmails = \App\Models\User::query()
            ->whereNotNull('email')
            ->pluck('email')
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->flip();
        $existingNoHp = User::query()
            ->whereNotNull('no_hp')
            ->pluck('no_hp')
            ->map(fn ($noHp) => $this->normalizeOptionalString($noHp))
            ->filter()
            ->flip();
        $rowsToAdd = [];
        $addedCount = 0;
        $skippedCount = 0;
        $kelasToSync = [];
        $errors = [];

        foreach ($dataArray as $index => $item) {
            $line = (int) ($item['_baris'] ?? ($index + 2));
            if (!is_array($item)) {
                $skippedCount++;
                $errors[] = "Baris {$line}: format data tidak dapat dibaca.";
                continue;
            }

            $username = preg_replace('/\s+/', '', trim((string) ($item['username'] ?? ''))) ?? '';
            $usernameKey = strtolower($username);
            $password = trim((string) ($item['password'] ?? ''));
            $name = trim((string) ($item['name'] ?? $item['nama'] ?? ''));
            $email = $this->normalizeEmailValue($item['email'] ?? null);
            $jenisKelamin = $this->normalizeOptionalString($item['jenisKelamin'] ?? $item['jenis_kelamin'] ?? null);
            $tanggalLahirRaw = $item['tanggalLahir'] ?? $item['tanggal_lahir'] ?? null;
            $agama = $this->normalizeOptionalString($item['agama'] ?? null);
            $noHp = $this->normalizeOptionalString($item['noHp'] ?? $item['no_hp'] ?? null);
            $alamat = $this->normalizeOptionalString($item['alamat'] ?? null);
            if ($username === '') {
                $skippedCount++;
                $errors[] = "Baris {$line}: Username wajib diisi.";
                continue;
            }

            if ($password === '') {
                $skippedCount++;
                $errors[] = "Baris {$line}: Password wajib diisi.";
                continue;
            }

            if ($name === '') {
                $name = $username;
            }

            if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skippedCount++;
                $errors[] = "Baris {$line}: format email tidak valid.";
                continue;
            }

            if (isset($existing[$usernameKey])) {
                $skippedCount++;
                $errors[] = "Baris {$line}: Username '{$username}' sudah digunakan.";
                continue;
            }

            if ($email !== null && isset($existingEmails[$email])) {
                $skippedCount++;
                $errors[] = "Baris {$line}: Email '{$email}' sudah digunakan.";
                continue;
            }

            if ($noHp !== null && isset($existingNoHp[$noHp])) {
                $skippedCount++;
                $errors[] = "Baris {$line}: No HP '{$noHp}' sudah digunakan.";
                continue;
            }

            $tanggalLahir = $this->normalizeDateValue($tanggalLahirRaw);
            if (trim((string) ($tanggalLahirRaw ?? '')) !== '' && $tanggalLahir === null) {
                $skippedCount++;
                $errors[] = "Baris {$line}: Tanggal Lahir harus menggunakan format tanggal yang valid.";
                continue;
            }

            $kelas = $this->normalizeKelasValue($item['kelas'] ?? null);
            if ($kelas !== null) {
                $kelasToSync[] = $kelas;
            }

            $rowsToAdd[] = [
                'username' => $username,
                'name' => $name,
                'email' => $email,
                'password' => bcrypt($password),
                'kelas' => $kelas,
                'jenis_kelamin' => $jenisKelamin,
                'tanggal_lahir' => $tanggalLahir,
                'agama' => $agama,
                'no_hp' => $noHp,
                'alamat' => $alamat,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $existing[$usernameKey] = true;
            if ($email !== null) {
                $existingEmails[$email] = true;
            }
            if ($noHp !== null) {
                $existingNoHp[$noHp] = true;
            }
            $addedCount++;
        }

        try {
            DB::transaction(function () use ($rowsToAdd, $kelasToSync): void {
                if (empty($rowsToAdd)) {
                    return;
                }

                $this->syncKelasValues($kelasToSync);
                User::query()->insert($rowsToAdd);

                $insertedUsernames = collect($rowsToAdd)
                    ->pluck('username')
                    ->filter()
                    ->values()
                    ->all();

                User::query()
                    ->whereIn('username', $insertedUsernames)
                    ->get()
                    ->each(fn (User $user) => $this->syncSpatieRoleForUser($user, 'wakel'));
            });
        } catch (\Throwable $exception) {
            Log::warning('Import guru gagal diproses.', [
                'exception' => $exception->getMessage(),
                'row_count' => count($rowsToAdd),
            ]);

            return [
                'success' => false,
                'message' => 'Import gagal disimpan. Periksa kembali username, email, nomor HP, dan data kelas pada file.',
            ];
        }

        $message = "Import selesai. Berhasil: {$addedCount}, Duplikat/Gagal: {$skippedCount}";
        if ($addedCount === 0 && $skippedCount > 0) {
            $message .= '. Cek data: Username/Password wajib, email valid, No HP unik, dan tanggal lahir gunakan format tanggal valid.';
        }

        return [
            'success' => true,
            'added' => $addedCount,
            'skipped' => $skippedCount,
            'message' => $message,
            'errors' => array_slice($errors, 0, 10),
        ];
    }


    public function importPiketBulk(array $args, $auth): array
    {
        if (!$this->authHasAnyRole($auth, ['admin', 'kepsek'])) {
            return ['success' => false, 'message' => 'Akses Ditolak: Anda tidak memiliki izin.'];
        }

        $dataArray = $args[0] ?? [];
        $existing = \App\Models\User::query()->pluck('username')->map(fn ($u) => trim((string) $u))->flip();
        $existingEmails = \App\Models\User::query()
            ->whereNotNull('email')
            ->pluck('email')
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->flip();
        $existingNoHp = User::query()
            ->whereNotNull('no_hp')
            ->pluck('no_hp')
            ->map(fn ($noHp) => $this->normalizeOptionalString($noHp))
            ->filter()
            ->flip();
        $rowsToAdd = [];
        $addedCount = 0;
        $skippedCount = 0;
        $kelasToSync = [];

        foreach ($dataArray as $item) {
            $username = isset($item['username']) ? trim((string) $item['username']) : '';
            $name = trim((string) ($item['name'] ?? $item['nama'] ?? ''));
            $email = $this->normalizeEmailValue($item['email'] ?? null);
            $jenisKelamin = $this->normalizeOptionalString($item['jenisKelamin'] ?? $item['jenis_kelamin'] ?? null);
            $tanggalLahirRaw = $item['tanggalLahir'] ?? $item['tanggal_lahir'] ?? null;
            $agama = $this->normalizeOptionalString($item['agama'] ?? null);
            $noHp = $this->normalizeOptionalString($item['noHp'] ?? $item['no_hp'] ?? null);
            $alamat = $this->normalizeOptionalString($item['alamat'] ?? null);
            if ($username === '' || empty($item['password'])) {
                $skippedCount++;
                continue;
            }

            if ($name === '') {
                $name = $username;
            }

            if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skippedCount++;
                continue;
            }

            if (isset($existing[$username])) {
                $skippedCount++;
                continue;
            }

            if ($email !== null && isset($existingEmails[$email])) {
                $skippedCount++;
                continue;
            }

            if ($noHp !== null && isset($existingNoHp[$noHp])) {
                $skippedCount++;
                continue;
            }

            $tanggalLahir = $this->normalizeDateValue($tanggalLahirRaw);
            if (trim((string) ($tanggalLahirRaw ?? '')) !== '' && $tanggalLahir === null) {
                $skippedCount++;
                continue;
            }

            $kelas = $this->normalizeKelasValue($item['kelas'] ?? null);
            if ($kelas !== null) {
                $kelasToSync[] = $kelas;
            }

            $rowsToAdd[] = [
                'username' => $username,
                'name' => $name,
                'email' => $email,
                'password' => bcrypt((string) $item['password']),
                'kelas' => $kelas,
                'jenis_kelamin' => $jenisKelamin,
                'tanggal_lahir' => $tanggalLahir,
                'agama' => $agama,
                'no_hp' => $noHp,
                'alamat' => $alamat,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $existing[$username] = true;
            if ($email !== null) {
                $existingEmails[$email] = true;
            }
            if ($noHp !== null) {
                $existingNoHp[$noHp] = true;
            }
            $addedCount++;
        }

        if (!empty($rowsToAdd)) {
            $this->syncKelasValues($kelasToSync);
            \App\Models\User::query()->insert($rowsToAdd);
            $insertedUsernames = collect($rowsToAdd)
                ->pluck('username')
                ->filter()
                ->values()
                ->all();
            if (!empty($insertedUsernames)) {
                User::query()
                    ->whereIn('username', $insertedUsernames)
                    ->get()
                    ->each(fn (User $user) => $this->syncSpatieRoleForUser($user, 'piket'));
            }
        }

        $message = "Import selesai. Berhasil: {$addedCount}, Duplikat/Gagal: {$skippedCount}";
        if ($addedCount === 0 && $skippedCount > 0) {
            $message .= '. Cek data: Username/Password wajib, email valid, No HP unik, dan tanggal lahir gunakan format tanggal valid.';
        }

        return [
            'success' => true,
            'added' => $addedCount,
            'skipped' => $skippedCount,
            'message' => $message,
        ];
    }

}
