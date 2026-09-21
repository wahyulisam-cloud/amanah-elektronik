<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Admin extends Authenticatable implements JWTSubject
{
    protected $table = 'admin';

    protected $primaryKey = 'admin_id';

    protected $fillable = [
        'admin_username',
        'admin_password'
    ];

    protected $hidden = [
        'admin_password'
    ];

    public function getAuthPassword()
    {
        return $this->admin_password;
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
