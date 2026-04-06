<?php

namespace App\Http\Controllers;

use App\Models\OpenPlaySession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OpenPlaySessionController extends Controller
{
    private const MAX_PAYLOAD_BYTES = 512000;

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:120'],
            'month' => ['sometimes', 'nullable', 'string', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        $q = isset($validated['q']) ? trim((string) $validated['q']) : '';
        $month = $validated['month'] ?? null;

        $sessions = $request->user()
            ->openPlaySessions()
            ->filterHistory($q === '' ? null : $q)
            ->hostedInMonth($month)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get(['id', 'title', 'created_at', 'updated_at']);

        return response()->json([
            'sessions' => $sessions->map(fn (OpenPlaySession $s) => [
                'id' => $s->id,
                'title' => $s->title,
                'created_at' => $s->created_at->toIso8601String(),
                'updated_at' => $s->updated_at->toIso8601String(),
            ]),
            'quota' => OpenPlaySession::quotaForUser($request->user()),
            'filters' => [
                'q' => $q,
                'month' => $month,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:120'],
            'data' => ['required', 'array'],
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

        $quota = OpenPlaySession::quotaForUser($request->user());
        if ($quota['remaining'] <= 0) {
            return response()->json([
                'message' => sprintf(
                    'You can save up to %d GameQ sessions to your account each calendar month. The limit resets at the start of next month.',
                    OpenPlaySession::MONTHLY_SAVE_LIMIT,
                ),
                'quota' => $quota,
            ], 422);
        }

        $encoded = json_encode($validated['data']);
        if ($encoded === false || strlen($encoded) > self::MAX_PAYLOAD_BYTES) {
            return response()->json(['message' => 'Session data too large.'], 422);
        }

        $rawTitle = isset($validated['title']) ? trim((string) $validated['title']) : '';
        $title = $rawTitle !== ''
            ? $rawTitle
            : 'Hosted · '.now()->timezone(config('app.timezone'))->format('M j, Y g:i a');

        $session = $request->user()->openPlaySessions()->create([
            'title' => $title,
            'payload' => $validated['data'],
        ]);

        return response()->json([
            'session' => [
                'id' => $session->id,
                'title' => $session->title,
                'created_at' => $session->created_at->toIso8601String(),
                'updated_at' => $session->updated_at->toIso8601String(),
            ],
            'quota' => OpenPlaySession::quotaForUser($request->user()),
        ], 201);
    }

    public function show(Request $request, OpenPlaySession $openPlaySession): JsonResponse
    {
        $this->authorize('view', $openPlaySession);

        return response()->json([
            'session' => [
                'id' => $openPlaySession->id,
                'title' => $openPlaySession->title,
                'updated_at' => $openPlaySession->updated_at->toIso8601String(),
                'payload' => $openPlaySession->payload,
            ],
        ]);
    }

    public function update(Request $request, OpenPlaySession $openPlaySession): JsonResponse
    {
        $this->authorize('update', $openPlaySession);

        $validated = $request->validate([
            'title' => ['sometimes', 'nullable', 'string', 'max:120'],
        ]);

        $openPlaySession->update($validated);
        $openPlaySession->refresh();

        return response()->json([
            'session' => [
                'id' => $openPlaySession->id,
                'title' => $openPlaySession->title,
                'updated_at' => $openPlaySession->updated_at->toIso8601String(),
            ],
        ]);
    }

    public function destroy(Request $request, OpenPlaySession $openPlaySession): JsonResponse
    {
        $this->authorize('delete', $openPlaySession);

        $openPlaySession->delete();

        return response()->json(['ok' => true]);
    }
}
