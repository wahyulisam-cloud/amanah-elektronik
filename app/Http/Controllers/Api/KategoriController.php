<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KategoriController extends Controller
{
    /**
     * Menampilkan semua data kategori
     */
public function index(Request $request)
{
    try {

        $perPage = $request->get('per_page', 5);

        $kategori = Kategori::orderBy(
            'kategori_id',
            'desc'
        )->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Successfully get kategori data',

            'data' => $kategori->items(),

            'pagination' => [
                'current_page' => $kategori->currentPage(),
                'last_page' => $kategori->lastPage(),
                'per_page' => $kategori->perPage(),
                'total' => $kategori->total(),
                'from' => $kategori->firstItem(),
                'to' => $kategori->lastItem(),
            ]

        ], 200);

    } catch (\Exception $e) {

        return response()->json([
            'success' => false,
            'message' => 'There is an error in Internal Server',
            'data' => [],
            'pagination' => null,
            'errors' => $e->getMessage()
        ], 500);
    }
}
    /**
     * Menambahkan data kategori
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kategori_nama' => 'required|string|max:100|unique:kategori,kategori_nama',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan data kategori!',
                'data' => null,
                'errors' => $validator->errors()
            ], 422);
        }

        try {

            $kategori = Kategori::create([
                'kategori_nama' => $request->kategori_nama
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Berhasil menambahkan data kategori!',
                'data' => $kategori
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
     * Menampilkan detail kategori berdasarkan ID
     */
    public function show(string $id)
    {
        try {

            $kategori = Kategori::find($id);

            if (!$kategori) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data kategori tidak ditemukan!',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Berhasil mengambil detail kategori!',
                'data' => $kategori
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
     * Mengubah data kategori
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'kategori_nama' => 'required|string|max:100|unique:kategori,kategori_nama,' . $id . ',kategori_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah data kategori!',
                'data' => null,
                'errors' => $validator->errors()
            ], 422);
        }

        try {

            $kategori = Kategori::find($id);

            if (!$kategori) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data kategori tidak ditemukan!',
                    'data' => null
                ], 404);
            }

            $kategori->update([
                'kategori_nama' => $request->kategori_nama
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Berhasil mengubah data kategori!',
                'data' => $kategori
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
     * Menghapus data kategori
     */
    public function destroy(string $id)
    {
        try {

            $kategori = Kategori::find($id);

            if (!$kategori) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data kategori tidak ditemukan!',
                    'data' => null
                ], 404);
            }

            $kategori->delete();

            return response()->json([
                'success' => true,
                'message' => 'Berhasil menghapus data kategori!',
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
