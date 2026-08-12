<?php

namespace App\Http\Controllers;

use App\Models\TabunganSiswaAccount;
use App\Models\TabunganSiswaTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $role = $user
            ? strtolower((string) ($user->getRoleNames()->first() ?? ''))
            : null;

        if (in_array($role, ['super-admin', 'admin', 'kepsek', 'wakasek'], true)) {
            return view('pages.dashboard-admin');
        }

        if ($role === 'bendahara') {
            return view('pages.dashboard-bendahara', $this->bendaharaDashboardPayload());
        }

        if (in_array($role, ['wakel', 'piket'], true)) {
            return view('pages.dashboard-guru');
        }

        return view('pages.dashboard-siswa');
    }

    protected function bendaharaDashboardPayload(): array
    {
        $accounts = TabunganSiswaAccount::query()
            ->with([
                'siswa:id,nama,kelas',
                'jenisTabungan:id,nama',
            ])
            ->get([
                'id',
                'siswa_id',
                'jenis_tabungan_id',
                'nomor_rekening',
                'saldo_cached',
                'is_active',
                'opened_at',
            ]);

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $monthlyTransactions = TabunganSiswaTransaction::query()
            ->with([
                'account.siswa:id,nama,kelas',
                'account.jenisTabungan:id,nama',
            ])
            ->whereBetween('transacted_at', [$monthStart, $monthEnd])
            ->get([
                'id',
                'account_id',
                'transacted_at',
                'jenis_transaksi',
                'nominal',
                'saldo_sesudah',
                'nomor_bukti',
            ]);

        $recentTransactions = TabunganSiswaTransaction::query()
            ->with([
                'account.siswa:id,nama,kelas',
                'account.jenisTabungan:id,nama',
            ])
            ->orderByDesc('transacted_at')
            ->orderByDesc('id')
            ->limit(6)
            ->get([
                'id',
                'account_id',
                'transacted_at',
                'jenis_transaksi',
                'nominal',
                'saldo_sesudah',
                'nomor_bukti',
            ]);

        $incomingTypes = [
            TabunganSiswaTransaction::TYPE_SETORAN,
            TabunganSiswaTransaction::TYPE_PENYESUAIAN_MASUK,
        ];
        $outgoingTypes = [
            TabunganSiswaTransaction::TYPE_PENARIKAN,
            TabunganSiswaTransaction::TYPE_PENYESUAIAN_KELUAR,
        ];

        $typeChart = $accounts
            ->groupBy(fn (TabunganSiswaAccount $account) => (int) ($account->jenis_tabungan_id ?? 0))
            ->map(function ($rows, $typeId) {
                $first = $rows->first();
                $label = trim((string) ($first?->jenisTabungan?->nama ?? 'Tanpa Jenis'));

                return [
                    'id' => (int) $typeId,
                    'label' => $label !== '' ? $label : 'Tanpa Jenis',
                    'value' => (int) $rows->sum('saldo_cached'),
                    'account_count' => (int) $rows->count(),
                ];
            })
            ->sortByDesc('value')
            ->values()
            ->all();

        $classChart = $accounts
            ->groupBy(function (TabunganSiswaAccount $account) {
                $kelas = trim((string) ($account->siswa?->kelas ?? ''));

                return $kelas !== '' ? $kelas : 'Tanpa Kelas';
            })
            ->map(function ($rows, $kelas) {
                return [
                    'label' => (string) $kelas,
                    'value' => (int) $rows->sum('saldo_cached'),
                    'account_count' => (int) $rows->count(),
                    'student_count' => (int) $rows->pluck('siswa_id')->filter()->unique()->count(),
                ];
            })
            ->sortByDesc('value')
            ->values()
            ->take(6)
            ->all();

        $statusChart = [
            [
                'key' => 'active',
                'label' => 'Aktif',
                'value' => (int) $accounts->where('is_active', true)->count(),
            ],
            [
                'key' => 'inactive',
                'label' => 'Nonaktif',
                'value' => (int) $accounts->where('is_active', false)->count(),
            ],
        ];

        return [
            'bendaharaDashboard' => [
                'period' => [
                    'label' => $monthStart->locale('id')->translatedFormat('F Y'),
                ],
                'summary' => [
                    'saldo_total' => (int) $accounts->sum('saldo_cached'),
                    'account_count' => (int) $accounts->count(),
                    'active_count' => (int) $accounts->where('is_active', true)->count(),
                    'inactive_count' => (int) $accounts->where('is_active', false)->count(),
                    'student_count' => (int) $accounts->pluck('siswa_id')->filter()->unique()->count(),
                    'monthly_transaction_count' => (int) $monthlyTransactions->count(),
                ],
                'monthly_summary' => [
                    'setoran_total' => (int) $monthlyTransactions
                        ->whereIn('jenis_transaksi', $incomingTypes)
                        ->sum('nominal'),
                    'penarikan_total' => (int) $monthlyTransactions
                        ->whereIn('jenis_transaksi', $outgoingTypes)
                        ->sum('nominal'),
                    'mutasi_bersih' => (int) $monthlyTransactions->sum(
                        fn (TabunganSiswaTransaction $transaction) => $transaction->signedAmount()
                    ),
                ],
                'type_chart' => $typeChart,
                'class_chart' => $classChart,
                'status_chart' => $statusChart,
                'recent_transactions' => $recentTransactions
                    ->map(function (TabunganSiswaTransaction $transaction) {
                        return [
                            'id' => (int) $transaction->id,
                            'nomor_bukti' => (string) ($transaction->nomor_bukti ?? '-'),
                            'transacted_at' => $transaction->transacted_at?->format('d M Y H:i') ?? '-',
                            'jenis_transaksi' => (string) $transaction->jenis_transaksi,
                            'jenis_transaksi_label' => $this->bendaharaTransactionTypeLabel((string) $transaction->jenis_transaksi),
                            'nominal' => (int) $transaction->nominal,
                            'signed_nominal' => (int) $transaction->signedAmount(),
                            'saldo_sesudah' => (int) $transaction->saldo_sesudah,
                            'siswa_nama' => (string) ($transaction->account?->siswa?->nama ?? '-'),
                            'kelas' => (string) ($transaction->account?->siswa?->kelas ?? '-'),
                            'jenis_tabungan' => (string) ($transaction->account?->jenisTabungan?->nama ?? '-'),
                        ];
                    })
                    ->values()
                    ->all(),
            ],
        ];
    }

    protected function bendaharaTransactionTypeLabel(string $type): string
    {
        return match ($type) {
            TabunganSiswaTransaction::TYPE_SETORAN => 'Setoran',
            TabunganSiswaTransaction::TYPE_PENARIKAN => 'Penarikan',
            TabunganSiswaTransaction::TYPE_PENYESUAIAN_MASUK => 'Penyesuaian Masuk',
            TabunganSiswaTransaction::TYPE_PENYESUAIAN_KELUAR => 'Penyesuaian Keluar',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }
}
