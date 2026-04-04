<?php

use App\Models\User;

return [

    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    'guards' => [
        // Business owners and staff
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        // Clients — separate session, separate middleware
        'client' => [
            'driver' => 'session',
            'provider' => 'clients',
        ],
    ],

    'providers' => [
        // Both guards use the same users table/model.
        // The 'role' column is what differentiates them.
        // Separate providers allow separate remember-token handling if needed.
        'users' => [
            'driver' => 'eloquent',
            'model' => User::class,
        ],

        'clients' => [
            'driver' => 'eloquent',
            'model' => User::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,

];
