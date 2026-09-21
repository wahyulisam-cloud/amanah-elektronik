<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alat extends Model
{
    protected $table = 'alat';

    protected $primaryKey = 'alat_id';

    protected $fillable = [
        'alat_kategori_id',
        'alat_nama',
        'alat_deskripsi',
        'alat_hargaperhari',
        'alat_stok',
        'alat_gambar',
    ];
    protected $appends = [
        'alat_gambar_url'
    ];

    public function getAlatGambarUrlAttribute()
    {
        if (!$this->alat_gambar) {
            return null;
        }

        return asset(
            'storage/' . $this->alat_gambar
        );
    }
    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'alat_kategori_id', 'kategori_id');
    }

    public function detailPenyewaan()
    {
        return $this->hasMany(PenyewaanDetail::class, 'penyewaan_detail_alat_id', 'alat_id');
    }
}
