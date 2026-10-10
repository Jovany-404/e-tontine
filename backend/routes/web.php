<?php

use Illuminate\Support\Facades\Route;

// Le backend est une API pure : la racine renvoie seulement une indication JSON.
Route::get('/', fn () => response()->json([
    'name' => config('app.name'),
    'api' => url('/api/v1'),
]));
