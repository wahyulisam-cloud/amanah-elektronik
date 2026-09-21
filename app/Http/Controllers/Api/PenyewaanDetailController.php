<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PenyewaanDetail;
use App\Models\Alat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;


class PenyewaanDetailController extends Controller
{

    /**
     * Menampilkan semua detail penyewaan
     */
    public function index()
    {
        try {

            $detail = PenyewaanDetail::with([
                'penyewaan',
                'alat'
            ])->get();


            return response()->json([

                'success' => true,

                'message' => 'Successfully get penyewaan detail data',

                'data' => $detail

            ], 200);
        } catch (\Exception $e) {

            return response()->json([

                'success' => false,

                'message' => 'Internal Server Error',

                'data' => null,

                'errors' => $e->getMessage()

            ], 500);
        }
    }

    /**
     * Tambah detail penyewaan
     */
    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [


            'penyewaan_detail_penyewaan_id'
            => 'required|exists:penyewaan,penyewaan_id',


            'penyewaan_detail_alat_id'
            => 'required|exists:alat,alat_id',


            'penyewaan_detail_jumlah'
            => 'required|integer|min:1',


            'penyewaan_detail_subharga'
            => 'required|numeric|min:0'


        ]);

        if ($validator->fails()) {

            return response()->json([

                'success' => false,

                'message' => 'Gagal menambahkan detail penyewaan!',

                'data' => null,

                'errors' => $validator->errors()

            ], 422);
        }

        DB::beginTransaction();

        try {

            $alat = Alat::find(
                $request->penyewaan_detail_alat_id
            );

            if (!$alat) {


                return response()->json([

                    'success' => false,

                    'message' => 'Data alat tidak ditemukan',

                    'data' => null

                ], 404);
            }

            if (
                $alat->alat_stok
                <
                $request->penyewaan_detail_jumlah
            ) {

                return response()->json([

                    'success' => false,

                    'message' => 'Stok alat tidak mencukupi!',

                    'data' => null

                ], 422);
            }

            $detail = PenyewaanDetail::create([


                'penyewaan_detail_penyewaan_id'
                =>
                $request->penyewaan_detail_penyewaan_id,


                'penyewaan_detail_alat_id'
                =>
                $request->penyewaan_detail_alat_id,


                'penyewaan_detail_jumlah'
                =>
                $request->penyewaan_detail_jumlah,


                'penyewaan_detail_subharga'
                =>
                $request->penyewaan_detail_subharga


            ]);

            $alat->decrement(

                'alat_stok',

                $request->penyewaan_detail_jumlah

            );

            DB::commit();

            return response()->json([

                'success' => true,

                'message' => 'Berhasil menambahkan detail penyewaan',

                'data' => $detail

            ], 201);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([

                'success' => false,

                'message' => 'Internal Server Error',

                'data' => null,

                'errors' => $e->getMessage()

            ], 500);
        }
    }

    /**
     * Detail penyewaan
     */
    public function show($id)
    {

        try {


            $detail = PenyewaanDetail::with([

                'penyewaan',

                'alat'

            ])->find($id);



            if (!$detail) {


                return response()->json([

                    'success' => false,

                    'message' => 'Data detail penyewaan tidak ditemukan',

                    'data' => null

                ], 404);
            }



            return response()->json([

                'success' => true,

                'message' => 'Berhasil mengambil detail penyewaan',

                'data' => $detail

            ], 200);
        } catch (\Exception $e) {


            return response()->json([

                'success' => false,

                'message' => 'Internal Server Error',

                'data' => null,

                'errors' => $e->getMessage()

            ], 500);
        }
    }

    /**
     * Update detail penyewaan
     */
    public function update(Request $request, $id)
    {

        try {

            $detail = PenyewaanDetail::find($id);

            if (!$detail) {

                return response()->json([

                    'success' => false,

                    'message' => 'Data detail penyewaan tidak ditemukan',

                    'data' => null

                ], 404);
            }

            $validator = Validator::make($request->all(), [


                'penyewaan_detail_jumlah'
                =>
                'sometimes|integer|min:1',


                'penyewaan_detail_subharga'
                =>
                'sometimes|numeric|min:0'


            ]);

            if ($validator->fails()) {


                return response()->json([

                    'success' => false,

                    'message' => 'Gagal update detail penyewaan',

                    'data' => null,

                    'errors' => $validator->errors()

                ], 422);
            }

            DB::beginTransaction();

            // jika jumlah berubah

            if ($request->has('penyewaan_detail_jumlah')) {


                $selisih =
                    $request->penyewaan_detail_jumlah
                    -
                    $detail->penyewaan_detail_jumlah;



                $alat = Alat::find(
                    $detail->penyewaan_detail_alat_id
                );



                if ($selisih > 0) {

                    if (
                        $alat->alat_stok < $selisih
                    ) {

                        return response()->json([

                            'success' => false,

                            'message' => 'Stok alat tidak mencukupi',

                            'data' => null

                        ], 422);
                    }


                    $alat->decrement(

                        'alat_stok',

                        $selisih

                    );
                } else {


                    $alat->increment(

                        'alat_stok',

                        abs($selisih)

                    );
                }
            }

            $detail->update(
                $request->all()
            );

            DB::commit();

            return response()->json([

                'success' => true,

                'message' => 'Berhasil update detail penyewaan',

                'data' => $detail

            ], 200);
        } catch (\Exception $e) {


            DB::rollBack();


            return response()->json([

                'success' => false,

                'message' => 'Internal Server Error',

                'data' => null,

                'errors' => $e->getMessage()

            ], 500);
        }
    }

    /**
     * Hapus detail penyewaan
     */
    public function destroy($id)
    {

        DB::beginTransaction();


        try {


            $detail = PenyewaanDetail::find($id);



            if (!$detail) {


                return response()->json([

                    'success' => false,

                    'message' => 'Data detail penyewaan tidak ditemukan',

                    'data' => null

                ], 404);
            }



            // kembalikan stok alat

            $alat = Alat::find(
                $detail->penyewaan_detail_alat_id
            );


            if ($alat) {


                $alat->increment(

                    'alat_stok',

                    $detail->penyewaan_detail_jumlah

                );
            }

            $detail->delete();

            DB::commit();

            return response()->json([

                'success' => true,

                'message' => 'Berhasil menghapus detail penyewaan',

                'data' => null

            ], 200);
        } catch (\Exception $e) {


            DB::rollBack();

            return response()->json([

                'success' => false,

                'message' => 'Internal Server Error',

                'data' => null,

                'errors' => $e->getMessage()

            ], 500);
        }
    }
}
