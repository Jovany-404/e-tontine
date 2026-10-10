<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class AuthenticationService
{
    public function register(array $data): User
    {
        $user = User::create([
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'nom' => $data['nom'] ?? null,
            'telephone' => $data['telephone'] ?? null,
            'role' => 'admin',
        ]);

        Auth::login($user);

        return $user;
    }

    public function login(array $data): array
    {
        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($email === '' || $password === '') {
            throw new \RuntimeException('Email ou mot de passe incorrect');
        }

        $user = $this->findUserByEmail($email);

        if (!$user || !$this->isPasswordValid($user, $password)) {
            throw new \RuntimeException('Email ou mot de passe incorrect');
        }

        $isActive = $user->getAttribute('is_active');
        if ($isActive === false || $isActive === 0) {
            throw new \RuntimeException('Utilisateur désactivé');
        }

        if ($this->shouldRehashPassword($user, $password)) {
            $user->forceFill(['password' => Hash::make($password)]);
            $user->save();
        }

        Auth::login($user);

        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ];
    }

    protected function findUserByEmail(string $email): ?User
    {
        return User::whereRaw('LOWER(email) = ?', [mb_strtolower($email)])->first();
    }

    protected function isPasswordValid(User $user, string $password): bool
    {
        foreach (['password', 'password_hash', 'mot_de_passe', 'password_hashed'] as $column) {
            $storedValue = $user->getAttribute($column);

            if ($storedValue === null || $storedValue === '') {
                continue;
            }

            if ($storedValue === $password) {
                return true;
            }

            if (Hash::check($password, (string) $storedValue)) {
                return true;
            }
        }

        return false;
    }

    protected function shouldRehashPassword(User $user, string $password): bool
    {
        $storedValue = $user->getAttribute('password');

        if (empty($storedValue)) {
            return false;
        }

        return $storedValue === $password || Hash::needsRehash((string) $storedValue);
    }

    public function logout(User $user): void
    {
        $user->tokens()->delete();
    }

    public function getCurrentUser(): User
    {
        return Auth::user();
    }

    public function updatePassword(User $user, string $oldPassword, string $newPassword): bool
    {
        if (!Hash::check($oldPassword, $user->password)) {
            throw new \Exception('Mot de passe actuel incorrect');
        }

        $user->update(['password' => Hash::make($newPassword)]);

        return true;
    }
}
