<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Utilisateur actuellement connecté.
     */
    public function show(Request $request): UserResource
    {
        return UserResource::make($request->user());
    }
}
