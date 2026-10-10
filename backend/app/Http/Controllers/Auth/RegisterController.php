<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthenticationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    protected AuthenticationService $authService;

    public function __construct(AuthenticationService $authService)
    {
        $this->authService = $authService;
    }

    public function store(RegisterRequest $request)
    {
        $data = $request->validated();

        $user = $this->authService->register($data);

        $token = $user->createToken('auth-token')->plainTextToken;

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Inscription réussie',
                'data' => [
                    'user' => $user,
                    'token' => $token,
                    'token_type' => 'Bearer',
                ],
            ], 201);
        }

        return redirect()->route('dashboard.admin')->with('success', 'Inscription réussie.');
    }
}
