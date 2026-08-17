<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()
            ->where('email', $request->validated('email'))
            ->first();

        if (
            ! $user
            || ! Hash::check(
                $request->validated('password'),
                $user->password
            )
        ) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 422);
        }

        // Mantém somente um token ativo para esta aplicação.
        $user->tokens()
            ->where('name', 'amar-assist-web')
            ->delete();

        $token = $user
            ->createToken('amar-assist-web')
            ->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()
            ->currentAccessToken()
            ->delete();

        return response()->json([
            'message' => 'Logged out.',
        ]);
    }
}