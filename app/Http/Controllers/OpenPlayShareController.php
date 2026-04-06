<?php

namespace App\Http\Controllers;

use App\Models\OpenPlaySession;
use App\Models\OpenPlayShare;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OpenPlayShareController extends Controller
{
    private const MAX_PAYLOAD_BYTES = 512000;

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'data' => ['required', 'array'],
        ]);

        $playerCount = is_array($validated['data']['players'] ?? null)
            ? count($validated['data']['players'])
            : 0;
        if ($playerCount > OpenPlaySession::MAX_PLAYERS_PER_SESSION) {
            return response()->json([
                'message' => sprintf(
                    'PickleGameQ allows at most %d players per session.',
                    OpenPlaySession::MAX_PLAYERS_PER_SESSION,
                ),
            ], 422);
        }

        $encoded = json_encode($validated['data']);
        if ($encoded === false || strlen($encoded) > self::MAX_PAYLOAD_BYTES) {
            return response()->json(['message' => 'Payload too large.'], 422);
        }

        $secret = Str::random(48);
        $share = OpenPlayShare::query()->create([
            'uuid' => (string) Str::uuid(),
            'secret_hash' => Hash::make($secret),
            'payload' => $validated['data'],
        ]);

        return response()->json(
            [
                'uuid' => $share->uuid,
                'secret' => $secret,
                'watch_url' => route('open-play.watch', $share),
            ],
            201,
        );
    }

    public function update(Request $request, OpenPlayShare $openPlayShare): JsonResponse
    {
        $validated = $request->validate([
            'secret' => ['required', 'string'],
            'data' => ['required', 'array'],
        ]);

        if (! Hash::check($validated['secret'], $openPlayShare->secret_hash)) {
            return response()->json(['message' => 'Invalid share credentials.'], 403);
        }

        $playerCount = is_array($validated['data']['players'] ?? null)
            ? count($validated['data']['players'])
            : 0;
        if ($playerCount > OpenPlaySession::MAX_PLAYERS_PER_SESSION) {
            return response()->json([
                'message' => sprintf(
                    'PickleGameQ allows at most %d players per session.',
                    OpenPlaySession::MAX_PLAYERS_PER_SESSION,
                ),
            ], 422);
        }

        $encoded = json_encode($validated['data']);
        if ($encoded === false || strlen($encoded) > self::MAX_PAYLOAD_BYTES) {
            return response()->json(['message' => 'Payload too large.'], 422);
        }

        $openPlayShare->update([
            'payload' => $validated['data'],
        ]);

        return response()->json([
            'updated_at' => $openPlayShare->fresh()->updated_at->toIso8601String(),
        ]);
    }

    public function destroy(Request $request, OpenPlayShare $openPlayShare): JsonResponse
    {
        $validated = $request->validate([
            'secret' => ['required', 'string'],
        ]);

        if (! Hash::check($validated['secret'], $openPlayShare->secret_hash)) {
            return response()->json(['message' => 'Invalid share credentials.'], 403);
        }

        $openPlayShare->delete();

        return response()->json(['ok' => true]);
    }

    public function data(OpenPlayShare $openPlayShare): JsonResponse
    {
        return response()->json([
            'updated_at' => $openPlayShare->updated_at->toIso8601String(),
            'data' => $openPlayShare->payload,
        ]);
    }
}
