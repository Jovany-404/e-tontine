<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CycleController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\MembershipRequestController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\PublicInvitationController;
use App\Http\Controllers\Api\TontineController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Toutes les routes sont préfixées par /api (automatique) puis /v1 : /api/v1/...
Route::prefix('v1')->group(function () {

    // Vérification de l'état de l'API et de la base de données.
    Route::get('/health', HealthController::class);

    // ---------- Routes publiques d'authentification ----------
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

        Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])
            ->middleware('throttle:password-reset');
        Route::post('/reset-password', [PasswordResetController::class, 'reset'])
            ->middleware('throttle:password-reset-confirm');

        // Lien reçu par email : signé et expirant. Le nom "verification.verify" est utilisé par AppServiceProvider.
        Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
            ->middleware(['signed', 'throttle:6,1'])
            ->name('verification.verify');
    });

    // ---------- Invitation : aperçu public (sans compte) ----------
    // Un token qui n'a pas le bon format ne correspond à aucune route : 404 JSON.
    Route::get('/invitations/{token}', [PublicInvitationController::class, 'show'])
        ->middleware('throttle:invitation')
        ->where('token', '[A-Za-z0-9]{48}');

    // ---------- Routes protégées par token Bearer (email vérifié non exigé) ----------
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', [UserController::class, 'show']);
        Route::patch('/user', [ProfileController::class, 'update']);

        // 5 tentatives par heure : empêche de deviner le mot de passe actuel avec un token volé.
        Route::put('/user/password', [ProfileController::class, 'updatePassword'])
            ->middleware('throttle:5,60,password-change');

        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/logout-all', [AuthController::class, 'logoutAll']);

        Route::post('/auth/email/resend', [EmailVerificationController::class, 'resend'])
            ->middleware('throttle:verification');
    });

    // ---------- Routes protégées, email vérifié obligatoire ----------
    Route::middleware(['auth:sanctum', 'verified'])->group(function () {
        // {tontine} est résolu par public_id (ULID), jamais par l'ID interne.
        Route::get('/tontines', [TontineController::class, 'index']);
        Route::post('/tontines', [TontineController::class, 'store'])
            ->middleware('throttle:10,60,tontine-create');
        Route::get('/tontines/{tontine}', [TontineController::class, 'show']);
        Route::patch('/tontines/{tontine}', [TontineController::class, 'update']);
        Route::post('/tontines/{tontine}/cycles/start', [CycleController::class, 'start'])
            ->middleware('throttle:tontine-start');
        Route::scopeBindings()->group(function () {
            Route::get('/tontines/{tontine}/schedules', [PaymentController::class, 'schedules']);
            Route::get('/tontines/{tontine}/payments', [PaymentController::class, 'index']);
            Route::post('/tontines/{tontine}/schedules/{schedule}/payments', [PaymentController::class, 'store'])
                ->middleware('throttle:payment-record');
        });

        // Lien d'invitation (gérant uniquement, contrôlé par la policy).
        Route::get('/tontines/{tontine}/invitations', [InvitationController::class, 'index']);
        Route::post('/tontines/{tontine}/invitations', [InvitationController::class, 'store'])
            ->middleware('throttle:20,60,invitation-create');
        Route::delete('/tontines/{tontine}/invitations', [InvitationController::class, 'destroy']);

        // Demande via lien valide, puis décisions réservées au gérant de cette tontine.
        Route::post('/tontines/{tontine}/membership-requests', [MembershipRequestController::class, 'store'])
            ->middleware('throttle:membership-request');

        Route::scopeBindings()->group(function () {
            Route::get('/tontines/{tontine}/membership-requests/mine', [MembershipRequestController::class, 'mine']);
            Route::get('/tontines/{tontine}/membership-requests', [MembershipRequestController::class, 'index']);
            Route::post('/tontines/{tontine}/membership-requests/{membershipRequest}/approve', [MembershipRequestController::class, 'approve']);
            Route::post('/tontines/{tontine}/membership-requests/{membershipRequest}/reject', [MembershipRequestController::class, 'reject']);
        });
    });
});
