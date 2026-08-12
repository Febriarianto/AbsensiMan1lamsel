<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionController extends Controller
{
    protected array $roleLabels = [
        'super-admin' => 'Superadmin',
        'admin' => 'Admin',
        'bendahara' => 'Bendahara',
        'kamad' => 'Kepala Madrasah',
        'waka' => 'Waka',
        'wakel' => 'Guru | Walikelas',
        'piket' => 'Guru | Piket',
        'siswa' => 'Siswa',
    ];

    protected array $protectedRoles = [
        'super-admin',
        'admin',
        'bendahara',
        'kamad',
        'waka',
        'wakel',
        'piket',
        'siswa',
    ];

    protected array $manageableUserRoles = [
        'admin',
        'bendahara',
        'kamad',
        'waka',
    ];

    protected array $permissionGroupLabels = [
        'dashboard' => 'Dashboard & Monitoring',
        'master' => 'Data Master',
        'absensi' => 'Absensi',
        'akademik' => 'Akademik & Pembelajaran',
        'laporan' => 'Laporan',
        'tabungan' => 'Tabungan Siswa',
        'pelanggaran' => 'Poin Pelanggaran',
        'notifikasi' => 'Notifikasi',
        'pengaturan' => 'Pengaturan Sistem',
        'lainnya' => 'Lainnya',
    ];

    protected array $permissionMetadata = [
        'dashboard.view' => ['label' => 'Lihat Dashboard', 'description' => 'Boleh membuka halaman dashboard utama.', 'group' => 'dashboard'],
        'monitoring.view' => ['label' => 'Lihat Monitoring Kehadiran', 'description' => 'Boleh memantau kehadiran siswa secara realtime.', 'group' => 'dashboard'],
        'siswa.view' => ['label' => 'Lihat Data Siswa', 'description' => 'Boleh membuka dan melihat daftar siswa.', 'group' => 'master'],
        'alumni.view' => ['label' => 'Lihat Data Alumni', 'description' => 'Boleh membuka dan melihat daftar alumni.', 'group' => 'master'],
        'alumni.manage' => ['label' => 'Kelola Data Alumni', 'description' => 'Boleh restore atau menghapus data alumni.', 'group' => 'master'],
        'guru.view' => ['label' => 'Lihat Data Guru', 'description' => 'Boleh membuka dan melihat daftar guru.', 'group' => 'master'],
        'piket.view' => ['label' => 'Lihat Data Piket', 'description' => 'Boleh membuka dan melihat data petugas piket.', 'group' => 'master'],
        'absen.manage' => ['label' => 'Kelola Jadwal Libur', 'description' => 'Boleh mengatur hari libur dan jadwal absensi.', 'group' => 'absensi'],
        'scanner.use' => ['label' => 'Gunakan Scanner Absensi', 'description' => 'Boleh memakai halaman scan absensi.', 'group' => 'absensi'],
        'kartu-absensi.manage' => ['label' => 'Kelola Kartu Absensi', 'description' => 'Boleh menautkan dan mengatur kartu absensi siswa.', 'group' => 'absensi'],
        'kartu-siswa.view' => ['label' => 'Lihat Kartu Siswa', 'description' => 'Boleh membuka dan mencetak kartu siswa.', 'group' => 'absensi'],
        'izin-sakit.request' => ['label' => 'Ajukan Izin / Sakit', 'description' => 'Boleh membuat pengajuan izin atau sakit.', 'group' => 'absensi'],
        'izin-sakit.approve' => ['label' => 'Setujui Izin / Sakit', 'description' => 'Boleh menyetujui pengajuan izin atau sakit.', 'group' => 'absensi'],
        'izin-sakit.manage' => ['label' => 'Kelola Izin / Sakit', 'description' => 'Boleh mengelola seluruh data izin dan sakit.', 'group' => 'absensi'],
        'kelas.manage' => ['label' => 'Kelola Kelas', 'description' => 'Boleh menambah, mengubah, dan menghapus kelas.', 'group' => 'akademik'],
        'jadwal-pelajaran.manage' => ['label' => 'Kelola Jadwal Pelajaran', 'description' => 'Boleh mengatur jadwal pelajaran.', 'group' => 'akademik'],
        'jurnal-mengajar.manage' => ['label' => 'Kelola Jurnal Mengajar', 'description' => 'Boleh mengisi dan mengelola jurnal mengajar.', 'group' => 'akademik'],
        'kenaikan-kelas.manage' => ['label' => 'Kelola Kenaikan Kelas', 'description' => 'Boleh memproses kenaikan kelas dan kelulusan.', 'group' => 'akademik'],
        'arsip.manage' => ['label' => 'Kelola Arsip', 'description' => 'Boleh membuka dan mengelola arsip data.', 'group' => 'akademik'],
        'rekap-bulanan.view' => ['label' => 'Lihat Rekap Bulanan', 'description' => 'Boleh membuka laporan rekap bulanan.', 'group' => 'laporan'],
        'rekap-tahunan.view' => ['label' => 'Lihat Rekap Tahunan', 'description' => 'Boleh membuka laporan rekap tahunan.', 'group' => 'laporan'],
        'rekap-absensi.view' => ['label' => 'Lihat Laporan Absensi', 'description' => 'Boleh membuka laporan absensi harian.', 'group' => 'laporan'],
        'rekap-absensi-pelajaran.view' => ['label' => 'Lihat Laporan Absensi Pelajaran', 'description' => 'Boleh membuka laporan absensi per pelajaran.', 'group' => 'laporan'],
        'tabungan-siswa.view' => ['label' => 'Lihat Tabungan Siswa', 'description' => 'Boleh membuka data tabungan siswa.', 'group' => 'tabungan'],
        'tabungan-siswa.manage' => ['label' => 'Kelola Transaksi Tabungan Siswa', 'description' => 'Boleh menambah dan mengubah transaksi tabungan.', 'group' => 'tabungan'],
        'tabungan-siswa.report' => ['label' => 'Lihat Laporan Tabungan Siswa', 'description' => 'Boleh membuka laporan tabungan siswa.', 'group' => 'tabungan'],
        'tabungan-siswa.jenis.manage' => ['label' => 'Kelola Jenis Transaksi Tabungan', 'description' => 'Boleh mengatur jenis transaksi tabungan.', 'group' => 'tabungan'],
        'tabungan-siswa.self.view' => ['label' => 'Lihat Tabungan Sendiri', 'description' => 'Siswa hanya boleh melihat tabungannya sendiri.', 'group' => 'tabungan'],
        'poin-pelanggaran.view' => ['label' => 'Lihat Poin Pelanggaran', 'description' => 'Boleh membuka data poin pelanggaran siswa.', 'group' => 'pelanggaran'],
        'poin-pelanggaran.manage' => ['label' => 'Kelola Poin Pelanggaran', 'description' => 'Boleh menambah dan mengubah poin pelanggaran.', 'group' => 'pelanggaran'],
        'poin-pelanggaran.self.view' => ['label' => 'Lihat Poin Pelanggaran Sendiri', 'description' => 'Siswa hanya boleh melihat poin pelanggarannya sendiri.', 'group' => 'pelanggaran'],
        'notifications.send' => ['label' => 'Kirim Notifikasi', 'description' => 'Boleh mengirim notifikasi resmi ke siswa.', 'group' => 'notifikasi'],
        'settings.roles.manage' => ['label' => 'Kelola Role & Permission', 'description' => 'Boleh mengatur hak akses setiap role.', 'group' => 'pengaturan'],
        'settings.users.manage' => ['label' => 'Kelola Data User', 'description' => 'Boleh menambah dan mengatur akun user.', 'group' => 'pengaturan'],
        'settings.general.manage' => ['label' => 'Kelola Pengaturan Umum', 'description' => 'Boleh mengubah identitas dan pengaturan umum aplikasi.', 'group' => 'pengaturan'],
        'settings.devices.manage' => ['label' => 'Kelola Pengaturan Perangkat', 'description' => 'Boleh mengatur perangkat dan integrasi terkait.', 'group' => 'pengaturan'],
        'settings.notifications.manage' => ['label' => 'Kelola Pengaturan Notifikasi', 'description' => 'Boleh mengatur template dan channel notifikasi.', 'group' => 'pengaturan'],
        'settings.backup.manage' => ['label' => 'Kelola Backup Data', 'description' => 'Boleh membuat dan mengelola backup sistem.', 'group' => 'pengaturan'],
        'settings.update.manage' => ['label' => 'Kelola Update Aplikasi', 'description' => 'Boleh menjalankan update aplikasi.', 'group' => 'pengaturan'],
    ];

    public function usersIndex(): View
    {
        $guard = (string) config('auth.defaults.guard', 'web');
        $viewer = auth()->user();
        $viewerManageableRoles = $this->getManageableRolesForViewer($viewer);
        $allowedRoles = $viewerManageableRoles;
        if ($this->isSuperAdmin($viewer)) {
            array_unshift($allowedRoles, 'super-admin');
        }
        $allowedRoles = array_values(array_unique($allowedRoles));
        $users = User::query()
            ->select([
                'id',
                'username',
                'name',
                'email',
                'jenis_kelamin',
                'tanggal_lahir',
                'agama',
                'no_hp',
                'alamat',
                'created_at',
            ])
            ->with(['roles:id,name,guard_name'])
            ->whereHas('roles', function ($query) use ($guard, $allowedRoles) {
                $query
                    ->where('guard_name', $guard)
                    ->whereIn('name', $allowedRoles);
            })
            ->orderBy('username')
            ->get()
            ->sortBy(function (User $user) {
                $roleRank = 9;
                if ($user->hasRole('super-admin')) {
                    $roleRank = 0;
                } elseif ($user->hasRole('admin')) {
                    $roleRank = 1;
                } elseif ($user->hasRole('bendahara')) {
                    $roleRank = 2;
                } elseif ($user->hasRole('kamad')) {
                    $roleRank = 3;
                } elseif ($user->hasRole('waka')) {
                    $roleRank = 4;
                }

                return sprintf('%d_%s', $roleRank, strtolower((string) $user->username));
            })
            ->values();

        return view('pages.user-management', [
            'users' => $users,
            'viewerManageableRoles' => $viewerManageableRoles,
            'viewerCanManageAdminRole' => in_array('admin', $viewerManageableRoles, true),
        ]);
    }

    public function index(): View
    {
        $guard = (string) config('auth.defaults.guard', 'web');
        $roleOrder = $this->protectedRoles;
        $permissions = Permission::query()
            ->where('guard_name', $guard)
            ->orderBy('name')
            ->get(['id', 'name']);

        $roles = Role::query()
            ->where('guard_name', $guard)
            ->whereIn('name', $this->protectedRoles)
            ->with(['permissions:id,name,guard_name'])
            ->get(['id', 'name', 'guard_name'])
            ->sortBy(function (Role $role) use ($roleOrder) {
                $name = strtolower(trim((string) $role->name));
                $index = array_search($name, $roleOrder, true);
                $rank = $index === false ? 99 : (int) $index;

                return sprintf('%02d_%s', $rank, $name);
            })
            ->values();

        return view('pages.role-permission', [
            'roles' => $roles,
            'permissionGroups' => $this->buildPermissionGroups($permissions),
            'protectedRoles' => $this->protectedRoles,
            'roleLabels' => $this->roleLabels,
        ]);
    }

    public function storeRole(Request $request): RedirectResponse|JsonResponse
    {
        return $this->errorResponse($request, 'Tambah role dinonaktifkan. Gunakan role sistem yang tersedia.', [
            'role' => 'Tambah role dinonaktifkan.',
        ], 403);
    }

    public function syncPermissions(Request $request, Role $role): RedirectResponse|JsonResponse
    {
        $guard = (string) config('auth.defaults.guard', 'web');
        if ($role->guard_name !== $guard) {
            abort(404);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [
                'string',
                Rule::exists('permissions', 'name')->where(fn ($query) => $query->where('guard_name', $guard)),
            ],
        ]);

        $permissions = collect($validated['permissions'] ?? [])
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->values()
            ->all();

        $allowedPermissions = Permission::query()
            ->where('guard_name', $guard)
            ->whereIn('name', $permissions)
            ->get();

        $role->syncPermissions($allowedPermissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $this->successResponse($request, 'Permission role "' . $role->name . '" berhasil diperbarui.');
    }

    public function destroyRole(Request $request, Role $role): RedirectResponse|JsonResponse
    {
        $guard = (string) config('auth.defaults.guard', 'web');
        if ($role->guard_name !== $guard) {
            abort(404);
        }

        if (in_array(strtolower($role->name), $this->protectedRoles, true)) {
            return $this->errorResponse($request, 'Role bawaan sistem tidak bisa dihapus.', [
                'role' => 'Role bawaan sistem tidak bisa dihapus.',
            ]);
        }

        $role->delete();

        return $this->successResponse($request, 'Role berhasil dihapus.');
    }

    public function storeAdminUser(Request $request): RedirectResponse|JsonResponse
    {
        $viewer = $request->user();
        $allowedRoles = $this->getManageableRolesForViewer($viewer);
        if (count($allowedRoles) === 0) {
            return $this->errorResponse($request, 'Anda tidak memiliki akses untuk mengelola user.', [
                'role' => 'Anda tidak memiliki akses untuk mengelola user.',
            ], 403);
        }

        $validated = $request->validate([
            'role' => ['required', 'in:' . implode(',', $allowedRoles)],
            'username' => ['required', 'string', 'max:60', 'alpha_dash', 'unique:users,username'],
            'name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:120', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'jenis_kelamin' => ['nullable', 'in:Laki-laki,Perempuan'],
            'tanggal_lahir' => ['nullable', 'date'],
            'agama' => ['nullable', 'string', 'max:50'],
            'no_hp' => ['nullable', 'string', 'max:30', 'unique:users,no_hp'],
            'alamat' => ['nullable', 'string', 'max:1000'],
        ], [
            'no_hp.unique' => 'No HP sudah digunakan.',
        ]);

        $managedRole = strtolower(trim((string) $validated['role']));
        $username = trim((string) $validated['username']);
        $name = trim((string) ($validated['name'] ?? ''));
        $email = trim((string) ($validated['email'] ?? ''));

        $user = User::query()->create([
            'username' => $username,
            'name' => $name !== '' ? $name : $username,
            'email' => $email !== '' ? strtolower($email) : null,
            'password' => Hash::make((string) $validated['password']),
            'kelas' => null,
            'jenis_kelamin' => $this->nullableString($validated['jenis_kelamin'] ?? null),
            'tanggal_lahir' => $this->nullableString($validated['tanggal_lahir'] ?? null),
            'agama' => $this->nullableString($validated['agama'] ?? null),
            'no_hp' => $this->nullableString($validated['no_hp'] ?? null),
            'alamat' => $this->nullableString($validated['alamat'] ?? null),
        ]);
        $user->syncRoles([$managedRole]);

        return $this->successResponse($request, 'User ' . $managedRole . ' berhasil ditambahkan.');
    }

    public function updateAdminUser(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $viewer = $request->user();
        if ($user->hasRole('super-admin')) {
            return $this->errorResponse($request, 'Akun ini tidak bisa diubah dari halaman ini.', [
                'user' => 'Akun ini tidak bisa diubah dari halaman ini.',
            ], 403);
        }

        $targetManagedRole = $this->resolveManagedRole($user);
        if ($targetManagedRole === null) {
            return $this->errorResponse($request, 'Akun ini tidak bisa diubah dari halaman ini.', [
                'user' => 'Akun ini tidak bisa diubah dari halaman ini.',
            ], 403);
        }

        if ($targetManagedRole === 'admin' && !$this->isSuperAdmin($viewer)) {
            return $this->errorResponse($request, 'Akun admin hanya bisa dikelola super-admin.', [
                'user' => 'Akun admin hanya bisa dikelola super-admin.',
            ], 403);
        }

        $allowedRoles = $this->getManageableRolesForViewer($viewer);
        if (count($allowedRoles) === 0) {
            return $this->errorResponse($request, 'Anda tidak memiliki akses untuk mengelola user.', [
                'role' => 'Anda tidak memiliki akses untuk mengelola user.',
            ], 403);
        }

        $validated = $request->validate([
            'role' => ['required', 'in:' . implode(',', $allowedRoles)],
            'username' => ['required', 'string', 'max:60', 'alpha_dash', 'unique:users,username,' . $user->id],
            'name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:120', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:6'],
            'jenis_kelamin' => ['nullable', 'in:Laki-laki,Perempuan'],
            'tanggal_lahir' => ['nullable', 'date'],
            'agama' => ['nullable', 'string', 'max:50'],
            'no_hp' => ['nullable', 'string', 'max:30', 'unique:users,no_hp,' . $user->id],
            'alamat' => ['nullable', 'string', 'max:1000'],
        ], [
            'no_hp.unique' => 'No HP sudah digunakan.',
        ]);

        $managedRole = strtolower(trim((string) $validated['role']));
        $username = trim((string) $validated['username']);
        $name = trim((string) ($validated['name'] ?? ''));
        $email = trim((string) ($validated['email'] ?? ''));

        $payload = [
            'username' => $username,
            'name' => $name !== '' ? $name : $username,
            'email' => $email !== '' ? strtolower($email) : null,
            'kelas' => null,
            'jenis_kelamin' => $this->nullableString($validated['jenis_kelamin'] ?? null),
            'tanggal_lahir' => $this->nullableString($validated['tanggal_lahir'] ?? null),
            'agama' => $this->nullableString($validated['agama'] ?? null),
            'no_hp' => $this->nullableString($validated['no_hp'] ?? null),
            'alamat' => $this->nullableString($validated['alamat'] ?? null),
        ];
        if (!empty($validated['password'])) {
            $payload['password'] = Hash::make((string) $validated['password']);
        }

        $user->update($payload);
        $user->syncRoles([$managedRole]);

        return $this->successResponse($request, 'User ' . $managedRole . ' berhasil diperbarui.');
    }

    public function destroyAdminUser(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $viewer = $request->user();
        $targetManagedRole = $this->resolveManagedRole($user);
        if ($user->hasRole('super-admin') || $targetManagedRole === null) {
            return $this->errorResponse($request, 'Akun ini tidak bisa dihapus dari halaman ini.', [
                'user' => 'Akun ini tidak bisa dihapus dari halaman ini.',
            ], 403);
        }

        if ($viewer && (int) $viewer->id === (int) $user->id) {
            return $this->errorResponse($request, 'User yang sedang login tidak bisa dihapus.', [
                'user' => 'User yang sedang login tidak bisa dihapus.',
            ], 422);
        }

        if ($targetManagedRole === 'admin' && !$this->isSuperAdmin($viewer)) {
            return $this->errorResponse($request, 'Akun admin hanya bisa dikelola super-admin.', [
                'user' => 'Akun admin hanya bisa dikelola super-admin.',
            ], 403);
        }

        if ($targetManagedRole === 'admin' && $this->countUsersByManagedRole('admin') <= 1) {
            return $this->errorResponse($request, 'User admin terakhir tidak bisa dihapus.', [
                'user' => 'User admin terakhir tidak bisa dihapus.',
            ], 422);
        }

        $user->delete();

        return $this->successResponse($request, 'User berhasil dihapus.');
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Permission>  $permissions
     * @return array<int, array{key:string,label:string,permissions:array<int, array{name:string,label:string,description:string}>}>
     */
    protected function buildPermissionGroups($permissions): array
    {
        $grouped = [];

        foreach ($permissions as $permission) {
            $metadata = $this->resolvePermissionMetadata((string) $permission->name);
            $groupKey = $metadata['group'];

            if (!array_key_exists($groupKey, $grouped)) {
                $grouped[$groupKey] = [
                    'key' => $groupKey,
                    'label' => $this->permissionGroupLabels[$groupKey] ?? 'Lainnya',
                    'permissions' => [],
                ];
            }

            $grouped[$groupKey]['permissions'][] = [
                'name' => (string) $permission->name,
                'label' => $metadata['label'],
                'description' => $metadata['description'],
            ];
        }

        $orderedGroups = [];
        foreach (array_keys($this->permissionGroupLabels) as $groupKey) {
            if (array_key_exists($groupKey, $grouped)) {
                $orderedGroups[] = $grouped[$groupKey];
                unset($grouped[$groupKey]);
            }
        }

        foreach ($grouped as $group) {
            $orderedGroups[] = $group;
        }

        return $orderedGroups;
    }

    /**
     * @return array{label:string,description:string,group:string}
     */
    protected function resolvePermissionMetadata(string $permissionName): array
    {
        if (array_key_exists($permissionName, $this->permissionMetadata)) {
            return $this->permissionMetadata[$permissionName];
        }

        return [
            'label' => $this->humanizePermissionName($permissionName),
            'description' => 'Permission tambahan sistem.',
            'group' => 'lainnya',
        ];
    }

    protected function humanizePermissionName(string $permissionName): string
    {
        $segments = array_values(array_filter(explode('.', trim($permissionName))));
        if ($segments === []) {
            return $permissionName;
        }

        $action = array_pop($segments);
        $resource = implode(' ', $segments);
        $resource = str_replace(['-', '.'], ' ', $resource);
        $resource = ucwords($resource);

        $actionLabels = [
            'view' => 'Lihat',
            'manage' => 'Kelola',
            'use' => 'Gunakan',
            'request' => 'Ajukan',
            'approve' => 'Setujui',
            'report' => 'Lihat Laporan',
        ];

        $actionLabel = $actionLabels[strtolower($action)] ?? ucwords(str_replace(['-', '_'], ' ', $action));

        return trim($actionLabel . ' ' . $resource);
    }

    protected function nullableString($value): ?string
    {
        $text = trim((string) ($value ?? ''));
        return $text === '' ? null : $text;
    }

    protected function resolveManagedRole(User $user): ?string
    {
        foreach ($this->manageableUserRoles as $roleName) {
            if ($user->hasRole($roleName)) {
                return $roleName;
            }
        }

        return null;
    }

    protected function getManageableRolesForViewer(?User $viewer): array
    {
        $roles = $this->manageableUserRoles;
        if (!$this->isSuperAdmin($viewer)) {
            $roles = array_values(array_filter(
                $roles,
                static fn (string $roleName) => $roleName !== 'admin'
            ));
        }

        return array_values(array_unique($roles));
    }

    protected function isSuperAdmin(?User $user): bool
    {
        return $user ? $user->hasRole('super-admin') : false;
    }

    protected function countUsersByManagedRole(string $roleName): int
    {
        $guard = (string) config('auth.defaults.guard', 'web');

        return User::query()
            ->whereHas('roles', function ($query) use ($guard, $roleName) {
                $query
                    ->where('guard_name', $guard)
                    ->where('name', $roleName);
            })
            ->count();
    }

    protected function isJsonRequest(Request $request): bool
    {
        if ($request->expectsJson() || $request->ajax()) {
            return true;
        }

        $accept = strtolower((string) $request->header('Accept', ''));
        return str_contains($accept, 'application/json');
    }

    protected function successResponse(Request $request, string $message): RedirectResponse|JsonResponse
    {
        if ($this->isJsonRequest($request)) {
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    protected function errorResponse(Request $request, string $message, array $errors = [], int $status = 422): RedirectResponse|JsonResponse
    {
        if ($this->isJsonRequest($request)) {
            $payload = [
                'success' => false,
                'message' => $message,
            ];
            if (!empty($errors)) {
                $payload['errors'] = $errors;
            }

            return response()->json($payload, $status);
        }

        if (!empty($errors)) {
            return back()->withErrors($errors)->withInput();
        }

        return back()->withErrors(['error' => $message])->withInput();
    }
}
