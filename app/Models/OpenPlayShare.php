<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpenPlayShare extends Model
{
    protected $fillable = [
        'uuid',
        'secret_hash',
        'payload',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
