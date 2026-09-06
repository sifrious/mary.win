<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Adopted from the clever/landing research library. Same table shape; only the
 * relations to tables mary.win carries are kept.
 */
class Topic extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'talk_id',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<Talk, $this>
     */
    public function talk(): BelongsTo
    {
        return $this->belongsTo(Talk::class);
    }

    /**
     * @return BelongsToMany<ResearchSource, $this>
     */
    public function researchSources(): BelongsToMany
    {
        return $this->belongsToMany(ResearchSource::class, 'research_source_topic')
            ->withTimestamps();
    }
}
