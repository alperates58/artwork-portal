<?php

use App\Http\Controllers\Api\V1\ArtworkApiController;
use Illuminate\Support\Facades\Route;

/*
 * Lider Portal — REST API v1
 * Auth: Sanctum token (Bearer)
 *
 * Tüm endpoint'ler auth:sanctum middleware ile korunur.
 * Token oluşturma: POST /api/v1/auth/token (web login sonrası)
 */

Route::prefix('v1')->name('api.v1.')->middleware(['auth:sanctum'])->group(function () {

    // ── Siparişler ──────────────────────────────────────────────
    Route::get('/orders',            [ArtworkApiController::class, 'orders'])->name('orders.index');
    Route::get('/orders/{orderNo}',  [ArtworkApiController::class, 'orderDetail'])->name('orders.show');

    // ── Artwork indirme ─────────────────────────────────────────
    Route::get('/artworks/{revision}/download-url',
                                     [ArtworkApiController::class, 'downloadUrl'])->name('artworks.download-url');

    // ── ERP push (sadece admin token) ───────────────────────────
    Route::post('/orders',           [ArtworkApiController::class, 'pushOrder'])->name('orders.push');
});

// Token oluşturma (web auth sonrası)
Route::post('/v1/auth/token', function (\Illuminate\Http\Request $request) {
    $request->validate([
        'email'       => ['required', 'email'],
        'password'    => ['required'],
        'device_name' => ['required', 'string', 'max:50'],
    ]);

    $user = \App\Models\User::where('email', $request->email)->first();

    if (! $user || ! \Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
        return response()->json(['error' => 'Geçersiz kimlik bilgileri.'], 401);
    }

    if (! $user->is_active) {
        return response()->json(['error' => 'Hesap pasif.'], 403);
    }

    $token = $user->createToken($request->device_name)->plainTextToken;

    return response()->json([
        'token'      => $token,
        'token_type' => 'Bearer',
        'user'       => [
            'id'   => $user->id,
            'name' => $user->name,
            'role' => $user->role->value,
        ],
    ]);
})->middleware('throttle:5,1')->name('api.v1.auth.token');
