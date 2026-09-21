<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alat;
use App\Models\Kategori;
use App\Models\Pelanggan;
use App\Models\Penyewaan;

class DashboardController extends Controller
{
    public function index()
    {
        $totalAlat = Alat::count();
        $totalKategori = Kategori::count();
        $totalPelanggan = Pelanggan::count();
        $totalPenyewaan = Penyewaan::count();

        $totalPendapatan = Penyewaan::sum('penyewaan_totalharga');

        $belumKembali = Penyewaan::where(
            'penyewaan_sttskembali',
            'Belum Kembali'
        )->count();

        $sudahKembali = Penyewaan::where(
            'penyewaan_sttskembali',
            'Sudah Kembali'
        )->count();

        $belumBayar = Penyewaan::where(
            'penyewaan_sttspembayaran',
            'Belum Dibayar'
        )->count();

        $lunas = Penyewaan::where(
            'penyewaan_sttspembayaran',
            'Lunas'
        )->count();

        $penyewaanTerbaru = Penyewaan::with('pelanggan')
            ->latest('penyewaan_id')
            ->take(5)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Dashboard berhasil diambil',
            'data' => [

                'total_alat' => $totalAlat,
                'total_kategori' => $totalKategori,
                'total_pelanggan' => $totalPelanggan,
                'total_penyewaan' => $totalPenyewaan,

                'total_pendapatan' => $totalPendapatan,

                'belum_kembali' => $belumKembali,
                'sudah_kembali' => $sudahKembali,

                'belum_bayar' => $belumBayar,
                'lunas' => $lunas,

                'penyewaan_terbaru' => $penyewaanTerbaru
            ]
        ]);
    }
    public function chartPenyewaan()
{
    $data = Penyewaan::selectRaw(
        'MONTH(penyewaan_tglsewa) as bulan,
         COUNT(*) as total'
    )
    ->groupBy('bulan')
    ->orderBy('bulan')
    ->get();


    $bulan = [
        1=>"Jan",
        2=>"Feb",
        3=>"Mar",
        4=>"Apr",
        5=>"Mei",
        6=>"Jun",
        7=>"Jul",
        8=>"Agu",
        9=>"Sep",
        10=>"Okt",
        11=>"Nov",
        12=>"Des",
    ];


    $result = $data->map(function($item) use ($bulan){

        return [

            "bulan" => $bulan[$item->bulan],

            "penyewaan" => $item->total

        ];

    });


    return response()->json($result);
}
}