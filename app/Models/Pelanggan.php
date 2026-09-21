<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Pelanggan extends Authenticatable implements JWTSubject
{
    protected $table = 'pelanggan';

    protected $primaryKey = 'pelanggan_id';

    protected $fillable = [
        'pelanggan_nama',
        'pelanggan_alamat',
        'pelanggan_notelp',
        'pelanggan_email',
        'pelanggan_password',
    ];

    /**
     * Jangan pernah kirim password ke frontend.
     */
    protected $hidden = [
        'pelanggan_password',
    ];
    
    public function getAuthPassword(): string
    {
        return $this->pelanggan_password;
    }

    /**
     * JWT Identifier
     */
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * JWT Custom Claims
     */
    public function getJWTCustomClaims(): array
    {
        return [];
    }

    /**
     * Relasi ke data identitas pelanggan
     */
    public function pelanggan_data()
    {
        return $this->hasMany(
            PelangganData::class,
            'pelanggan_data_pelanggan_id',
            'pelanggan_id'
        );
    }

    /**
     * Relasi ke penyewaan
     */
    public function penyewaan()
    {
        return $this->hasMany(
            Penyewaan::class,
            'penyewaan_pelanggan_id',
            'pelanggan_id'
        );
    }
}