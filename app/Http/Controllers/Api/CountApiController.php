<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Siswa;

class CountApiController extends Controller
{
    public function index()
    {
        $total = Siswa::count();

        // $lakiLaki = Siswa::where('jenis_kelamin', 'L')->count();

        // $perempuan = Siswa::where('jenis_kelamin', 'P')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                // 'laki_laki' => $lakiLaki,
                // 'perempuan' => $perempuan,
            ],
        ]);
    }
}
