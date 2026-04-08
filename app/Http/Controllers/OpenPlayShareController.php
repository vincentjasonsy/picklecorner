<?php

namespace App\Http\Controllers;

use App\Models\OpenPlaySession;
use App\Models\OpenPlayShare;
use App\Services\GameQShareToggleBreakPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OpenPlayShareController extends Controller
{
    private const MAX_PAYLOAD_BYTES = 512000;

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'data' => ['required', 'array'],
            'open_play_session_id' => [
                'nullable',
                'integer',
                Rule::exists('open_play_sessions', 'id')->where(
                    fn ($q) => $q->where('user_id', $request->user()->getKey()),
                ),
            ],
        ]);

        $playerCount = is_array($validated['data']['players'] ?? null)
            ? count($validated['data']['players'])
            : 0;
        if ($playerCount > OpenPlaySession::MAX_PLAYERS_PER_SESSION) {
            return response()->json([
                'message' => sprintf(
                    'GameQ allows at most %d players per session.',
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
            'open_play_session_id' => $validated['open_play_session_id'] ?? null,
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
                    'GameQ allows at most %d players per session.',
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
            'server_at_ms' => (int) round(microtime(true) * 1000),
            'data' => $openPlayShare->payload,
        ]);
    }

    /**
     * Public “take a break” toggle for live watch (self-confirm on the client).
     * Host can still override via the full share update.
     */
    public function togglePlayerBreak(Request $request, OpenPlayShare $openPlayShare): JsonResponse
    {
        $validated = $request->validate([
            'player_id' => ['required', 'string', 'max:128'],
            'skip_shuffle' => ['required', 'boolean'],
        ]);

        $payload = $openPlayShare->payload;
        if (! is_array($payload)) {
            $payload = [];
        }

        [$next, $err] = GameQShareToggleBreakPayload::apply(
            $payload,
            $validated['player_id'],
            $validated['skip_shuffle'],
        );

        if ($err !== null) {
            return response()->json(['message' => $err], 422);
        }

        $playerCount = is_array($next['players'] ?? null)
            ? count($next['players'])
            : 0;
        if ($playerCount > OpenPlaySession::MAX_PLAYERS_PER_SESSION) {
            return response()->json([
                'message' => sprintf(
                    'GameQ allows at most %d players per session.',
                    OpenPlaySession::MAX_PLAYERS_PER_SESSION,
                ),
            ], 422);
        }

        $encoded = json_encode($next);
        if ($encoded === false || strlen($encoded) > self::MAX_PAYLOAD_BYTES) {
            return response()->json(['message' => 'Payload too large.'], 422);
        }

        $openPlayShare->update([
            'payload' => $next,
        ]);

        $fresh = $openPlayShare->fresh();

        return response()->json([
            'updated_at' => $fresh->updated_at->toIso8601String(),
            'server_at_ms' => (int) round(microtime(true) * 1000),
            'data' => $fresh->payload,
        ]);
    }
}
