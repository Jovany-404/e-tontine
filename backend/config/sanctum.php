<?php

return [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:3000,localhost:8000',
        '127.0.0.1,127.0.0.1:8000,::1'
    ))),

    'exempt-guides' => [],

    'default-guard' => 'web',

    'authenticate-middleware' => \App\Http\Middleware\AuthenticateUser::class,
];
