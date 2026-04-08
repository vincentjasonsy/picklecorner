<?php

namespace App\Models;

use App\Support\PublicStorageUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourtGalleryImage extends Model
{
    use HasUuids;

    protected $table = 'court_gallery_images';

    protected $fillable = [
        'court_id',
        'path',
        'sort_order',
        'alt_text',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeApproved($query)
    {
        return $query->whereNotNull('approved_at');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePendingApproval($query)
    {
        return $query->whereNull('approved_at');
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function publicUrl(): string
    {
        return PublicStorageUrl::forPath($this->path) ?? '';
    }
}
