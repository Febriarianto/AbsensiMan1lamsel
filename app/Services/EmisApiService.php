<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmisApiService
{
    protected string $env;
    protected string $publicKey;
    protected string $privateKey;
    protected ?string $statisticNum;

    public function __construct()
    {
        $this->env = config('services.emis.env', 'staging');
        $this->publicKey = config('services.emis.public_key', 'J6lRGV9DS8OWGOEnXMG9ORzaNeRArg89');
        $this->privateKey = config('services.emis.private_key', '0M1ZFxl8fh7jODo1GFTqx1Z2ckaxEgo6');
        $this->statisticNum = config('services.emis.statistic_num','131118010001');
    }

    /**
     * Mendapatkan Base URL berdasarkan tipe data (accounts/institutions/students/personnels/partners)
     */
    protected function getBaseUrl(string $type = 'partners'): string
    {
        if ($this->env === 'production') {
            return "https://api-emis.kemenag.go.id/v1/{$type}";
        }

        // Untuk staging, BPS (partners) tetap menggunakan URL khusus
        if ($type === 'partners') {
            return "https://api-emis.kemenag.go.id/v1/{$type}";
        }

        return "https://api-emis.kemenag.go.id/v1/{$type}";
    }

    /**
     * Mendapatkan token, akan hit API login jika belum ada di cache atau sudah expired
     */
    public function getToken(): ?string
    {
        return Cache::remember('emis_api_token', 14400, function () {
            $response = Http::withoutVerifying()->post($this->getBaseUrl('accounts') . '/partners-login', [
                'public_key' => $this->publicKey,
                'private_key' => $this->privateKey,
            ]);

            if ($response->successful()) {
                return $response->json('results.token');
            }

            Log::error('EMIS API Login Failed', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        });
    }

    /**
     * Helper untuk membuat HTTP request dengan Bearer token
     */
    protected function request()
    {
        $token = $this->getToken();

        return Http::withoutVerifying()
            ->withToken($token)
            ->acceptJson()
            ->timeout(30); // Antisipasi request lambat
    }

    /**
     * Refresh Token (Opsional, karena getToken() menggunakan Cache::remember)
     */
    public function refreshToken(): ?array
    {
        $response = $this->request()->post($this->getBaseUrl('accounts') . '/refresh');

        if ($response->successful()) {
            return $response->json('results');
        }

        return null;
    }

    /**
     * 1. Institution Info
     */
    public function getInstitutionInfo(array $params = []): ?array
    {
        if (isset($params['statistic_num']) === false && $this->statisticNum) {
            $params['statistic_num'] = $this->statisticNum;
        }

        $response = $this->request()->get($this->getBaseUrl('institutions') . '/info', $params);

        return $response->successful() ? $response->json() : null;
    }

    /**
     * 2. Cari Rombel
     */
    public function getCariRombel(array $params = []): ?array
    {
        if (isset($params['statistic_num']) === false && $this->statisticNum) {
            $params['statistic_num'] = $this->statisticNum;
        }

        $response = $this->request()->get($this->getBaseUrl('partners') . '/cari/rombel', $params);

        return $response->successful() ? $response->json() : null;
    }

    /**
     * 3. Institution Assets
     */
    public function getInstitutionAssets(array $params = []): ?array
    {
        if (isset($params['statistic_num']) === false && $this->statisticNum) {
            $params['statistic_num'] = $this->statisticNum;
        }

        $response = $this->request()->get($this->getBaseUrl('partners') . '/institution/assets', $params);

        return $response->successful() ? $response->json() : null;
    }

    /**
     * 4. Student List
     */
    public function getStudentList(array $params = []): ?array
    {
        if (isset($params['statistic_num']) === false && $this->statisticNum) {
            $params['statistic_num'] = $this->statisticNum;
        }

        $response = $this->request()->get($this->getBaseUrl('students') . '/partners/students', $params);

        return $response->successful() ? $response->json() : null;
    }

    /**
     * 5. Personnel List (v1 - Full Detail)
     */
    public function getPersonnelListV1(array $params = []): ?array
    {
        if (isset($params['statistic_num']) === false && $this->statisticNum) {
            $params['statistic_num'] = $this->statisticNum;
        }

        $response = $this->request()->get($this->getBaseUrl('personnels') . '/partners/personnels', $params);

        return $response->successful() ? $response->json() : null;
    }

    /**
     * 6. Personnel List (v2 - Ringkas)
     */
    public function getPersonnelListV2(array $params = []): ?array
    {
        if (isset($params['statistic_num']) === false && $this->statisticNum) {
            $params['statistic_num'] = $this->statisticNum;
        }

        $response = $this->request()->get($this->getBaseUrl('personnels') . '/partners/v2/gtk', $params);

        return $response->successful() ? $response->json() : null;
    }

    /**
     * 7. BPS Dashboard
     */
    public function getBpsDashboard(string $provinceCode, string $academicYear, int $page = 1): ?array
    {
        $response = $this->request()->get($this->getBaseUrl('partners') . '/get-bps', [
            'province_code' => $provinceCode,
            'academic_year' => $academicYear,
            'page' => $page,
        ]);

        return $response->successful() ? $response->json() : null;
    }
}
