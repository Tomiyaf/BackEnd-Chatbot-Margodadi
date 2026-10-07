<?php

namespace App\Http\Controllers\Api;

use App\Enums\ChannelType;
use App\Enums\OperatorRole;
use App\Enums\OperatorStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Operator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Register a new operator and generate access token.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:operators,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:50'],
            'role' => ['nullable', 'string', 'in:ADMIN,OPERATOR'],
        ]);

        $operator = Operator::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'role' => isset($validated['role']) ? OperatorRole::from($validated['role']) : OperatorRole::OPERATOR,
            'status' => OperatorStatus::OFFLINE,
            'is_active' => true,
        ]);

        ActivityLog::create([
            'operator_id' => $operator->operator_id,
            'actor_name' => $operator->name,
            'action' => 'REGISTER',
            'target' => '#OP-'.str_pad($operator->operator_id, 2, '0', STR_PAD_LEFT),
            'description' => "Pendaftaran aparatur baru: {$operator->name} ({$operator->role->value})",
            'channel' => ChannelType::WEB,
            'created_at' => now(),
        ]);

        $token = $operator->createToken('operator_auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Operator registered successfully',
            'data' => [
                'operator' => $operator,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 201);
    }

    /**
     * Authenticate operator and generate access token.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['nullable', 'string'],
            'username' => ['nullable', 'string'],
            'password' => ['required', 'string'],
        ]);

        $identifier = $validated['email'] ?? $validated['username'] ?? $request->input('identifier');

        if (! $identifier) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email atau username wajib diisi.',
            ], 422);
        }

        $operator = Operator::where('email', $identifier)
            ->orWhere('email', $identifier.'@margodadi.desa.id')
            ->orWhereRaw('LOWER(name) LIKE ?', ['%'.strtolower($identifier).'%'])
            ->first();

        if (! $operator || ! Hash::check($validated['password'], $operator->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kombinasi username/email dan password tidak sesuai.',
            ], 401);
        }

        if (! $operator->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Your operator account has been deactivated.',
            ], 403);
        }

        $token = $operator->createToken('operator_auth_token')->plainTextToken;

        ActivityLog::create([
            'operator_id' => $operator->operator_id,
            'actor_name' => $operator->name,
            'action' => 'LOGIN',
            'target' => '#OP-'.str_pad($operator->operator_id, 2, '0', STR_PAD_LEFT),
            'description' => "Aparatur {$operator->name} ({$operator->role->value}) berhasil masuk ke Back-Office Pekon",
            'channel' => ChannelType::WEB,
            'created_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'operator' => $operator,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    /**
     * Get authenticated operator profile.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'operator' => $request->user(),
            ],
        ]);
    }

    /**
     * Log out operator by revoking current access token.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user) {
            ActivityLog::create([
                'operator_id' => $user->operator_id,
                'actor_name' => $user->name,
                'action' => 'LOGOUT',
                'target' => '#OP-'.str_pad($user->operator_id, 2, '0', STR_PAD_LEFT),
                'description' => "Aparatur {$user->name} keluar (logout) dari sesi Back-Office",
                'channel' => ChannelType::WEB,
                'created_at' => now(),
            ]);
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Successfully logged out',
        ]);
    }
}
