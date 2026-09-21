<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penyewaan extends Model
{
    protected $table = 'penyewaan';

    protected $primaryKey = 'penyewaan_id';

    protected $fillable = [
        'penyewaan_pelanggan_id',
        'penyewaan_tglsewa',
        'penyewaan_tglkembali',
        'penyewaan_sttspembayaran',
        'penyewaan_sttskembali',
        'penyewaan_totalharga'
    ];

    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class,'penyewaan_pelanggan_id','pelanggan_id');
    }

    public function detail()
    {
        return $this->hasMany(PenyewaanDetail::class,'penyewaan_detail_penyewaan_id','penyewaan_id');
    }
}