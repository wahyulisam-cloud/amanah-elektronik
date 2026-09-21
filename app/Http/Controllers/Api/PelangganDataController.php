<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PelangganData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class PelangganDataController extends Controller
{
    /**
     * Menampilkan semua data identitas pelanggan
     */
    public function index()
    {
        try {

            $data = PelangganData::with('pelanggan')->get();

            return response()->json([
                'success' => true,
                'message' => 'Successfully get pelanggan data',
                'data' => $data
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'There is an error in Internal Server',
                'data' => null,
                'errors' => $e->getMessage()
            ], 500);

        }
    }

    /**
     * Menambahkan data identitas pelanggan
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'pelanggan_data_pelanggan_id' => 'required|exists:pelanggan,pelanggan_id',

            'pelanggan_data_jenis' => 'required|in:KTP,SIM',

            'pelanggan_data_file' => 'required|image|mimes:jpg,jpeg,png|max:2048'

        ]);

        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan data pelanggan!',
                'data' => null,
                'errors' => $validator->errors()
            ], 422);

        }

        try {

            $file = $request->file('pelanggan_data_file');

            $namaFile = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            $file->storeAs('pelanggan', $namaFile, 'public');

            $data = PelangganData::create([

                'pelanggan_data_pelanggan_id' => $request->pelanggan_data_pelanggan_id,

                'pelanggan_data_jenis' => $request->pelanggan_data_jenis,

                'pelanggan_data_file' => 'pelanggan/' . $namaFile

            ]);

            return response()->json([
                'success' => true,
                'message' => 'Berhasil menambahkan data pelanggan!',
                'data' => $data
            ], 201);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'There is an error in Internal Server',
                'data' => null,
                'errors' => $e->getMessage()
            ], 500);

        }
    }

    /**
     * Detail identitas pelanggan
     */
    public function show(string $id)
    {
        try {

            $data = PelangganData::with('pelanggan')->find($id);

            if (!$data) {

                return response()->json([
                    'success' => false,
                    'message' => 'Data pelanggan tidak ditemukan!',
                    'data' => null
                ], 404);

            }

            return response()->json([
                'success' => true,
                'message' => 'Berhasil mengambil detail data pelanggan!',
                'data' => $data
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'There is an error in Internal Server',
                'data' => null,
                'errors' => $e->getMessage()
            ], 500);

        }
    }

    /**
     * Update identitas pelanggan
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [

            'pelanggan_data_pelanggan_id' => 'required|exists:pelanggan,pelanggan_id',

            'pelanggan_data_jenis' => 'required|in:KTP,SIM',

            'pelanggan_data_file' => 'nullable|image|mimes:jpg,jpeg,png|max:2048'

        ]);

        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah data pelanggan!',
                'data' => null,
                'errors' => $validator->errors()
            ], 422);

        }

        try {

            $data = PelangganData::find($id);

            if (!$data) {

                return response()->json([
                    'success' => false,
                    'message' => 'Data pelanggan tidak ditemukan!',
                    'data' => null
                ], 404);

            }

            if ($request->hasFile('pelanggan_data_file')) {

                if (
                    $data->pelanggan_data_file &&
                    Storage::disk('public')->exists($data->pelanggan_data_file)
                ) {

                    Storage::disk('public')->delete($data->pelanggan_data_file);

                }

                $file = $request->file('pelanggan_data_file');

                $namaFile = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

                $file->storeAs('pelanggan', $namaFile, 'public');

                $data->pelanggan_data_file = 'pelanggan/' . $namaFile;

            }

            $data->pelanggan_data_pelanggan_id = $request->pelanggan_data_pelanggan_id;

            $data->pelanggan_data_jenis = $request->pelanggan_data_jenis;

            $data->save();

            return response()->json([
                'success' => true,
                'message' => 'Berhasil mengubah data pelanggan!',
                'data' => $data
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'There is an error in Internal Server',
                'data' => null,
                'errors' => $e->getMessage()
            ], 500);

        }
    }

    /**
     * Hapus identitas pelanggan
     */
    public function destroy(string $id)
    {
        try {

            $data = PelangganData::find($id);

            if (!$data) {

                return response()->json([
                    'success' => false,
                    'message' => 'Data pelanggan tidak ditemukan!',
                    'data' => null
                ], 404);

            }

            if (
                $data->pelanggan_data_file &&
                Storage::disk('public')->exists($data->pelanggan_data_file)
            ) {

                Storage::disk('public')->delete($data->pelanggan_data_file);

            }

            $data->delete();

            return response()->json([
                'success' => true,
                'message' => 'Berhasil menghapus data pelanggan!',
                'data' => null
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'There is an error in Internal Server',
                'data' => null,
                'errors' => $e->getMessage()
            ], 500);

        }
    }
}