<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Models\Member;
use App\Models\Subscription;

Route::get('/test', function () {
    return response()->json([
        'status' => 'API funcionando',
        'service' => 'members-service',
    ]);
});

Route::post('/email/send-code', [AuthController::class, 'sendEmailCode']);
Route::post('/email/verify-code', [AuthController::class, 'verifyEmailCode']);
Route::post('/password/reset', [AuthController::class, 'resetPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/auth/password/reset', [AuthController::class, 'resetPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

Route::post('/check-access', function (Request $request) {
    $request->validate([
        'email' => ['required', 'email'],
    ]);

    $member = Member::where('email', $request->email)->first();

    if (! $member) {
        return response()->json([
            'allowed' => false,
            'reason' => 'member_not_found',
        ], 404);
    }

    $subscription = Subscription::where('member_id', $member->id)
        ->where('status', 'active')
        ->first();

    if (! $subscription) {
        return response()->json([
            'allowed' => false,
            'reason' => 'no_active_subscription',
            'member_id' => $member->id,
        ]);
    }

    return response()->json([
        'allowed' => true,
        'member_id' => $member->id,
        'subscription_id' => $subscription->id,
        'plan_id' => $subscription->plan_id,
        'status' => $subscription->status,
        'starts_at' => $subscription->starts_at,
        'ends_at' => $subscription->ends_at,
    ]);
});
