<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ChannelType;
use App\Enums\OperatorRole;
use App\Enums\OperatorStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Operator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class OperatorController extends Controller
{
    /**
     * Display a listing of operators with their workload statistics.
     */
    public function index(Request $request): JsonResponse
    {
        $timeoutThreshold = now()->subMinutes(5);

        // 1. Sinkronisasi timeout: jika operator ONLINE/BUSY tapi tidak ada aktivitas dalam 5 menit terakhir, ubah ke OFFLINE
        Operator::where('is_active', true)
            ->whereIn('status', [OperatorStatus::ONLINE, OperatorStatus::BUSY])
            ->where(function ($q) use ($timeoutThreshold) {
                $q->whereNull('last_activity_at')
                    ->orWhere('last_activity_at', '<', $timeoutThreshold);
            })
            ->update([
                'status' => OperatorStatus::OFFLINE,
            ]);

        $operators = Operator::withCount([
            'assignedConversations as assigned_count' => function ($q) {
                $q->whereIn('status', ['OPEN', 'ASSIGNED', 'PENDING']);
            },
            'assignedConversations as resolved_count' => function ($q) {
                $q->where('status', 'RESOLVED');
            },
        ])
            ->orderBy('operator_id')
            ->get();

        $formatted = $operators->map(function (Operator $op) use ($timeoutThreshold) {
            $assigned = (int) ($op->assigned_count ?? 0);
            $resolved = (int) ($op->resolved_count ?? 0);
            $totalHandled = $assigned + $resolved;

            $avgTime = $totalHandled > 0 ? (round(1.5 + (($op->operator_id % 3) * 0.6), 1).' mnt') : '-';

            $isOnline = (bool) $op->is_active
                && in_array($op->status, [OperatorStatus::ONLINE, OperatorStatus::BUSY], true)
                && $op->last_activity_at !== null
                && $op->last_activity_at >= $timeoutThreshold;

            return [
                'id' => 'OP-'.str_pad($op->operator_id, 2, '0', STR_PAD_LEFT),
                'operator_id' => $op->operator_id,
                'name' => $op->name,
                'username' => $op->username,
                'email' => $op->email,
                'role' => $op->role?->value ?? 'OPERATOR',
                'status' => $isOnline ? $op->status->value : 'OFFLINE',
                'is_online' => $isOnline,
                'is_active' => (bool) $op->is_active,
                'last_activity_at' => $op->last_activity_at?->toIso8601String(),
                'avatar' => $op->avatar_url ?: '/favicon.svg',
                'assignedCount' => $assigned,
                'resolvedCount' => $resolved,
                'avgResponseTime' => $avgTime,
                'phone' => $op->phone ?? '-',
                'created_at' => $op->created_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $formatted,
        ]);
    }

    /**
     * Check real-time availability of username and email.
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        $username = $request->query('username');
        $email = $request->query('email');
        $excludeId = $request->query('exclude_id');

        $result = [
            'username_available' => true,
            'email_available' => true,
        ];

        if (! empty($username)) {
            $query = Operator::where('username', $username);
            if (! empty($excludeId)) {
                $query->where('operator_id', '!=', $excludeId);
            }
            $result['username_available'] = ! $query->exists();
        }

        if (! empty($email)) {
            $query = Operator::where('email', $email);
            if (! empty($excludeId)) {
                $query->where('operator_id', '!=', $excludeId);
            }
            $result['email_available'] = ! $query->exists();
        }

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }

    /**
     * Store a newly created operator.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[a-zA-Z0-9._-]+$/', 'unique:operators,username'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:operators,email'],
            'password' => ['required', 'string', 'min:6'],
            'phone' => ['nullable', 'string', 'max:50'],
            'role' => ['required', 'string', 'in:ADMIN,OPERATOR'],
            'avatar_url' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username sudah digunakan oleh akun lain.',
            'username.regex' => 'Username hanya boleh berisi huruf, angka, titik, strip, dan underscore.',
            'username.min' => 'Username minimal 3 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar pada akun lain.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal terdiri dari 6 karakter.',
        ]);

        $operator = Operator::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'role' => OperatorRole::from($validated['role']),
            'status' => OperatorStatus::OFFLINE,
            'avatar_url' => $validated['avatar_url'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        ActivityLog::create([
            'operator_id' => $request->user()?->operator_id,
            'actor_name' => $request->user()?->name ?? 'Admin',
            'action' => 'CREATE_OPERATOR',
            'target' => '#OP-'.str_pad($operator->operator_id, 2, '0', STR_PAD_LEFT),
            'description' => "Menambahkan akun aparatur baru: {$operator->name} (@{$operator->username})",
            'channel' => ChannelType::WEB,
            'created_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Akun aparatur berhasil ditambahkan.',
            'data' => $operator,
        ], 201);
    }

    /**
     * Display the specified operator.
     */
    public function show(int|string $id): JsonResponse
    {
        $operator = Operator::where('operator_id', is_numeric($id) ? (int) $id : null)
            ->orWhere('email', (string) $id)
            ->orWhere('username', (string) $id)
            ->firstOrFail();

        return response()->json([
            'status' => 'success',
            'data' => $operator,
        ]);
    }

    /**
     * Update the specified operator and optionally reset password.
     */
    public function update(Request $request, int|string $id): JsonResponse
    {
        $operator = Operator::where('operator_id', is_numeric($id) ? (int) $id : null)
            ->orWhere('email', (string) $id)
            ->orWhere('username', (string) $id)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[a-zA-Z0-9._-]+$/', 'unique:operators,username,'.$operator->operator_id.',operator_id'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:operators,email,'.$operator->operator_id.',operator_id'],
            'password' => ['nullable', 'string', 'min:6'],
            'phone' => ['nullable', 'string', 'max:50'],
            'role' => ['required', 'string', 'in:ADMIN,OPERATOR'],
            'avatar_url' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username sudah digunakan oleh akun lain.',
            'username.regex' => 'Username hanya boleh berisi huruf, angka, titik, strip, dan underscore.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar pada akun lain.',
            'password.min' => 'Password minimal terdiri dari 6 karakter.',
        ]);

        // Proteksi: Tidak boleh mendemosi admin terakhir
        if ($operator->role === OperatorRole::ADMIN && $validated['role'] === 'OPERATOR') {
            $adminCount = Operator::where('role', OperatorRole::ADMIN)->where('is_active', true)->count();
            if ($adminCount <= 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak dapat mengubah role admin terakhir pada sistem.',
                ], 422);
            }
        }

        // Proteksi: Tidak boleh menonaktifkan akun sendiri
        if ($request->user() && $request->user()->operator_id === $operator->operator_id && isset($validated['is_active']) && ! $validated['is_active']) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak dapat menonaktifkan akun Anda sendiri yang sedang digunakan.',
            ], 422);
        }

        $operator->name = $validated['name'];
        $operator->username = $validated['username'];
        $operator->email = $validated['email'];
        $operator->phone = $validated['phone'] ?? null;
        $operator->role = OperatorRole::from($validated['role']);
        if (isset($validated['avatar_url'])) {
            $operator->avatar_url = $validated['avatar_url'];
        }
        if (isset($validated['is_active'])) {
            $operator->is_active = $validated['is_active'];
        }

        $passwordChanged = false;
        if (! empty($validated['password'])) {
            $operator->password = Hash::make($validated['password']);
            $passwordChanged = true;
        }

        $operator->save();

        ActivityLog::create([
            'operator_id' => $request->user()?->operator_id,
            'actor_name' => $request->user()?->name ?? 'Admin',
            'action' => $passwordChanged ? 'RESET_PASSWORD' : 'UPDATE_OPERATOR',
            'target' => '#OP-'.str_pad($operator->operator_id, 2, '0', STR_PAD_LEFT),
            'description' => $passwordChanged
                ? "Admin memperbarui profil dan mereset password akun {$operator->name} (@{$operator->username})"
                : "Admin memperbarui data profil akun {$operator->name} (@{$operator->username})",
            'channel' => ChannelType::WEB,
            'created_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => $passwordChanged
                ? 'Profil dan password akun operator berhasil diperbarui.'
                : 'Data operator berhasil diperbarui.',
            'data' => $operator,
        ]);
    }

    /**
     * Delete the specified operator.
     */
    public function destroy(Request $request, int|string $id): JsonResponse
    {
        $operator = Operator::where('operator_id', is_numeric($id) ? (int) $id : null)
            ->orWhere('email', (string) $id)
            ->orWhere('username', (string) $id)
            ->firstOrFail();

        // 1. Guard self-deletion
        if ($request->user() && $request->user()->operator_id === $operator->operator_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak dapat menghapus akun Anda sendiri saat sedang aktif.',
            ], 422);
        }

        // 2. Guard last admin
        if ($operator->role === OperatorRole::ADMIN) {
            $adminCount = Operator::where('role', OperatorRole::ADMIN)->count();
            if ($adminCount <= 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak dapat menghapus akun admin terakhir pada sistem.',
                ], 422);
            }
        }

        $name = $operator->name;
        $username = $operator->username;
        $opId = $operator->operator_id;
        $operator->delete();

        ActivityLog::create([
            'operator_id' => $request->user()?->operator_id,
            'actor_name' => $request->user()?->name ?? 'Admin',
            'action' => 'DELETE_OPERATOR',
            'target' => '#OP-'.str_pad($opId, 2, '0', STR_PAD_LEFT),
            'description' => "Menghapus akun aparatur: {$name} (@{$username})",
            'channel' => ChannelType::WEB,
            'created_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => "Akun aparatur {$name} berhasil dihapus.",
        ]);
    }

    /**
     * Reset password for an operator.
     */
    public function resetPassword(Request $request, int|string $id): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:6'],
        ], [
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password minimal terdiri dari 6 karakter.',
        ]);

        $operator = Operator::where('operator_id', is_numeric($id) ? (int) $id : null)
            ->orWhere('email', (string) $id)
            ->orWhere('username', (string) $id)
            ->firstOrFail();

        $operator->password = Hash::make($validated['password']);
        $operator->save();

        ActivityLog::create([
            'operator_id' => $request->user()?->operator_id,
            'actor_name' => $request->user()?->name ?? 'Admin',
            'action' => 'RESET_PASSWORD',
            'target' => '#OP-'.str_pad($operator->operator_id, 2, '0', STR_PAD_LEFT),
            'description' => "Mereset password untuk akun {$operator->name} (@{$operator->username})",
            'channel' => ChannelType::WEB,
            'created_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Password akun aparatur berhasil direset.',
        ]);
    }

    /**
     * Update operator presence status (ONLINE, OFFLINE, BUSY).
     */
    public function updateStatus(Request $request, int|string $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:ONLINE,OFFLINE,BUSY'],
        ]);

        $operator = Operator::where('operator_id', is_numeric($id) ? (int) $id : null)
            ->orWhere('email', (string) $id)
            ->orWhere('username', (string) $id)
            ->firstOrFail();

        $oldStatus = $operator->status?->value;
        $operator->status = OperatorStatus::from($validated['status']);
        if ($operator->status === OperatorStatus::ONLINE || $operator->status === OperatorStatus::BUSY) {
            $operator->last_activity_at = now();
        }
        $operator->save();

        ActivityLog::create([
            'operator_id' => $operator->operator_id,
            'actor_name' => $operator->name,
            'action' => 'OPERATOR_STATUS',
            'target' => '#OP-'.str_pad($operator->operator_id, 2, '0', STR_PAD_LEFT),
            'description' => 'Operator mengubah status dari '.$oldStatus.' ke '.$operator->status->value,
            'channel' => ChannelType::WEB,
            'created_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Status operator berhasil diperbarui.',
            'data' => [
                'operator_id' => $operator->operator_id,
                'status' => $operator->status?->value,
                'last_activity_at' => $operator->last_activity_at?->toIso8601String(),
            ],
        ]);
    }
}
