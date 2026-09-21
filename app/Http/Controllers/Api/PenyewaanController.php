<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Penyewaan;
use App\Models\PenyewaanDetail;
use App\Models\Alat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PenyewaanController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    | GET /api/penyewaan
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        try {

            $perPage = (int) $request->get('per_page', 5);
            $search = trim($request->get('search', ''));

            if ($perPage < 1) {
                $perPage = 5;
            }

            if ($perPage > 100) {
                $perPage = 100;
            }

            $query = Penyewaan::with([
                'pelanggan',
                'detail.alat'
            ]);

            if ($search !== '') {

                $query->where(function ($q) use ($search) {

                    $q->where(
                        'penyewaan_id',
                        'like',
                        "%{$search}%"
                    )

                        ->orWhere(
                            'penyewaan_tglsewa',
                            'like',
                            "%{$search}%"
                        )

                        ->orWhere(
                            'penyewaan_tglkembali',
                            'like',
                            "%{$search}%"
                        )

                        ->orWhere(
                            'penyewaan_sttspembayaran',
                            'like',
                            "%{$search}%"
                        )

                        ->orWhere(
                            'penyewaan_sttskembali',
                            'like',
                            "%{$search}%"
                        )

                        ->orWhereHas('pelanggan', function ($pelanggan) use ($search) {

                            $pelanggan->where(
                                'pelanggan_nama',
                                'like',
                                "%{$search}%"
                            );
                        });
                });
            }

            $penyewaan = $query
                ->orderBy('penyewaan_id', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Berhasil mengambil data penyewaan.',
                'data' => $penyewaan->items(),

                'pagination' => [
                    'current_page' => $penyewaan->currentPage(),
                    'last_page' => $penyewaan->lastPage(),
                    'per_page' => $penyewaan->perPage(),
                    'total' => $penyewaan->total(),
                    'from' => $penyewaan->firstItem(),
                    'to' => $penyewaan->lastItem(),
                ]

            ], 200);
        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data penyewaan.',
                'data' => null,
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | SHOW DETAIL PENYEWAAN ADMIN
    |--------------------------------------------------------------------------
    | GET /api/penyewaan/{id}
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        try {

            $penyewaan = Penyewaan::with([
                'pelanggan',
                'detail.alat'
            ])->find($id);

            if (!$penyewaan) {

                return response()->json([
                    'success' => false,
                    'message' => 'Data penyewaan tidak ditemukan.',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Detail penyewaan berhasil diambil.',
                'data' => $penyewaan
            ], 200);
        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail penyewaan.',
                'data' => null,
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /*
|--------------------------------------------------------------------------
| STORE
|--------------------------------------------------------------------------
| POST /api/penyewaan
|--------------------------------------------------------------------------
*/

    public function store(Request $request)
    {
        $pelangganId = $request->input('penyewaan_pelanggan_id');

        if (!$pelangganId) {

            $pelanggan = auth('pelanggan')->user();

            if (!$pelanggan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pelanggan belum login.',
                    'data' => null,
                ], 401);
            }

            $pelangganId = $pelanggan->pelanggan_id;
        }

        $tanggalSewa =
            $request->input('penyewaan_tglsewa')
            ?? $request->input('tanggal_sewa');

        $tanggalKembali =
            $request->input('penyewaan_tglkembali')
            ?? $request->input('tanggal_kembali');

        /*
    |--------------------------------------------------------------------------
    | 3. VALIDASI
    |--------------------------------------------------------------------------
    */

        $validator = Validator::make([
            'penyewaan_pelanggan_id' => $pelangganId,
            'penyewaan_tglsewa' => $tanggalSewa,
            'penyewaan_tglkembali' => $tanggalKembali,
            'detail' => $request->input('detail'),
        ], [

            'penyewaan_pelanggan_id' => [
                'required',
                'exists:pelanggan,pelanggan_id',
            ],

            'penyewaan_tglsewa' => [
                'required',
                'date',
            ],

            'penyewaan_tglkembali' => [
                'required',
                'date',
                'after_or_equal:penyewaan_tglsewa',
            ],

            'detail' => [
                'required',
                'array',
                'min:1',
            ],

            'detail.*.alat_id' => [
                'required',
                'exists:alat,alat_id',
            ],

            'detail.*.jumlah' => [
                'required',
                'integer',
                'min:1',
            ],
        ]);

        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'data' => null,
                'errors' => $validator->errors(),
            ], 422);
        }

        /*
    |--------------------------------------------------------------------------
    | 4. TRANSAKSI DATABASE
    |--------------------------------------------------------------------------
    */

        DB::beginTransaction();

        try {

            /*
        |--------------------------------------------------------------------------
        | TANGGAL
        |--------------------------------------------------------------------------
        */

            $tglSewa = Carbon::parse($tanggalSewa)->startOfDay();

            $tglKembali = Carbon::parse($tanggalKembali)->startOfDay();

            $lamaSewa =
                $tglSewa->diffInDays($tglKembali) + 1;

            /*
        |--------------------------------------------------------------------------
        | TOTAL HARGA
        |--------------------------------------------------------------------------
        */

            $totalHarga = 0;

            $detailData = [];

            /*
        |--------------------------------------------------------------------------
        | CEK ALAT + STOK + HARGA
        |--------------------------------------------------------------------------
        */

            foreach ($request->detail as $item) {

                $alat = Alat::lockForUpdate()->find(
                    $item['alat_id']
                );

                if (!$alat) {
                    throw new \Exception(
                        'Data alat tidak ditemukan.'
                    );
                }

                $jumlah = (int) $item['jumlah'];

                if ($alat->alat_stok < $jumlah) {
                    throw new \Exception(
                        "Stok alat {$alat->alat_nama} tidak mencukupi. " .
                            "Stok tersedia: {$alat->alat_stok}."
                    );
                }

                $hargaPerHari =
                    (int) $alat->alat_hargaperhari;

                $subtotal =
                    $hargaPerHari *
                    $jumlah *
                    $lamaSewa;

                $totalHarga += $subtotal;

                $detailData[] = [
                    'alat' => $alat,
                    'jumlah' => $jumlah,
                    'harga_perhari' => $hargaPerHari,
                    'subtotal' => $subtotal,
                ];
            }

            /*
        |--------------------------------------------------------------------------
        | BUAT HEADER PENYEWAAN
        |--------------------------------------------------------------------------
        */

            $penyewaan = Penyewaan::create([

                'penyewaan_pelanggan_id' =>
                $pelangganId,

                'penyewaan_tglsewa' =>
                $tglSewa->format('Y-m-d'),

                'penyewaan_tglkembali' =>
                $tglKembali->format('Y-m-d'),

                'penyewaan_sttspembayaran' =>
                $request->input(
                    'penyewaan_sttspembayaran',
                    'Belum Dibayar'
                ),

                'penyewaan_sttskembali' =>
                $request->input(
                    'penyewaan_sttskembali',
                    'Belum Kembali'
                ),

                'penyewaan_totalharga' =>
                $totalHarga,
            ]);

            /*
        |--------------------------------------------------------------------------
        | SIMPAN DETAIL + KURANGI STOK
        |--------------------------------------------------------------------------
        */

            foreach ($detailData as $item) {

                PenyewaanDetail::create([

                    'penyewaan_detail_penyewaan_id' =>
                    $penyewaan->penyewaan_id,

                    'penyewaan_detail_alat_id' =>
                    $item['alat']->alat_id,

                    'penyewaan_detail_jumlah' =>
                    $item['jumlah'],

                    'penyewaan_detail_subharga' =>
                    $item['subtotal'],
                ]);

                $item['alat']->decrement(
                    'alat_stok',
                    $item['jumlah']
                );
            }

            DB::commit();

            $penyewaan->load([
                'pelanggan',
                'detail.alat',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Berhasil membuat penyewaan.',
                'data' => $penyewaan,
            ], 201);
        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat penyewaan.',
                'data' => null,
                'errors' => [
                    'server' => [
                        $e->getMessage(),
                    ],
                ],
            ], 500);
        }
    }


    /*
|--------------------------------------------------------------------------
| UPDATE
|--------------------------------------------------------------------------
| PUT /api/penyewaan/{id}
|--------------------------------------------------------------------------
*/

   public function update(Request $request, $id)
{
    DB::beginTransaction();

    try {

        /*
        |--------------------------------------------------------------------------
        | 1. AMBIL DATA PENYEWAAN
        |--------------------------------------------------------------------------
        */

        $penyewaan = Penyewaan::with('detail')
            ->lockForUpdate()
            ->find($id);

        if (!$penyewaan) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Data penyewaan tidak ditemukan.',
                'data' => null,
            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | 2. NORMALISASI TANGGAL
        |--------------------------------------------------------------------------
        */

        $tanggalSewa =
            $request->input('penyewaan_tglsewa')
            ?? $request->input('tanggal_sewa')
            ?? $penyewaan->penyewaan_tglsewa;

        $tanggalKembali =
            $request->input('penyewaan_tglkembali')
            ?? $request->input('tanggal_kembali')
            ?? $penyewaan->penyewaan_tglkembali;


        /*
        |--------------------------------------------------------------------------
        | 3. VALIDASI REQUEST
        |--------------------------------------------------------------------------
        */

        $validator = Validator::make($request->all(), [

            'penyewaan_pelanggan_id' => [
                'sometimes',
                'required',
                'exists:pelanggan,pelanggan_id',
            ],

            'penyewaan_tglsewa' => [
                'sometimes',
                'required',
                'date',
            ],

            'penyewaan_tglkembali' => [
                'sometimes',
                'required',
                'date',
            ],

            'penyewaan_sttspembayaran' => [
                'sometimes',
                'required',
                'in:Belum Dibayar,Lunas',
            ],

            'penyewaan_sttskembali' => [
                'sometimes',
                'required',
                'in:Belum Kembali,Sudah Kembali',
            ],

            'detail' => [
                'sometimes',
                'required',
                'array',
                'min:1',
            ],

            'detail.*.alat_id' => [
                'required_with:detail',
                'exists:alat,alat_id',
            ],

            'detail.*.jumlah' => [
                'required_with:detail',
                'integer',
                'min:1',
            ],
        ]);


        if ($validator->fails()) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'data' => null,
                'errors' => $validator->errors(),
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | 4. PARSE TANGGAL
        |--------------------------------------------------------------------------
        */

        $tglSewa = Carbon::parse($tanggalSewa)
            ->startOfDay();

        $tglKembali = Carbon::parse($tanggalKembali)
            ->startOfDay();


        /*
        |--------------------------------------------------------------------------
        | 5. CEK URUTAN TANGGAL
        |--------------------------------------------------------------------------
        */

        if ($tglKembali->lt($tglSewa)) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' =>
                    'Tanggal kembali tidak boleh lebih awal dari tanggal sewa.',
                'data' => null,
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | 6. HITUNG DURASI
        |--------------------------------------------------------------------------
        */

        $lamaSewa =
            $tglSewa->diffInDays($tglKembali) + 1;


        /*
        |--------------------------------------------------------------------------
        | 7. STATUS LAMA & BARU
        |--------------------------------------------------------------------------
        */

        $statusKembaliLama =
            $penyewaan->penyewaan_sttskembali;

        $statusKembaliBaru =
            $request->has('penyewaan_sttskembali')
                ? $request->input('penyewaan_sttskembali')
                : $statusKembaliLama;


        /*
        |--------------------------------------------------------------------------
        | 8. UPDATE DATA HEADER
        |--------------------------------------------------------------------------
        */

        $penyewaan->penyewaan_tglsewa =
            $tglSewa->format('Y-m-d');

        $penyewaan->penyewaan_tglkembali =
            $tglKembali->format('Y-m-d');


        if ($request->has('penyewaan_pelanggan_id')) {

            $penyewaan->penyewaan_pelanggan_id =
                $request->input('penyewaan_pelanggan_id');
        }


        if ($request->has('penyewaan_sttspembayaran')) {

            $penyewaan->penyewaan_sttspembayaran =
                $request->input('penyewaan_sttspembayaran');
        }


        /*
        |--------------------------------------------------------------------------
        | 9. VALIDASI STATUS PENGEMBALIAN
        |--------------------------------------------------------------------------
        |
        | Jika admin mengubah menjadi Sudah Kembali,
        | pembayaran harus Lunas.
        |
        */

        if (
            $statusKembaliBaru === 'Sudah Kembali'
            &&
            $penyewaan->penyewaan_sttspembayaran !== 'Lunas'
        ) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' =>
                    'Penyewaan tidak dapat ditandai sudah kembali karena pembayaran belum lunas.',
                'data' => null,
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | 10. TENTUKAN APAKAH STATUS PENGEMBALIAN BERUBAH
        |--------------------------------------------------------------------------
        */

        $statusMenjadiKembali =
            $statusKembaliLama !== 'Sudah Kembali'
            &&
            $statusKembaliBaru === 'Sudah Kembali';

        $statusMenjadiBelumKembali =
            $statusKembaliLama === 'Sudah Kembali'
            &&
            $statusKembaliBaru === 'Belum Kembali';


        /*
        |--------------------------------------------------------------------------
        | 11. UPDATE DETAIL ALAT
        |--------------------------------------------------------------------------
        */

        if ($request->has('detail')) {

            /*
            |----------------------------------------------------------------------
            | Kumpulkan ID alat lama
            |----------------------------------------------------------------------
            */

            $alatLama = [];

            foreach ($penyewaan->detail as $oldDetail) {

                $alatLama[
                    $oldDetail->penyewaan_detail_alat_id
                ] =
                    $oldDetail->penyewaan_detail_jumlah;
            }


            /*
            |----------------------------------------------------------------------
            | Kembalikan stok detail lama
            |----------------------------------------------------------------------
            |
            | Hanya dilakukan apabila penyewaan masih aktif.
            |
            | Jika sudah kembali, stoknya memang sudah berada di inventory.
            |
            */

            if ($statusKembaliLama !== 'Sudah Kembali') {

                foreach ($penyewaan->detail as $oldDetail) {

                    $alat =
                        Alat::lockForUpdate()->find(
                            $oldDetail->penyewaan_detail_alat_id
                        );

                    if ($alat) {

                        $alat->increment(
                            'alat_stok',
                            $oldDetail->penyewaan_detail_jumlah
                        );
                    }
                }
            }


            /*
            |----------------------------------------------------------------------
            | Hapus detail lama
            |----------------------------------------------------------------------
            */

            $penyewaan->detail()->delete();


            /*
            |----------------------------------------------------------------------
            | Buat detail baru
            |----------------------------------------------------------------------
            */

            $totalHarga = 0;

            foreach ($request->input('detail') as $item) {

                $alat =
                    Alat::lockForUpdate()->find(
                        $item['alat_id']
                    );

                if (!$alat) {

                    throw new \Exception(
                        'Data alat tidak ditemukan.'
                    );
                }


                $jumlah =
                    (int) $item['jumlah'];


                /*
                |------------------------------------------------------------------
                | Jika penyewaan masih aktif, cek stok
                |------------------------------------------------------------------
                */

                if ($statusKembaliBaru !== 'Sudah Kembali') {

                    if ($alat->alat_stok < $jumlah) {

                        throw new \Exception(
                            "Stok alat {$alat->alat_nama} tidak mencukupi. " .
                            "Stok tersedia: {$alat->alat_stok}."
                        );
                    }
                }


                /*
                |------------------------------------------------------------------
                | Harga
                |------------------------------------------------------------------
                */

                $hargaPerHari =
                    (int) $alat->alat_hargaperhari;

                $subtotal =
                    $hargaPerHari *
                    $jumlah *
                    $lamaSewa;

                $totalHarga += $subtotal;


                /*
                |------------------------------------------------------------------
                | Simpan detail
                |------------------------------------------------------------------
                */

                PenyewaanDetail::create([

                    'penyewaan_detail_penyewaan_id' =>
                        $penyewaan->penyewaan_id,

                    'penyewaan_detail_alat_id' =>
                        $alat->alat_id,

                    'penyewaan_detail_jumlah' =>
                        $jumlah,

                    'penyewaan_detail_subharga' =>
                        $subtotal,
                ]);


                /*
                |------------------------------------------------------------------
                | Kurangi stok jika masih disewa
                |------------------------------------------------------------------
                */

                if ($statusKembaliBaru !== 'Sudah Kembali') {

                    $alat->decrement(
                        'alat_stok',
                        $jumlah
                    );
                }
            }

            $penyewaan->penyewaan_totalharga =
                $totalHarga;
        }


        /*
        |--------------------------------------------------------------------------
        | 12. DETAIL TIDAK DIKIRIM
        |--------------------------------------------------------------------------
        */

        else {

            $totalHarga = 0;

            foreach ($penyewaan->detail as $detail) {

                $alat =
                    Alat::find(
                        $detail->penyewaan_detail_alat_id
                    );

                if ($alat) {

                    $hargaPerHari =
                        (int) $alat->alat_hargaperhari;

                    $jumlah =
                        (int) $detail->penyewaan_detail_jumlah;

                    $subtotal =
                        $hargaPerHari *
                        $jumlah *
                        $lamaSewa;

                    $detail->penyewaan_detail_subharga =
                        $subtotal;

                    $detail->save();

                    $totalHarga += $subtotal;
                }
            }

            $penyewaan->penyewaan_totalharga =
                $totalHarga;
        }


        /*
        |--------------------------------------------------------------------------
        | 13. UPDATE STATUS PENGEMBALIAN
        |--------------------------------------------------------------------------
        */

        $penyewaan->penyewaan_sttskembali =
            $statusKembaliBaru;


        /*
        |--------------------------------------------------------------------------
        | 14. KELOLA STOK BERDASARKAN PERUBAHAN STATUS
        |--------------------------------------------------------------------------
        */

        /*
        |--------------------------------------------------------------------------
        | BELUM KEMBALI -> SUDAH KEMBALI
        |--------------------------------------------------------------------------
        */

        if ($statusMenjadiKembali) {

            foreach ($penyewaan->detail as $detail) {

                $alat =
                    Alat::lockForUpdate()->find(
                        $detail->penyewaan_detail_alat_id
                    );

                if (!$alat) {

                    throw new \Exception(
                        'Data alat untuk detail penyewaan tidak ditemukan.'
                    );
                }

                $alat->increment(
                    'alat_stok',
                    $detail->penyewaan_detail_jumlah
                );
            }
        }


        /*
        |-------------------------------------------------------------------------- 
        | SUDAH KEMBALI -> BELUM KEMBALI
        |--------------------------------------------------------------------------
        */

        if ($statusMenjadiBelumKembali) {

            foreach ($penyewaan->detail as $detail) {

                $alat =
                    Alat::lockForUpdate()->find(
                        $detail->penyewaan_detail_alat_id
                    );

                if (!$alat) {

                    throw new \Exception(
                        'Data alat untuk detail penyewaan tidak ditemukan.'
                    );
                }

                if (
                    $alat->alat_stok
                    <
                    $detail->penyewaan_detail_jumlah
                ) {

                    throw new \Exception(
                        "Stok alat {$alat->alat_nama} tidak mencukupi."
                    );
                }

                $alat->decrement(
                    'alat_stok',
                    $detail->penyewaan_detail_jumlah
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | 15. SIMPAN
        |--------------------------------------------------------------------------
        */

        $penyewaan->save();


        /*
        |--------------------------------------------------------------------------
        | 16. COMMIT
        |--------------------------------------------------------------------------
        */

        DB::commit();


        /*
        |--------------------------------------------------------------------------
        | 17. LOAD DATA TERBARU
        |--------------------------------------------------------------------------
        */

        $penyewaan->load([
            'pelanggan',
            'detail.alat',
        ]);


        /*
        |--------------------------------------------------------------------------
        | 18. RESPONSE
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,
            'message' =>
                'Data penyewaan berhasil diperbarui.',
            'data' => $penyewaan,
        ], 200);

    } catch (\Throwable $e) {

        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' =>
                'Gagal memperbarui data penyewaan.',
            'data' => null,
            'errors' => [
                'server' => [
                    $e->getMessage(),
                ],
            ],
        ], 500);
    }
}
    /*
|--------------------------------------------------------------------------
| KEMBALIKAN PENYEWAAN
|--------------------------------------------------------------------------
| PUT /api/penyewaan/{id}/kembali
|--------------------------------------------------------------------------
*/

    public function kembalikan($id)
    {
        DB::beginTransaction();

        try {

            /*
        |--------------------------------------------------------------------------
        | 1. CARI PENYEWAAN
        |--------------------------------------------------------------------------
        */

            $penyewaan = Penyewaan::with('detail')
                ->lockForUpdate()
                ->find($id);

            if (!$penyewaan) {

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Data penyewaan tidak ditemukan.',
                    'data' => null,
                ], 404);
            }


            /*
        |--------------------------------------------------------------------------
        | 2. CEK STATUS PEMBAYARAN
        |--------------------------------------------------------------------------
        */

            if ($penyewaan->penyewaan_sttspembayaran !== 'Lunas') {

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Alat belum dapat dikembalikan karena pembayaran belum lunas.',
                    'data' => null,
                ], 422);
            }


            /*
        |--------------------------------------------------------------------------
        | 3. CEK APAKAH SUDAH DIKEMBALIKAN
        |--------------------------------------------------------------------------
        */

            if ($penyewaan->penyewaan_sttskembali === 'Sudah Kembali') {

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Penyewaan ini sudah dikembalikan sebelumnya.',
                    'data' => null,
                ], 422);
            }


            /*
        |--------------------------------------------------------------------------
        | 4. KEMBALIKAN STOK ALAT
        |--------------------------------------------------------------------------
        */

            foreach ($penyewaan->detail as $detail) {

                $alat = Alat::lockForUpdate()->find(
                    $detail->penyewaan_detail_alat_id
                );

                if (!$alat) {
                    throw new \Exception(
                        'Data alat untuk detail penyewaan tidak ditemukan.'
                    );
                }

                $alat->increment(
                    'alat_stok',
                    $detail->penyewaan_detail_jumlah
                );
            }


            /*
        |--------------------------------------------------------------------------
        | 5. UPDATE STATUS PENGEMBALIAN
        |--------------------------------------------------------------------------
        */

            $penyewaan->penyewaan_sttskembali = 'Sudah Kembali';

            $penyewaan->save();


            /*
        |--------------------------------------------------------------------------
        | 6. COMMIT
        |--------------------------------------------------------------------------
        */

            DB::commit();


            /*
        |--------------------------------------------------------------------------
        | 7. LOAD DATA TERBARU
        |--------------------------------------------------------------------------
        */

            $penyewaan->load([
                'pelanggan',
                'detail.alat',
            ]);


            return response()->json([
                'success' => true,
                'message' => 'Alat berhasil dikembalikan dan stok telah diperbarui.',
                'data' => $penyewaan,
            ], 200);
        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengembalikan alat.',
                'data' => null,
                'errors' => [
                    'server' => [
                        $e->getMessage(),
                    ],
                ],
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY
    |--------------------------------------------------------------------------
    | DELETE /api/penyewaan/{id}
    |--------------------------------------------------------------------------
    */

    public function destroy($id)
    {
        DB::beginTransaction();

        try {

            $penyewaan = Penyewaan::with('detail')
                ->lockForUpdate()
                ->find($id);


            if (!$penyewaan) {

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Data penyewaan tidak ditemukan.',
                    'data' => null
                ], 404);
            }


            /*
            |--------------------------------------------------------------------------
            | KEMBALIKAN STOK
            |--------------------------------------------------------------------------
            */

            foreach ($penyewaan->detail as $detail) {

                $alat = Alat::lockForUpdate()->find(
                    $detail->penyewaan_detail_alat_id
                );


                if ($alat) {

                    $alat->increment(
                        'alat_stok',
                        $detail->penyewaan_detail_jumlah
                    );
                }
            }


            /*
            |--------------------------------------------------------------------------
            | HAPUS DETAIL + HEADER
            |--------------------------------------------------------------------------
            */

            $penyewaan->detail()->delete();

            $penyewaan->delete();


            DB::commit();


            return response()->json([
                'success' => true,
                'message' => 'Berhasil menghapus data penyewaan.',
                'data' => null
            ], 200);
        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus penyewaan.',
                'data' => null,
                'errors' => $e->getMessage()
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | RIWAYAT PELANGGAN
    |--------------------------------------------------------------------------
    | GET /api/auth/pelanggan/penyewaan
    |--------------------------------------------------------------------------
    */

    public function riwayatPelanggan()
    {
        try {

            $pelanggan = auth('pelanggan')->user();


            if (!$pelanggan) {

                return response()->json([
                    'success' => false,
                    'message' => 'Pelanggan belum login.',
                    'data' => null
                ], 401);
            }


            $riwayat = Penyewaan::with([
                'detail.alat'
            ])
                ->where(
                    'penyewaan_pelanggan_id',
                    $pelanggan->pelanggan_id
                )
                ->latest('penyewaan_id')
                ->get();


            return response()->json([
                'success' => true,
                'message' => 'Riwayat penyewaan berhasil diambil.',
                'data' => $riwayat
            ], 200);
        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil riwayat penyewaan.',
                'data' => null,
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | DETAIL PENYEWAAN PELANGGAN
    |--------------------------------------------------------------------------
    | GET /api/auth/pelanggan/penyewaan/{id}
    |--------------------------------------------------------------------------
    */

    public function showPelanggan($id)
    {
        try {

            $pelanggan = auth('pelanggan')->user();


            if (!$pelanggan) {

                return response()->json([
                    'success' => false,
                    'message' => 'Pelanggan belum login.',
                    'data' => null
                ], 401);
            }


            $penyewaan = Penyewaan::with([
                'pelanggan',
                'detail.alat'
            ])
                ->where(
                    'penyewaan_pelanggan_id',
                    $pelanggan->pelanggan_id
                )
                ->where(
                    'penyewaan_id',
                    $id
                )
                ->first();


            if (!$penyewaan) {

                return response()->json([
                    'success' => false,
                    'message' => 'Data penyewaan tidak ditemukan.',
                    'data' => null
                ], 404);
            }


            return response()->json([
                'success' => true,
                'message' => 'Detail penyewaan berhasil diambil.',
                'data' => $penyewaan
            ], 200);
        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail penyewaan.',
                'data' => null,
                'errors' => $e->getMessage()
            ], 500);
        }
    }
}
