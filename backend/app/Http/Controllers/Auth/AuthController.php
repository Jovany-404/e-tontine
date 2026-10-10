<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthenticationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    protected AuthenticationService $authService;

    public function __construct(AuthenticationService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request)
    {
        $data = $request->validated();
        $user = $this->authService->register($data);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Inscription réussie',
                'data' => [
                    'user' => $user,
                    'token' => $user->createToken('auth-token')->plainTextToken,
                    'token_type' => 'Bearer',
                ],
            ], 201);
        }

        return redirect()->route('dashboard.admin')->with('success', 'Inscription réussie.');
    }

    public function login(LoginRequest $request)
    {
        $data = $request->validated();

        try {
            $result = $this->authService->login($data);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Connexion réussie',
                    'data' => $result,
                ], 200);
            }

            return redirect()->route('dashboard.admin')->with('success', 'Connexion réussie.');
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Email ou mot de passe incorrect',
                ], 401);
            }

            return redirect()->back()->withInput()->with('error', $e->getMessage() ?: 'Email ou mot de passe incorrect');
        }
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            $this->authService->logout(Auth::user());
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Déconnexion réussie',
            ], 200);
        }

        return redirect()->route('login')->with('success', 'Déconnexion réussie.');
    }
}
