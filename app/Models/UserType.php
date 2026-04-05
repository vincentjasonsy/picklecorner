<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserType extends Model
{
    use HasUuids;

    public const SLUG_SUPER_ADMIN = 'super_admin';

    public const SLUG_COURT_ADMIN = 'court_admin';

    public const SLUG_COURT_CLIENT_DESK = 'court_client_desk';

    public const SLUG_COACH = 'coach';

    public const SLUG_USER = 'user';

    protected $fillable = [
        'slug',
        'name',
        'description',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
