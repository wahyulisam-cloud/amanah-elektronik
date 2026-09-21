<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PelangganData extends Model
{
    protected $table = 'pelanggan_data';

    protected $primaryKey = 'pelanggan_data_id';

    protected $fillable = [

        'pelanggan_data_pelanggan_id',

        'pelanggan_data_jenis',

        'pelanggan_data_file'

    ];

    /**
     * Relasi ke pelanggan
     */
    public function pelanggan()
    {
        return $this->belongsTo(
            Pelanggan::class,
            'pelanggan_data_pelanggan_id',
            'pelanggan_id'
        );
    }
}