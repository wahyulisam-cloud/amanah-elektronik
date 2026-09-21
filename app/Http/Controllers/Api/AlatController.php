<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class AlatController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
{
    try {

        $search = trim($request->get('search', ''));

        $query = Alat::with('kategori');

        // ============================
        // SEARCH
        // ============================

        if ($search !== '') {

            $query->where(function ($q) use ($search) {

                $q->where(
                    'alat_nama',
                    'like',
                    "%{$search}%"
                )

                ->orWhere(
                    'alat_deskripsi',
                    'like',
                    "%{$search}%"
                )

                ->orWhereHas('kategori', function ($kategori) use ($search) {

                    $kategori->where(
                        'kategori_nama',
                        'like',
                        "%{$search}%"
                    );

                });

            });
        }

        // ==================================================
        // JIKA ADA PARAMETER per_page
        // MAKA GUNAKAN PAGINATION
        // ==================================================

        if ($request->has('per_page')) {

            $perPage = (int) $request->get('per_page', 5);

            if ($perPage < 1) {
                $perPage = 5;
            }

            if ($perPage > 100) {
                $perPage = 100;
            }

            $alat = $query
                ->orderBy('alat_id', 'desc')
                ->paginate($perPage);

            return response()->json([

                'success' => true,

                'message' =>
                    'Berhasil mengambil data alat.',

                'data' => $alat->items(),

                'pagination' => [

                    'current_page' =>
                        $alat->currentPage(),

                    'last_page' =>
                        $alat->lastPage(),

                    'per_page' =>
                        $alat->perPage(),

                    'total' =>
                        $alat->total(),

                    'from' =>
                        $alat->firstItem(),

                    'to' =>
                        $alat->lastItem(),

                ]

            ], 200);
        }

        // ==================================================
        // MOBILE / PUBLIC
        // TANPA PAGINATION
        // ==================================================

        $alat = $query
            ->orderBy('alat_id', 'desc')
            ->get();

        return response()->json([

            'success' => true,

            'message' =>
                'Berhasil mengambil data alat.',

            'data' => $alat,

            'pagination' => null,

        ], 200);

    } catch (\Throwable $e) {

        return response()->json([

            'success' => false,

            'message' =>
                'Gagal mengambil data alat.',

            'data' => null,

            'pagination' => null,

            'errors' =>
                $e->getMessage()

        ], 500);
    }
}

    /**
     * Store a newly created resource.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'alat_kategori_id' => 'required|exists:kategori,kategori_id',
            'alat_nama' => 'required|string|max:150',
            'alat_deskripsi' => 'required|string|max:255',
            'alat_hargaperhari' => 'required|numeric|min:0',
            'alat_stok' => 'required|integer|min:0',

            'alat_gambar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',

        ]);

        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan data alat!',
                'data' => null,
                'errors' => $validator->errors()
            ], 422);
        }

        try {

            $gambar = null;

            if ($request->hasFile('alat_gambar')) {

                $gambar = $request
                    ->file('alat_gambar')
                    ->store('alat', 'public');
            }

            $alat = Alat::create([

                'alat_kategori_id' => $request->alat_kategori_id,
                'alat_nama' => $request->alat_nama,
                'alat_deskripsi' => $request->alat_deskripsi,
                'alat_hargaperhari' => $request->alat_hargaperhari,
                'alat_stok' => $request->alat_stok,
                'alat_gambar' => $gambar,

            ]);

            $alat->load('kategori');

            return response()->json([
                'success' => true,
                'message' => 'Berhasil menambahkan data alat!',
                'data' => $alat
            ], 201);
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
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {

            $alat = Alat::with('kategori')->find($id);

            if (!$alat) {

                return response()->json([
                    'success' => false,
                    'message' => 'Data alat tidak ditemukan!',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Berhasil mengambil detail alat!',
                'data' => $alat
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
     * Update the specified resource.
     */
    public function update(Request $request, string $id)
{
    $validator = Validator::make($request->all(), [

        'alat_kategori_id' => 'required|exists:kategori,kategori_id',
        'alat_nama' => 'required|string|max:150',
        'alat_deskripsi' => 'required|string|max:255',
        'alat_hargaperhari' => 'required|numeric|min:0',
        'alat_stok' => 'required|integer|min:0',
        'alat_gambar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',

    ]);

    if ($validator->fails()) {

        return response()->json([
            'success' => false,
            'message' => 'Gagal mengubah data alat!',
            'data' => null,
            'errors' => $validator->errors()
        ], 422);
    }

    try {

        $alat = Alat::find($id);

        if (!$alat) {

            return response()->json([
                'success' => false,
                'message' => 'Data alat tidak ditemukan!',
                'data' => null
            ], 404);
        }

        // ==========================================
        // DATA YANG AKAN DIUPDATE
        // ==========================================

        $data = [

            'alat_kategori_id' =>
                $request->alat_kategori_id,

            'alat_nama' =>
                $request->alat_nama,

            'alat_deskripsi' =>
                $request->alat_deskripsi,

            'alat_hargaperhari' =>
                $request->alat_hargaperhari,

            'alat_stok' =>
                $request->alat_stok,

        ];

        // ==========================================
        // JIKA ADA GAMBAR BARU
        // ==========================================

        if ($request->hasFile('alat_gambar')) {

            // Hapus gambar lama
            if ($alat->alat_gambar) {

                Storage::disk('public')
                    ->delete($alat->alat_gambar);
            }

            // Simpan gambar baru
            $gambar = $request
                ->file('alat_gambar')
                ->store('alat', 'public');

            $data['alat_gambar'] = $gambar;
        }

        // ==========================================
        // UPDATE DATABASE
        // ==========================================

        $alat->update($data);

        // Load kategori
        $alat->load('kategori');

        return response()->json([

            'success' => true,

            'message' =>
                'Berhasil mengubah data alat!',

            'data' => $alat

        ], 200);

    } catch (\Exception $e) {

        return response()->json([

            'success' => false,

            'message' =>
                'Internal Server Error',

            'data' => null,

            'errors' =>
                $e->getMessage()

        ], 500);
    }
}

    /**
     * Remove the specified resource.
     */
    public function destroy(string $id)
    {
        try {

            $alat = Alat::find($id);

            if (!$alat) {

                return response()->json([
                    'success' => false,
                    'message' => 'Data alat tidak ditemukan!',
                    'data' => null
                ], 404);
            }
            if ($alat->alat_gambar) {
                Storage::disk('public')->delete($alat->alat_gambar);
            }

            $alat->delete();

            return response()->json([
                'success' => true,
                'message' => 'Berhasil menghapus data alat!',
                'data' => null
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
}
