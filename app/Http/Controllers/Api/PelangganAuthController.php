<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pelanggan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\PelangganData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PelangganAuthController extends Controller
{
    /**
     * ==========================================================
     * LOGIN PELANGGAN
     * ==========================================================
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pelanggan_email' => 'required|email',
            'pelanggan_password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal!',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            /*
             * Cari pelanggan berdasarkan email
             */
            $pelanggan = Pelanggan::where(
                'pelanggan_email',
                $request->pelanggan_email
            )->first();

            /*
             * Jika email tidak ditemukan
             */
            if (!$pelanggan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email atau password salah.',
                    'data' => null,
                ], 401);
            }

            /*
             * Cek password
             */
            if (!Hash::check(
                $request->pelanggan_password,
                $pelanggan->pelanggan_password
            )) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email atau password salah.',
                    'data' => null,
                ], 401);
            }

            $token = Auth::guard('pelanggan')->login($pelanggan);

            return response()->json([
                'success' => true,
                'message' => 'Login pelanggan berhasil.',
                'token' => $token,
                'data' => $pelanggan,
            ], 200);
        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat login.',
                'data' => null,
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ==========================================================
     * REGISTER / AKTIVASI AKUN PELANGGAN
     * ==========================================================
     *
     * ALUR:
     *
     * 1. Email ditemukan + password sudah ada
     *    => akun sudah aktif => tolak
     *
     * 2. Email ditemukan + password NULL
     *    => pelanggan sudah didaftarkan admin
     *    => cocokkan nomor telepon
     *    => jika cocok, buat password / aktivasi akun
     *
     * 3. Email tidak ditemukan
     *    => buat pelanggan baru
     *    => wajib melengkapi data + identitas
     *
     * TIDAK MEMBUTUHKAN TOKEN.
     */
    public function register(Request $request)
    {
        // ==========================================================
        // VALIDASI DASAR REGISTER
        // ==========================================================

        $validator = Validator::make(
            $request->all(),
            [
                'pelanggan_email' => [
                    'required',
                    'email',
                    'max:100'
                ],

                'pelanggan_notelp' => [
                    'required',
                    'regex:/^[0-9]{10,13}$/'
                ],

                'pelanggan_password' => [
                    'required',
                    'string',
                    'min:8',
                    'confirmed'
                ],

                /*
             * Data berikut hanya wajib jika ternyata
             * email belum terdaftar di database.
             *
             * Untuk sementara nullable karena pelanggan
             * yang sudah dibuat admin tidak perlu mengisi
             * ulang data tersebut.
             */
                'pelanggan_nama' => [
                    'nullable',
                    'string',
                    'max:150'
                ],

                'pelanggan_alamat' => [
                    'nullable',
                    'string',
                    'max:200'
                ],

                'pelanggan_data_jenis' => [
                    'nullable',
                    'in:KTP,SIM'
                ],

                'pelanggan_data_file' => [
                    'nullable',
                    'file',
                    'image',
                    'mimes:jpg,jpeg,png',
                    'max:2048'
                ],
            ],
            [
                'pelanggan_email.required' =>
                'Email wajib diisi.',

                'pelanggan_email.email' =>
                'Format email tidak valid.',

                'pelanggan_email.max' =>
                'Email maksimal 100 karakter.',

                'pelanggan_notelp.required' =>
                'Nomor telepon wajib diisi.',

                'pelanggan_notelp.regex' =>
                'Nomor telepon harus terdiri dari 10-13 digit angka.',

                'pelanggan_password.required' =>
                'Password wajib diisi.',

                'pelanggan_password.min' =>
                'Password minimal 8 karakter.',

                'pelanggan_password.confirmed' =>
                'Konfirmasi password tidak cocok.',

                'pelanggan_nama.max' =>
                'Nama pelanggan maksimal 150 karakter.',

                'pelanggan_alamat.max' =>
                'Alamat pelanggan maksimal 200 karakter.',

                'pelanggan_data_jenis.in' =>
                'Jenis identitas harus KTP atau SIM.',

                'pelanggan_data_file.image' =>
                'File identitas harus berupa gambar.',

                'pelanggan_data_file.mimes' =>
                'Foto identitas harus berformat JPG, JPEG, atau PNG.',

                'pelanggan_data_file.max' =>
                'Ukuran foto identitas maksimal 2 MB.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal!',
                'errors' => $validator->errors(),
            ], 422);
        }


        // ==========================================================
        // NORMALISASI DATA
        // ==========================================================

        $email = strtolower(
            trim($request->pelanggan_email)
        );

        $notelp = trim(
            $request->pelanggan_notelp
        );


        // ==========================================================
        // CARI PELANGGAN BERDASARKAN EMAIL
        // ==========================================================

        $pelanggan = Pelanggan::with('pelanggan_data')
            ->where('pelanggan_email', $email)
            ->first();


        // ==========================================================
        // SKENARIO 1
        // EMAIL SUDAH TERDAFTAR
        // ==========================================================

        if ($pelanggan) {

            // ======================================================
            // CEK APAKAH AKUN SUDAH AKTIF
            // ======================================================

            if (!empty($pelanggan->pelanggan_password)) {

                return response()->json([
                    'success' => false,
                    'message' =>
                    'Email tersebut sudah memiliki akun. Silakan login.',
                    'data' => null,
                ], 409);
            }


            // ======================================================
            // EMAIL ADA, TAPI AKUN BELUM AKTIF
            //
            // Berarti kemungkinan besar data ini sebelumnya
            // dimasukkan oleh ADMIN.
            // ======================================================

            if ($pelanggan->pelanggan_notelp !== $notelp) {

                return response()->json([
                    'success' => false,
                    'message' =>
                    'Data pelanggan ditemukan, tetapi nomor telepon tidak sesuai.',
                    'errors' => [
                        'pelanggan_notelp' => [
                            'Nomor telepon tidak sesuai dengan data pelanggan.'
                        ]
                    ],
                    'data' => null,
                ], 422);
            }


            // ======================================================
            // AKTIVASI AKUN PELANGGAN
            // ======================================================

            try {

                $pelanggan->pelanggan_password =
                    Hash::make(
                        $request->pelanggan_password
                    );

                $pelanggan->save();

                $pelanggan->refresh();


                // ==================================================
                // RESPONSE
                // ==================================================
                //
                // Password TIDAK dikirim.
                //

                return response()->json([
                    'success' => true,
                    'message' =>
                    'Akun pelanggan berhasil diaktivasi. Silakan login.',
                    'data' => [
                        'pelanggan_id' =>
                        $pelanggan->pelanggan_id,

                        'pelanggan_nama' =>
                        $pelanggan->pelanggan_nama,

                        'pelanggan_alamat' =>
                        $pelanggan->pelanggan_alamat,

                        'pelanggan_notelp' =>
                        $pelanggan->pelanggan_notelp,

                        'pelanggan_email' =>
                        $pelanggan->pelanggan_email,
                    ],
                ], 200);
            } catch (\Throwable $e) {

                return response()->json([
                    'success' => false,
                    'message' =>
                    'Gagal mengaktivasi akun pelanggan.',
                    'data' => null,

                    /*
                 * Untuk development boleh ditampilkan.
                 * Nanti production sebaiknya jangan dikirim.
                 */
                    'errors' => $e->getMessage(),
                ], 500);
            }
        }


        // ==========================================================
        // SKENARIO 2
        // EMAIL BELUM ADA
        //
        // Berarti pengguna benar-benar melakukan REGISTER BARU.
        // ==========================================================


        // ==========================================================
        // VALIDASI DATA REGISTER BARU
        // ==========================================================

        $validatorBaru = Validator::make(
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
                ],
            ],
            [
                'pelanggan_nama.required' =>
                'Nama pelanggan wajib diisi.',

                'pelanggan_nama.max' =>
                'Nama pelanggan maksimal 150 karakter.',

                'pelanggan_alamat.required' =>
                'Alamat pelanggan wajib diisi.',

                'pelanggan_alamat.max' =>
                'Alamat pelanggan maksimal 200 karakter.',

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
                'Ukuran foto identitas maksimal 2 MB.',
            ]
        );


        if ($validatorBaru->fails()) {

            return response()->json([
                'success' => false,
                'message' =>
                'Data pelanggan baru belum lengkap.',
                'errors' =>
                $validatorBaru->errors(),
            ], 422);
        }


        // ==========================================================
        // TRANSACTION
        // ==========================================================

        DB::beginTransaction();

        $uploadedFilePath = null;

        try {

            // ======================================================
            // BUAT DATA PELANGGAN BARU
            // ======================================================

            $pelanggan = Pelanggan::create([

                'pelanggan_nama' =>
                $request->pelanggan_nama,

                'pelanggan_alamat' =>
                $request->pelanggan_alamat,

                'pelanggan_notelp' =>
                $notelp,

                'pelanggan_email' =>
                $email,

                'pelanggan_password' =>
                Hash::make(
                    $request->pelanggan_password
                ),
            ]);


            // ======================================================
            // UPLOAD IDENTITAS
            // ======================================================

            $file = $request->file(
                'pelanggan_data_file'
            );

            $namaFile =
                time() .
                '_' .
                uniqid() .
                '.' .
                $file->getClientOriginalExtension();


            $uploadedFilePath =
                $file->storeAs(
                    'pelanggan',
                    $namaFile,
                    'public'
                );


            // ======================================================
            // SIMPAN DATA IDENTITAS
            // ======================================================

            PelangganData::create([

                'pelanggan_data_pelanggan_id' =>
                $pelanggan->pelanggan_id,

                'pelanggan_data_jenis' =>
                $request->pelanggan_data_jenis,

                'pelanggan_data_file' =>
                $uploadedFilePath,
            ]);


            // ======================================================
            // COMMIT
            // ======================================================

            DB::commit();


            // ======================================================
            // RESPONSE
            // ======================================================

            return response()->json([
                'success' => true,
                'message' =>
                'Registrasi akun berhasil. Silakan login.',
                'data' => [
                    'pelanggan_id' =>
                    $pelanggan->pelanggan_id,

                    'pelanggan_nama' =>
                    $pelanggan->pelanggan_nama,

                    'pelanggan_alamat' =>
                    $pelanggan->pelanggan_alamat,

                    'pelanggan_notelp' =>
                    $pelanggan->pelanggan_notelp,

                    'pelanggan_email' =>
                    $pelanggan->pelanggan_email,

                    'pelanggan_data' =>
                    $pelanggan
                        ->load('pelanggan_data')
                        ->pelanggan_data,
                ],
            ], 201);
        } catch (\Throwable $e) {

            // ======================================================
            // ROLLBACK
            // ======================================================

            DB::rollBack();


            // ======================================================
            // HAPUS FILE JIKA DATABASE GAGAL
            // ======================================================

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


            // ======================================================
            // ERROR RESPONSE
            // ======================================================

            return response()->json([
                'success' => false,
                'message' =>
                'Terjadi kesalahan saat melakukan registrasi.',
                'data' => null,

                /*
             * Untuk development/testing.
             */
                'errors' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * ==========================================================
     * DATA PELANGGAN YANG SEDANG LOGIN
     * ==========================================================
     */
    public function me()
    {
        try {

            /*
             * Ambil user dari guard pelanggan.
             */
            $pelanggan = Auth::guard('pelanggan')->user();

            if (!$pelanggan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data pelanggan tidak ditemukan.',
                    'data' => null,
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Data pelanggan berhasil diambil.',
                'data' => $pelanggan,
            ], 200);
        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data pelanggan.',
                'data' => null,
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * ==========================================================
     * UPDATE PROFILE PELANGGAN YANG SEDANG LOGIN
     * ==========================================================
     */
    public function updateProfile(Request $request)
    {
        try {

            // =====================================================
            // AMBIL PELANGGAN DARI TOKEN
            // =====================================================

            $pelanggan = Auth::guard('pelanggan')->user();

            if (!$pelanggan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pelanggan belum terautentikasi.',
                    'data' => null,
                ], 401);
            }

            // =====================================================
            // VALIDASI
            // =====================================================

            $validator = Validator::make($request->all(), [

                'pelanggan_nama' => [
                    'required',
                    'string',
                    'max:100'
                ],

                'pelanggan_alamat' => [
                    'required',
                    'string'
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
                        $pelanggan->pelanggan_id .
                        ',pelanggan_id'
                ],

            ]);

            if ($validator->fails()) {

                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal!',
                    'errors' => $validator->errors()
                ], 422);
            }

            // =====================================================
            // UPDATE
            // =====================================================

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

            // =====================================================
            // AMBIL DATA TERBARU
            // =====================================================

            $pelanggan->refresh();

            // =====================================================
            // RESPONSE
            // =====================================================

            return response()->json([
                'success' => true,
                'message' => 'Profile berhasil diperbarui.',
                'data' => $pelanggan
            ], 200);
        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui profile.',
                'data' => null,
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ==========================================================
     * LOGOUT PELANGGAN
     * ==========================================================
     */
    public function logout()
    {
        try {

            /*
             * Logout menggunakan guard pelanggan.
             */
            Auth::guard('pelanggan')->logout();

            return response()->json([
                'success' => true,
                'message' => 'Logout pelanggan berhasil.',
            ], 200);
        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Gagal logout.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
