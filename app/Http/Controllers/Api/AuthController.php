<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    private const PASSWORD_RESET_CODE_TTL_MINUTES = 15;

    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Credenciais invalidas.'
            ], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Login realizado com sucesso.',
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout realizado com sucesso.'
        ]);
    }

    public function sendEmailCode(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = strtolower(trim($data['email']));
        // Keep code delivery generic so the shared endpoint still works for registration.

        $code = (string) random_int(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => Hash::make($code),
                'created_at' => now(),
            ],
        );

        Mail::raw(
            "Seu codigo de verificacao Fiscotrans e: {$code}\n\n" .
            'Este codigo expira em ' . self::PASSWORD_RESET_CODE_TTL_MINUTES . ' minutos.',
            function ($message) use ($email) {
                $message->to($email)->subject('Codigo de verificacao Fiscotrans');
            },
        );

        return response()->json([
            'message' => 'Codigo enviado com sucesso.',
        ]);
    }

    public function verifyEmailCode(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
        ]);

        if (! $this->isValidPasswordResetCode($data['email'], $data['code'])) {
            return response()->json([
                'message' => 'Codigo invalido ou expirado.',
            ], 422);
        }

        return response()->json([
            'message' => 'Codigo verificado com sucesso.',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->merge([
            'code' => $request->input('code', $request->input('codigo', $request->input('token'))),
            'password' => $request->input(
                'password',
                $request->input('nova_senha', $request->input('novaSenha', $request->input('senha'))),
            ),
        ]);

        $data = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $email = strtolower(trim($data['email']));

        if (! $this->isValidPasswordResetCode($email, $data['code'])) {
            return response()->json([
                'message' => 'Codigo invalido ou expirado.',
            ], 422);
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            return response()->json([
                'message' => 'E-mail nao encontrado.',
            ], 404);
        }

        $user->forceFill([
            'password' => Hash::make($data['password']),
        ])->save();

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        if (method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }

        return response()->json([
            'message' => 'Senha redefinida com sucesso.',
        ]);
    }

    private function isValidPasswordResetCode(string $email, string $code): bool
    {
        $record = DB::table('password_reset_tokens')
            ->where('email', strtolower(trim($email)))
            ->first();

        if (! $record || ! $record->created_at) {
            return false;
        }

        if (now()->parse($record->created_at)->lt(now()->subMinutes(self::PASSWORD_RESET_CODE_TTL_MINUTES))) {
            return false;
        }

        return Hash::check($code, $record->token);
    }
}
