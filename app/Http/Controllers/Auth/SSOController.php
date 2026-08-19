<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SSOController extends Controller
{
    public function redirectToProvider()
    {
        $baseUrl = rtrim(trim((string) config('services.kemenag_sso.url')), '/');
        $clientId = trim((string) config('services.kemenag_sso.client_id'));

        if ($baseUrl === '' || $clientId === '') {
            return redirect()->route('login')->withErrors([
                'sso' => 'Konfigurasi SSO Kemenag belum lengkap. Hubungi administrator aplikasi.',
            ]);
        }

        // URL redirect ke halaman login SSO Kemenag
        $url = $baseUrl.'/auth/signin?'.http_build_query(['appid' => $clientId]);

        return redirect()->away($url);
    }

    public function handleProviderCallback(Request $request)
    {
        $token = $request->query('token');

        if (! $token) {
            return redirect('/')->with('error', 'Token tidak ditemukan');
        }

        // Panggil API verify ke SSO Kemenag
        $response = Http::acceptJson()
            ->timeout(max(1, (int) config('services.kemenag_sso.timeout', 15)))
            ->withToken($token)
            ->post(rtrim((string) config('services.kemenag_sso.url'), '/').'/auth/verify');

        if ($response->failed()) {
            abort(403, 'Gagal menghubungi server SSO Kemenag');
        }

        $data = $response->json();

        if (! is_array($data) || (int) ($data['status'] ?? 200) >= 400) {
            Log::warning('Token SSO Kemenag ditolak.', [
                'sso_status' => $data['status'] ?? null,
                'sso_message' => $data['msg'] ?? null,
            ]);

            abort(403, 'Token SSO Kemenag tidak valid atau sudah kedaluwarsa. Silakan login kembali.');
        }

        // Validasi format response
        if (! isset($data['pegawai'])) {
            abort(403, 'Data user tidak valid dari SSO');
        }

        $userData = $data['pegawai'];

        // 🔍 Cari user berdasarkan username/NIP
        $user = User::where('username', $userData['NIP'])->first();

        if (! $user) {
            Log::warning('Akun hasil SSO Kemenag belum terdaftar.', [
                'nip' => $userData['NIP'],
            ]);

            return redirect()->route('login')->withErrors([
                'sso' => 'Akun SSO dengan NIP tersebut belum terdaftar di aplikasi. Silakan hubungi admin.',
            ]);
        }

        // Login user
        Auth::login($user);
        $request->session()->regenerate();

        // Simpan token jika ingin digunakan lagi
        session(['sso_token' => $token]);

        // Log Activity custom
        Log::info('Login melalui SSO Kemenag berhasil.', ['user_id' => $user->id]);

        // Redirect ke dashboard
        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Opsional: redirect ke logout SSO
        return redirect(config('services.kemenag_sso.url').'/auth/signout');
    }
}
