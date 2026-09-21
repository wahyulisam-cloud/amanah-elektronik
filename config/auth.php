<?php

return [

    'defaults' => [
        'guard' => 'api',
        'passwords' => 'users',
    ],

    'guards' => [

        'web' => [
            'driver' => 'session',
            'provider' => 'admin',
        ],

        'api' => [
            'driver' => 'jwt',
            'provider' => 'admin',
        ],

        'pelanggan' => [
            'driver' => 'jwt',
            'provider' => 'pelanggans',
        ],

    ],

    'providers' => [

        'admin' => [
            'driver' => 'eloquent',
            'model' => App\Models\Admin::class,
        ],

        'pelanggans' => [
            'driver' => 'eloquent',
            'model' => App\Models\Pelanggan::class,
        ],

    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env(
                'AUTH_PASSWORD_RESET_TOKEN_TABLE',
                'password_reset_tokens'
            ),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env(
        'AUTH_PASSWORD_TIMEOUT',
        10800
    ),

];