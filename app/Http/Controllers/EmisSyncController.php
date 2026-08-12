<?php

namespace App\Http\Controllers;

use App\Models\Alumni;
use App\Models\Siswa;
use App\Models\EmisSyncLog;
use App\Services\EmisApiService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EmisSyncController extends Controller
{
    protected EmisApiService $emisApi;

    public function __construct(EmisApiService $emisApi)
    {
        $this->emisApi = $emisApi;
    }

    public function index()
    {
        $lastLog = EmisSyncLog::latest()->first();
        return view('admin.emis_sync', compact('lastLog'));
    }

    public function syncStudent(Request $request)
    {
        // Tingkatkan batas waktu eksekusi PHP agar tidak terputus (120 detik)
        set_time_limit(120);

        $page = $request->query('page', 1);

        $response = $this->emisApi->getStudentList([
            'page' => (int) $page
        ]);

        if (!$response || empty($response['success'])) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data dari API EMIS.'
            ], 500);
        }

        $results = $response['results'] ?? [];
        $pagination = $response['metadata']['pagination'] ?? [];
        $totalSynced = 0;

        foreach ($results as $item) {
            $nisn = $item['nisn'] ?? null;
            if (!$nisn) {
                continue; // Skip if no NISN
            }

            // Status from learning_activity
            $statusName = $item['learning_activity']['status_description']['name'] ?? '';
            $isLulus = ($statusName === 'Lulus');

            // Gender mapping (EMIS uses "Laki-laki" / "Perempuan", DB uses "Laki-Laki" / "Perempuan")
            $genderRaw = $item['gender'] ?? '';
            $gender = ($genderRaw === 'Laki-laki') ? 'Laki-Laki' : 'Perempuan';

            if ($isLulus) {
                // Determine graduation year
                $year = date('Y');
                if (isset($item['learning_activity']['updated_at'])) {
                    $year = Carbon::parse($item['learning_activity']['updated_at'])->format('Y');
                }

                // Create or Update Alumni
                Alumni::updateOrCreate(
                    ['nisn' => $nisn],
                    [
                        'nama' => $item['full_name'] ?? '',
                        'jenis_kelamin' => $gender,
                        'kelas_terakhir' => $item['learning_activity']['m_level']['name'] ?? '',
                        'tahun_lulus' => $year,
                        'kontak' => $item['handphone'] ?? '',
                    ]
                );

                // Hapus data dari tabel siswa jika ada
                Siswa::where('nisn', $nisn)->delete();
            } else {
                // Parse full address
                $fullAddress = $item['full_address'] ?? $item['address'] ?? '';

                // Create or Update Siswa
                Siswa::updateOrCreate(
                    ['nisn' => $nisn],
                    [
                        'nama' => $item['full_name'] ?? '',
                        'jenis_kelamin' => $gender,
                        'tanggal_lahir' => $item['birth_date'] ?? null,
                        'agama' => 'Islam', // Default
                        'nama_ayah' => $item['parents']['father_full_name'] ?? '',
                        'nama_ibu' => $item['parents']['mother_full_name'] ?? '',
                        'no_hp' => $item['handphone'] ?? '',
                        'kelas' => $item['learning_activity']['m_level']['name'] ?? '',
                        'alamat' => $fullAddress,
                    ]
                );
            }
            $totalSynced++;
        }

        return response()->json([
            'success' => true,
            'message' => "Berhasil memproses $totalSynced data siswa pada halaman $page.",
            'current_page' => $pagination['current_page'] ?? 1,
            'last_page' => $pagination['last_page'] ?? 1,
            'total' => $pagination['total'] ?? 0,
            'synced' => $totalSynced,
        ]);
    }

    public function logSync(Request $request)
    {
        EmisSyncLog::create([
            'total_synced' => $request->input('total_synced', 0),
            'status' => $request->input('status', 'success'),
            'notes' => $request->input('notes'),
        ]);

        return response()->json(['success' => true]);
    }
}

