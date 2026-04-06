<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpenPlaySession extends Model
{
    /** Max new account snapshots a user may create per calendar month (app timezone). */
    public const MONTHLY_SAVE_LIMIT = 5;

    /** Max players in one GameQ session (queue / roster). */
    public const MAX_PLAYERS_PER_SESSION = 55;

    protected $fillable = [
        'user_id',
        'title',
        'payload',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function countCreatedThisMonthForUser(User $user): int
    {
        $start = now()->startOfMonth();
        $end = now()->copy()->endOfMonth();

        return static::query()
            ->where('user_id', $user->getKey())
            ->whereBetween('created_at', [$start, $end])
            ->count();
    }

    /**
     * @return array{limit: int, used: int, remaining: int, resets_at: string}
     */
    public static function quotaForUser(User $user): array
    {
        $limit = self::MONTHLY_SAVE_LIMIT;
        $used = self::countCreatedThisMonthForUser($user);

        return [
            'limit' => $limit,
            'used' => $used,
            'remaining' => max(0, $limit - $used),
            'resets_at' => now()->copy()->startOfMonth()->addMonth()->toIso8601String(),
        ];
    }

    /**
     * Filter hosted-session history by search text (title or JSON payload, e.g. player names).
     *
     * @param  Builder<OpenPlaySession>  $query
     * @return Builder<OpenPlaySession>
     */
    public function scopeFilterHistory(Builder $query, ?string $search): Builder
    {
        if ($search === null) {
            return $query;
        }
        $t = trim($search);
        if ($t === '') {
            return $query;
        }

        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $t);
        $needle = '%'.$escaped.'%';

        return $query->where(function (Builder $w) use ($needle): void {
            $w->where('title', 'like', $needle)
                ->orWhere('payload', 'like', $needle);
        });
    }

    /**
     * @param  Builder<OpenPlaySession>  $query
     * @return Builder<OpenPlaySession>
     */
    public function scopeHostedInMonth(Builder $query, ?string $yearMonth): Builder
    {
        if ($yearMonth === null || $yearMonth === '' || ! preg_match('/^\d{4}-\d{2}$/', $yearMonth)) {
            return $query;
        }

        [$ys, $ms] = explode('-', $yearMonth, 2);
        $y = (int) $ys;
        $m = (int) $ms;

        return $query->whereYear('created_at', $y)->whereMonth('created_at', $m);
    }
}
