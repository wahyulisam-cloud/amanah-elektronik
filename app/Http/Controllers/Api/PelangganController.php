<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pelanggan;
use App\Models\PelangganData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PelangganController extends Controller
{
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

            $query = Pelanggan::with('pelanggan_data');

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where(
                        'pelanggan_nama',
                        'like',
                        "%{$search}%"
                    )
                        ->orWhere(
                            'pelanggan_email',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'pelanggan_notelp',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'pelanggan_alamat',
                            'like',
                            "%{$search}%"
                        );
                });
            }

            $pelanggan = $query
                ->orderBy('pelanggan_id', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Berhasil mengambil data pelanggan.',
                'data' => $pelanggan->items(),
                'pagination' => [
                    'current_page' => $pelanggan->currentPage(),
                    'last_page' => $pelanggan->lastPage(),
                    'per_page' => $pelanggan->perPage(),
                    'total' => $pelanggan->total(),
                    'from' => $pelanggan->firstItem(),
                    'to' => $pelanggan->lastItem(),
                ]
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data pelanggan.',
                'data' => null,
                'pagination' => null,
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'pelanggan_nama' => [
                    'required',
                    'string',
                    'max:150'
                ],

                'pelanggan_alamat' => [
                    'required',
                    'string',
                    'max:200'
                ],

                'pelanggan_notelp' => [
                    'required',
                    'regex:/^[0-9]{10,13}$/'
                ],

                'pelanggan_email' => [
                    'required',
                    'email',
                    'max:100',
                    'unique:pelanggan,pelanggan_email'
                ],

                'pelanggan_data_jenis' => [
                    'required',
                    'in:KTP,SIM'
                ],

                'pelanggan_data_file' => [
                    'required',
                    'file',
                    'image',
                    'mimes:jpg,jpeg,png',
                    'max:2048'
                ]
            ],
            [
                'pelanggan_nama.required' =>
                'Nama pelanggan wajib diisi.',

                'pelanggan_alamat.required' =>
                'Alamat pelanggan wajib diisi.',

                'pelanggan_notelp.required' =>
                'Nomor telepon wajib diisi.',

                'pelanggan_notelp.regex' =>
                'Nomor telepon harus terdiri dari 10-13 digit angka.',

                'pelanggan_email.required' =>
                'Email pelanggan wajib diisi.',

                'pelanggan_email.email' =>
                'Format email tidak valid.',

                'pelanggan_email.unique' =>
                'Email pelanggan sudah terdaftar.',

                'pelanggan_data_jenis.required' =>
                'Jenis identitas wajib dipilih.',

                'pelanggan_data_jenis.in' =>
                'Jenis identitas harus KTP atau SIM.',

                'pelanggan_data_file.required' =>
                'Foto identitas wajib diupload.',

                'pelanggan_data_file.image' =>
                'File identitas harus berupa gambar.',

                'pelanggan_data_file.mimes' =>
                'Foto identitas harus berformat JPG, JPEG, atau PNG.',

                'pelanggan_data_file.max' =>
                'Ukuran foto identitas maksimal 2 MB.'
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal!',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        $uploadedFilePath = null;

        try {
            $pelanggan = Pelanggan::create([
                'pelanggan_nama' => $request->pelanggan_nama,
                'pelanggan_alamat' => $request->pelanggan_alamat,
                'pelanggan_notelp' => $request->pelanggan_notelp,
                'pelanggan_email' => $request->pelanggan_email,
                'pelanggan_password' => null,
            ]);

            $file = $request->file('pelanggan_data_file');

            $namaFile =
                time() .
                '_' .
                uniqid() .
                '.' .
                $file->getClientOriginalExtension();

            $uploadedFilePath = $file->storeAs(
                'pelanggan',
                $namaFile,
                'public'
            );

            PelangganData::create([
                'pelanggan_data_pelanggan_id' =>
                $pelanggan->pelanggan_id,

                'pelanggan_data_jenis' =>
                $request->pelanggan_data_jenis,

                'pelanggan_data_file' =>
                $uploadedFilePath,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data pelanggan berhasil ditambahkan.',
                'data' => $pelanggan->load('pelanggan_data')
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            if (
                $uploadedFilePath &&
                Storage::disk('public')->exists(
                    $uploadedFilePath
                )
            ) {
                Storage::disk('public')->delete(
                    $uploadedFilePath
                );
            }

            return response()->json([
                'success' => false,
                'message' =>
                'Terjadi kesalahan saat menambahkan pelanggan.',
                'data' => null
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $pelanggan = Pelanggan::with(
                'pelanggan_data'
            )->find($id);

            if (!$pelanggan) {
                return response()->json([
                    'success' => false,
                    'message' =>
                    'Data pelanggan tidak ditemukan!',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' =>
                'Berhasil mengambil detail pelanggan.',
                'data' => $pelanggan
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' =>
                'Gagal mengambil detail pelanggan.',
                'data' => null
            ], 500);
        }
    }

    public function update(Request $request, string $id)
    {
        $pelanggan = Pelanggan::with(
            'pelanggan_data'
        )->find($id);

        if (!$pelanggan) {
            return response()->json([
                'success' => false,
                'message' =>
                'Data pelanggan tidak ditemukan!'
            ], 404);
        }

        $validator = Validator::make(
            $request->all(),
            [
                'pelanggan_nama' => [
                    'required',
                    'string',
                    'max:150'
                ],

                'pelanggan_alamat' => [
                    'required',
                    'string',
                    'max:200'
                ],

                'pelanggan_notelp' => [
                    'required',
                    'regex:/^[0-9]{10,13}$/'
                ],

                'pelanggan_email' => [
                    'required',
                    'email',
                    'max:100',
                    'unique:pelanggan,pelanggan_email,' .
                        $id .
                        ',pelanggan_id'
                ],

                'pelanggan_data_jenis' => [
                    'required',
                    'in:KTP,SIM'
                ],

                'pelanggan_data_file' => [
                    'nullable',
                    'file',
                    'image',
                    'mimes:jpg,jpeg,png',
                    'max:2048'
                ]
            ],
            [
                'pelanggan_nama.required' =>
                'Nama pelanggan wajib diisi.',

                'pelanggan_alamat.required' =>
                'Alamat pelanggan wajib diisi.',

                'pelanggan_notelp.required' =>
                'Nomor telepon wajib diisi.',

                'pelanggan_notelp.regex' =>
                'Nomor telepon harus terdiri dari 10-13 digit angka.',

                'pelanggan_email.required' =>
                'Email pelanggan wajib diisi.',

                'pelanggan_email.email' =>
                'Format email tidak valid.',

                'pelanggan_email.unique' =>
                'Email tersebut sudah digunakan pelanggan lain.',

                'pelanggan_data_jenis.required' =>
                'Jenis identitas wajib dipilih.',

                'pelanggan_data_jenis.in' =>
                'Jenis identitas harus KTP atau SIM.',

                'pelanggan_data_file.image' =>
                'File identitas harus berupa gambar.',

                'pelanggan_data_file.mimes' =>
                'Foto identitas harus berformat JPG, JPEG, atau PNG.',

                'pelanggan_data_file.max' =>
                'Ukuran foto identitas maksimal 2 MB.'
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal!',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        $newFilePath = null;

        try {
            $pelanggan->update([
                'pelanggan_nama' =>
                $request->pelanggan_nama,

                'pelanggan_alamat' =>
                $request->pelanggan_alamat,

                'pelanggan_notelp' =>
                $request->pelanggan_notelp,

                'pelanggan_email' =>
                $request->pelanggan_email,
            ]);

            $pelangganData =
                $pelanggan->pelanggan_data->first();

            if (!$pelangganData) {
                if (
                    !$request->hasFile(
                        'pelanggan_data_file'
                    )
                ) {
                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' =>
                        'File identitas wajib diupload.'
                    ], 422);
                }

                $file =
                    $request->file(
                        'pelanggan_data_file'
                    );

                $namaFile =
                    time() .
                    '_' .
                    uniqid() .
                    '.' .
                    $file->getClientOriginalExtension();

                $newFilePath =
                    $file->storeAs(
                        'pelanggan',
                        $namaFile,
                        'public'
                    );

                PelangganData::create([
                    'pelanggan_data_pelanggan_id' =>
                    $pelanggan->pelanggan_id,

                    'pelanggan_data_jenis' =>
                    $request->pelanggan_data_jenis,

                    'pelanggan_data_file' =>
                    $newFilePath
                ]);
            } else {
                $pelangganData->pelanggan_data_jenis =
                    $request->pelanggan_data_jenis;

                if (
                    $request->hasFile(
                        'pelanggan_data_file'
                    )
                ) {
                    $file =
                        $request->file(
                            'pelanggan_data_file'
                        );

                    $namaFile =
                        time() .
                        '_' .
                        uniqid() .
                        '.' .
                        $file->getClientOriginalExtension();

                    $newFilePath =
                        $file->storeAs(
                            'pelanggan',
                            $namaFile,
                            'public'
                        );

                    if (
                        $pelangganData->pelanggan_data_file &&
                        Storage::disk('public')->exists(
                            $pelangganData->pelanggan_data_file
                        )
                    ) {
                        Storage::disk('public')->delete(
                            $pelangganData->pelanggan_data_file
                        );
                    }

                    $pelangganData->pelanggan_data_file =
                        $newFilePath;
                }

                $pelangganData->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' =>
                'Berhasil mengubah data pelanggan.',
                'data' =>
                $pelanggan->load(
                    'pelanggan_data'
                )
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            if (
                $newFilePath &&
                Storage::disk('public')->exists(
                    $newFilePath
                )
            ) {
                Storage::disk('public')->delete(
                    $newFilePath
                );
            }

            return response()->json([
                'success' => false,
                'message' =>
                'Terjadi kesalahan saat mengubah data pelanggan.',
                'data' => null
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        $pelanggan = Pelanggan::with(
            'pelanggan_data'
        )->find($id);

        if (!$pelanggan) {
            return response()->json([
                'success' => false,
                'message' =>
                'Data pelanggan tidak ditemukan!'
            ], 404);
        }

        DB::beginTransaction();

        try {
            foreach (
                $pelanggan->pelanggan_data
                as $data
            ) {
                if (
                    $data->pelanggan_data_file &&
                    Storage::disk('public')->exists(
                        $data->pelanggan_data_file
                    )
                ) {
                    Storage::disk('public')->delete(
                        $data->pelanggan_data_file
                    );
                }
            }

            $pelanggan
                ->pelanggan_data()
                ->delete();

            $pelanggan->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' =>
                'Berhasil menghapus pelanggan.'
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' =>
                'Terjadi kesalahan saat menghapus pelanggan.'
            ], 500);
        }
    }
}
