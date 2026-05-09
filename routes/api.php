<?php

use App\Http\Controllers\Api\AsaasWebhookController;
use App\Http\Controllers\Api\Calculators\CalculatorController;
use App\Http\Controllers\Api\LiveCourseCheckoutController;
use App\Http\Controllers\Api\LiveCourseController;
use App\Http\Controllers\Api\MemberContextController;
use App\Models\Member;
use App\Models\Permission;
use App\Models\Role;
use App\Services\MemberPermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

Route::post('/member-context', MemberContextController::class);

Route::post('/webhooks/asaas', [AsaasWebhookController::class, 'handle'])
    ->name('webhooks.asaas');

Route::middleware('fiscotrans.key')->group(function () {

    Route::post('/protected-areas', function (Request $request) {
        $areas = Permission::query()
            ->where('slug', 'like', '%.access')
            ->pluck('slug')
            ->map(function ($slug) {
                $slug = trim((string) $slug);
                if ($slug === '') {
                    return null;
                }

                return preg_replace('/\.access$/', '', $slug);
            })
            ->filter()->unique()->sort()->values()->all();

        return response()->json(['success' => true, 'areas' => $areas]);
    });

    Route::post('/sync-member', function (Request $request) {
        $email = trim((string) $request->input('email'));
        $roleSlug = trim((string) $request->input('role'));
        $plainPassword = (string) $request->input('password');
        if (! $email) {
            return response()->json(['success' => false, 'reason' => 'missing_email'], 422);
        }
        $member = Member::where('email', $email)->first();
        if (! $member) {
            $createData = [
                'wp_user_id' => $request->input('wp_user_id'),
                'name' => $request->input('name'),
                'email' => $email,
                'phone' => $request->input('phone'),
                'cpf_cnpj' => $request->input('cpf_cnpj'),
                'company_name' => $request->input('company_name'),
                'status' => $request->input('status') ?? 'active',
                'origin' => $request->input('origin') ?? 'wordpress',
            ];
            if ($plainPassword !== '') {
                $createData['password'] = Hash::make($plainPassword);
            }
            $member = Member::create($createData);
            $action = 'created';
        } else {
            $updateData = array_filter([
                'wp_user_id' => $request->input('wp_user_id'),
                'name' => $request->input('name'),
                'phone' => $request->input('phone'),
                'cpf_cnpj' => $request->input('cpf_cnpj'),
                'company_name' => $request->input('company_name'),
            ], fn ($v) => $v !== null && $v !== '');
            if ($plainPassword !== '') {
                $updateData['password'] = Hash::make($plainPassword);
            }
            if (! empty($updateData)) {
                $member->update($updateData);
            }
            $action = 'updated';
        }
        $roleAttached = false;
        $roleFound = false;
        if ($roleSlug !== '') {
            $role = Role::where('slug', $roleSlug)->first();
            if ($role) {
                $roleFound = true;
                if (method_exists($member, 'roles')) {
                    $member->roles()->syncWithoutDetaching([$role->id]);
                    $roleAttached = true;
                }
            }
        }

        return response()->json([
            'success' => true,
            'action' => $action,
            'member_id' => $member->id,
            'status' => $member->status,
            'role_sent' => $roleSlug ?: null,
            'role_found' => $roleFound,
            'role_attached' => $roleAttached,
            'password_synced' => $plainPassword !== '',
        ]);
    });

    Route::post('/check-access', function (Request $request) {
        $email = trim((string) $request->input('email'));
        if (! $email) {
            return response()->json(['allowed' => false, 'member_status' => null, 'reason' => 'missing_parameters'], 422);
        }
        $member = Member::with('roles')->where('email', $email)->first();
        if (! $member) {
            return response()->json(['allowed' => false, 'member_status' => null, 'reason' => 'member_not_found'], 404);
        }
        if (($member->status ?? null) !== 'active') {
            return response()->json(['allowed' => false, 'member_status' => $member->status, 'reason' => 'member_not_active'], 403);
        }
        $role = $member->roles->first()?->slug ?? null;
        $permissions = app(MemberPermissionService::class)->getPermissionsByMember($member);
        $allowed = ! empty($permissions);

        return response()->json([
            'allowed' => $allowed,
            'reason' => $allowed ? 'ok' : 'subscription_required',
            'member_status' => $member->status,
            'role' => $role,
            'permissions' => $permissions,
            'member_id' => $member->id,
            'email' => $member->email,
        ], $allowed ? 200 : 403);
    });

    Route::post('/check-permission', function (Request $request) {
        $email = trim((string) $request->input('email'));
        $permission = trim((string) $request->input('permission'));
        if (! $email || ! $permission) {
            return response()->json(['allowed' => false, 'reason' => 'missing_parameters'], 422);
        }
        $member = Member::where('email', $email)->first();
        if (! $member || $member->status !== 'active') {
            return response()->json(['allowed' => false, 'reason' => 'member_not_active'], 403);
        }
        $service = app(MemberPermissionService::class);
        $permissions = $service->getPermissionsByMember($member);
        $allowed = $service->hasPermission($member, $permission);

        return response()->json([
            'allowed' => $allowed,
            'permission' => $permission,
            'permissions' => $permissions,
        ], $allowed ? 200 : 403);
    });

    Route::post('/member-permissions', function (Request $request) {
        $email = trim((string) $request->input('email'));
        if (! $email) {
            return response()->json(['allowed' => false, 'member_status' => null, 'permissions' => [], 'reason' => 'missing_parameters'], 422);
        }
        $member = Member::where('email', $email)->first();
        if (! $member) {
            return response()->json(['allowed' => false, 'member_status' => null, 'permissions' => [], 'reason' => 'member_not_found'], 404);
        }
        if (($member->status ?? null) !== 'active') {
            return response()->json(['allowed' => false, 'member_status' => $member->status, 'permissions' => [], 'reason' => 'member_not_active'], 403);
        }
        $service = app(MemberPermissionService::class);
        $permissions = $service->getPermissionsByMember($member);
        $allowed = ! empty($permissions);

        return response()->json([
            'allowed' => $allowed,
            'reason' => $allowed ? 'ok' : 'subscription_required',
            'member_status' => $member->status,
            'permissions' => $permissions,
            'member_id' => $member->id,
            'email' => $member->email,
        ]);
    });
});

Route::prefix('v1')->group(function () {
    Route::get('/member-context', [\App\Http\Controllers\Api\V1\MemberContextController::class, 'show']);
    Route::post('/checkout', \App\Http\Controllers\Api\V1\CheckoutController::class);
    Route::get('/plans', function () {
        $type = request('type');
        $plans = \App\Models\Plan::with('permissions')
            ->where('active', true)
            ->when($type, fn ($q) => $q->where('type', $type))
            ->orderBy('type')->orderBy('price')->get()
            ->map(fn ($plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'type' => $plan->type,
                'description' => $plan->description,
                'image_url' => $plan->image_url,
                'badge_text' => $plan->badge_text,
                'price' => (float) $plan->price,
                'price_formatted' => $plan->formattedPrice(),
                'billing_cycle' => $plan->billing_cycle,
                'is_one_time' => $plan->billing_cycle === 'one_time',
                'permissions' => $plan->permissions->pluck('slug'),
            ]);

        return response()->json(['success' => true, 'plans' => $plans]);
    });
});

Route::get('/health', function () {
    return response()->json([
        'ok' => true,
        'app' => config('app.name'),
        'env' => app()->environment(),
        'timestamp' => now()->toDateTimeString(),
    ]);
});

Route::post('/login', function (Request $request) {
    $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
    ]);
    $email = trim((string) $request->input('email'));
    $password = (string) $request->input('password');
    $member = Member::where('email', $email)->first();
    if (! $member) {
        return response()->json(['success' => false, 'message' => 'E-mail ou senha inválidos.'], 401);
    }
    $passwordHash = $member->getAttribute('password');
    if (! is_string($passwordHash) || trim($passwordHash) === '') {
        return response()->json(['success' => false, 'message' => 'Este usuário não possui senha cadastrada.'], 401);
    }
    if (! Hash::check($password, $passwordHash)) {
        return response()->json(['success' => false, 'message' => 'E-mail ou senha inválidos.'], 401);
    }
    if (($member->status ?? null) !== 'active') {
        return response()->json(['success' => false, 'message' => 'Usuário sem acesso ativo.'], 403);
    }

    return response()->json([
        'success' => true,
        'message' => 'Login realizado com sucesso.',
        'member' => [
            'id' => $member->id,
            'name' => $member->name,
            'email' => $member->email,
            'status' => $member->status,
        ],
    ]);
});

Route::post('/email/send-code', function (Request $request) {
    $email = trim((string) $request->input('email'));
    if (! $email) {
        return response()->json(['success' => false, 'reason' => 'missing_email'], 422);
    }
    $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    Cache::put("email_verify_code_{$email}", $code, now()->addMinutes(10));
    $nome = Member::where('email', $email)->value('name') ?? '';
    Mail::raw(
        ($nome ? "Olá, {$nome}!\n\n" : "Olá!\n\n").
        "Seu código de verificação é: {$code}\n\nEste código expira em 10 minutos.",
        function ($message) use ($email) {
            $message->to($email)->subject('Código de verificação – Fiscotrans');
        }
    );

    return response()->json(['success' => true, 'message' => 'Código enviado para '.$email]);
});

Route::post('/email/verify-code', function (Request $request) {
    $email = trim((string) $request->input('email'));
    $code = trim((string) $request->input('code'));
    if (! $email || ! $code) {
        return response()->json(['success' => false, 'reason' => 'missing_parameters'], 422);
    }
    $cached = Cache::get("email_verify_code_{$email}");
    if (! $cached || $cached !== $code) {
        return response()->json(['success' => false, 'reason' => 'invalid_or_expired_code'], 422);
    }
    Cache::forget("email_verify_code_{$email}");
    Member::where('email', $email)->update(['email_verified_at' => now()]);

    return response()->json(['success' => true, 'message' => 'E-mail verificado com sucesso.']);
});

Route::post('/password/reset', function (Request $request) {
    $email = trim((string) $request->input('email'));
    $code = trim((string) $request->input('code', $request->input('codigo', $request->input('token'))));
    $password = (string) $request->input(
        'password',
        $request->input('senha', $request->input('novaSenha', $request->input('nova_senha')))
    );

    if (! $email || ! $code || ! $password) {
        return response()->json(['success' => false, 'reason' => 'missing_parameters'], 422);
    }

    if (strlen($password) < 8) {
        return response()->json(['success' => false, 'message' => 'A senha deve ter no minimo 8 caracteres.'], 422);
    }

    $cached = Cache::get("email_verify_code_{$email}");
    if (! $cached || $cached !== $code) {
        return response()->json(['success' => false, 'reason' => 'invalid_or_expired_code'], 422);
    }

    $member = Member::where('email', $email)->first();
    if (! $member) {
        return response()->json(['success' => false, 'message' => 'E-mail nao encontrado.'], 404);
    }

    $member->update([
        'password' => Hash::make($password),
        'email_verified_at' => now(),
    ]);

    Cache::forget("email_verify_code_{$email}");

    return response()->json(['success' => true, 'message' => 'Senha redefinida com sucesso.']);
});

Route::post('/register', function (Request $request) {
    $email = trim((string) $request->input('email'));
    $nome = trim((string) $request->input('first_name'));
    $sobre = trim((string) $request->input('last_name'));
    $senha = (string) $request->input('password');
    if (! $email || ! $nome || ! $senha) {
        return response()->json(['success' => false, 'message' => 'E-mail, nome e senha são obrigatórios.'], 422);
    }
    if (Member::where('email', $email)->exists()) {
        return response()->json(['success' => false, 'message' => 'Este e-mail já está cadastrado.'], 409);
    }
    $member = Member::create([
        'name' => trim("{$nome} {$sobre}"),
        'email' => $email,
        'password' => Hash::make($senha),
        'phone' => $request->input('phone'),
        'cpf_cnpj' => $request->input('cpf_cnpj'),
        'company_name' => $request->input('empresa'),
        'status' => 'active',
        'origin' => 'app',
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Cadastro realizado com sucesso.',
        'member_id' => $member->id,
    ], 201);
});

Route::prefix('calculators')->group(function () {
    Route::get('/', [CalculatorController::class, 'index']);
    Route::get('/{slug}', [CalculatorController::class, 'show']);
    Route::post('/{slug}/execute', [CalculatorController::class, 'execute']);
});

Route::post('/live-courses/checkout', [LiveCourseCheckoutController::class, 'store']);

Route::get('/live-courses', [LiveCourseController::class, 'index']);

use App\Http\Controllers\Api\IaConsultaController;

Route::post('/ia/perguntar', [IaConsultaController::class, 'perguntar']);
