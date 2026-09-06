<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Adopted from the clever/landing research library. Same table shape; only the
 * relations to tables mary.win carries are kept.
 */
class Talk extends Model
{
    public const STATUS_DRAFTING = 'drafting';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'slug', 'title', 'venue', 'event_date', 'thesis', 'vault_root', 'status',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsToMany<ResearchSource, $this>
     */
    public function sources(): BelongsToMany
    {
        return $this->belongsToMany(ResearchSource::class, 'research_source_talk')
            ->withPivot('source_tag_local')
            ->withTimestamps();
    }

    /**
     * @return HasMany<Topic, $this>
     */
    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class);
    }
}
