<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ChannelType;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\RagConfiguration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SettingController extends Controller
{
    /**
     * Get active RAG configuration.
     */
    public function getRag(): JsonResponse
    {
        $rag = RagConfiguration::where('is_active', true)->latest('created_at')->first();

        if (! $rag) {
            $rag = RagConfiguration::create([
                'version' => 'v1.0-prod',
                'embedding_model' => 'text-embedding-3-small',
                'llm_model' => 'gpt-4o-mini',
                'chunking_config' => ['size' => 500, 'overlap' => 50],
                'retrieval_config' => ['top_k' => 4, 'min_similarity' => 0.75],
                'generation_config' => ['temperature' => 0.3, 'max_tokens' => 1000],
                'is_active' => true,
                'created_at' => now(),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $rag,
        ]);
    }

    /**
     * Update or create active RAG configuration.
     */
    public function updateRag(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'version' => ['nullable', 'string', 'max:50'],
            'embedding_model' => ['required', 'string', 'max:150'],
            'llm_model' => ['required', 'string', 'max:150'],
            'chunking_config' => ['nullable', 'array'],
            'retrieval_config' => ['nullable', 'array'],
            'generation_config' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $rag = RagConfiguration::where('is_active', true)->latest('created_at')->first();

        if ($rag) {
            $rag->update([
                'embedding_model' => $validated['embedding_model'],
                'llm_model' => $validated['llm_model'],
                'chunking_config' => $validated['chunking_config'] ?? $rag->chunking_config,
                'retrieval_config' => $validated['retrieval_config'] ?? $rag->retrieval_config,
                'generation_config' => $validated['generation_config'] ?? $rag->generation_config,
            ]);
        } else {
            $rag = RagConfiguration::create([
                'version' => $validated['version'] ?? ('v1.'.time()),
                'embedding_model' => $validated['embedding_model'],
                'llm_model' => $validated['llm_model'],
                'chunking_config' => $validated['chunking_config'] ?? ['size' => 500, 'overlap' => 50],
                'retrieval_config' => $validated['retrieval_config'] ?? ['top_k' => 4, 'min_similarity' => 0.75],
                'generation_config' => $validated['generation_config'] ?? ['temperature' => 0.3, 'max_tokens' => 1000],
                'is_active' => true,
                'created_at' => now(),
            ]);
        }

        $operator = $request->user();
        ActivityLog::create([
            'operator_id' => $operator->operator_id,
            'actor_name' => $operator->name,
            'action' => 'SETTING_CHANGE',
            'target' => '#RAG-CONFIG',
            'description' => 'Operator memperbarui konfigurasi RAG Engine (Model: '.$rag->llm_model.')',
            'channel' => ChannelType::WEB,
            'created_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Konfigurasi RAG berhasil disimpan.',
            'data' => $rag,
        ]);
    }

    /**
     * Update current operator profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $operator = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'max:150', 'unique:operators,email,'.$operator->operator_id.',operator_id'],
            'phone' => ['nullable', 'string', 'max:50'],
            'avatar_url' => ['nullable', 'string', 'max:500'],
        ]);

        if ($request->hasFile('avatar')) {
            $request->validate([
                'avatar' => ['image', 'mimes:jpeg,png,jpg,webp,svg', 'max:5120'],
            ]);
            $path = $request->file('avatar')->store('avatars', 'public');
            $operator->avatar_url = asset('storage/' . $path);
        } elseif ($request->has('avatar_url')) {
            $operator->avatar_url = $validated['avatar_url'];
        }

        $operator->name = $validated['name'];
        $operator->email = $validated['email'];
        $operator->phone = $validated['phone'] ?? null;
        $operator->save();

        ActivityLog::create([
            'operator_id' => $operator->operator_id,
            'actor_name' => $operator->name,
            'action' => 'PROFILE_UPDATE',
            'target' => '#OP-'.str_pad($operator->operator_id, 2, '0', STR_PAD_LEFT),
            'description' => "Aparatur {$operator->name} memperbarui rincian profil akun",
            'channel' => ChannelType::WEB,
            'created_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Profil aparatur berhasil diperbarui.',
            'data' => [
                'operator' => $operator->fresh(),
            ],
        ]);
    }

    /**
     * Update or reset operator avatar.
     */
    public function updateAvatar(Request $request): JsonResponse
    {
        $operator = $request->user();

        // 1. Reset to default (favicon)
        if ($request->boolean('reset') || $request->input('reset') === 'true' || $request->input('reset') === 1) {
            $operator->avatar_url = null;
            $operator->save();

            ActivityLog::create([
                'operator_id' => $operator->operator_id,
                'actor_name' => $operator->name,
                'action' => 'AVATAR_UPDATE',
                'target' => '#OP-'.str_pad($operator->operator_id, 2, '0', STR_PAD_LEFT),
                'description' => "Aparatur {$operator->name} mereset foto profil ke default favicon",
                'channel' => ChannelType::WEB,
                'created_at' => now(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Foto profil berhasil direset ke favicon bawaan.',
                'data' => [
                    'operator' => $operator->fresh(),
                ],
            ]);
        }

        // 2. Upload physical image file
        if ($request->hasFile('avatar')) {
            $request->validate([
                'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:5120'],
            ], [
                'avatar.required' => 'Mohon pilih berkas gambar foto profil.',
                'avatar.image' => 'File harus berupa gambar.',
                'avatar.mimes' => 'Format gambar yang didukung: JPEG, PNG, JPG, WebP, SVG.',
                'avatar.max' => 'Ukuran gambar maksimal 5MB.',
            ]);

            $path = $request->file('avatar')->store('avatars', 'public');
            $operator->avatar_url = asset('storage/' . $path);
            $operator->save();

            ActivityLog::create([
                'operator_id' => $operator->operator_id,
                'actor_name' => $operator->name,
                'action' => 'AVATAR_UPDATE',
                'target' => '#OP-'.str_pad($operator->operator_id, 2, '0', STR_PAD_LEFT),
                'description' => "Aparatur {$operator->name} memperbarui foto profil",
                'channel' => ChannelType::WEB,
                'created_at' => now(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Foto profil berhasil diperbarui.',
                'data' => [
                    'operator' => $operator->fresh(),
                ],
            ]);
        }

        // 3. Base64 image upload fallback
        $base64Input = $request->input('avatar_base64') ?? $request->input('avatar');
        if (is_string($base64Input) && preg_match('/^data:image\/(\w+);base64,/', $base64Input, $matches)) {
            $imageType = strtolower($matches[1]);
            if ($imageType === 'jpeg') {
                $imageType = 'jpg';
            }
            $cleanBase64 = substr($base64Input, strpos($base64Input, ',') + 1);
            $binaryData = base64_decode($cleanBase64);

            if ($binaryData !== false) {
                $fileName = 'avatars/avatar_' . $operator->operator_id . '_' . time() . '.' . $imageType;
                \Illuminate\Support\Facades\Storage::disk('public')->put($fileName, $binaryData);
                $operator->avatar_url = asset('storage/' . $fileName);
                $operator->save();

                ActivityLog::create([
                    'operator_id' => $operator->operator_id,
                    'actor_name' => $operator->name,
                    'action' => 'AVATAR_UPDATE',
                    'target' => '#OP-'.str_pad($operator->operator_id, 2, '0', STR_PAD_LEFT),
                    'description' => "Aparatur {$operator->name} memperbarui foto profil",
                    'channel' => ChannelType::WEB,
                    'created_at' => now(),
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Foto profil berhasil diperbarui.',
                    'data' => [
                        'operator' => $operator->fresh(),
                    ],
                ]);
            }
        }

        // 4. Direct URL
        if ($request->filled('avatar_url')) {
            $request->validate([
                'avatar_url' => ['required', 'string', 'max:500'],
            ]);

            $operator->avatar_url = $request->input('avatar_url');
            $operator->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Foto profil berhasil diperbarui.',
                'data' => [
                    'operator' => $operator->fresh(),
                ],
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Mohon pilih berkas gambar foto profil.',
        ], 422);
    }

    /**
     * Change operator password.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:3'],
        ]);

        $operator = $request->user();

        if (! Hash::check($validated['current_password'], $operator->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kata sandi saat ini tidak sesuai.',
            ], 422);
        }

        $operator->password = Hash::make($validated['new_password']);
        $operator->save();

        ActivityLog::create([
            'operator_id' => $operator->operator_id,
            'actor_name' => $operator->name,
            'action' => 'PASSWORD_CHANGE',
            'target' => '#OP-'.str_pad($operator->operator_id, 2, '0', STR_PAD_LEFT),
            'description' => "Aparatur {$operator->name} memperbarui kata sandi akun",
            'channel' => ChannelType::WEB,
            'created_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Kata sandi berhasil diperbarui.',
        ]);
    }
}
