<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Adopted from the clever/landing research library. Same table shape; only the
 * relations to tables mary.win carries are kept.
 */
class Person extends Model
{
    public const KIND_PERSON = 'person';

    public const KIND_ORG = 'org';

    public const KIND_PSEUDONYM = 'pseudonym';

    protected $fillable = [
        'slug', 'name', 'sort_name', 'kind', 'bio', 'url',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsToMany<ResearchSource, $this>
     */
    public function researchSources(): BelongsToMany
    {
        return $this->belongsToMany(ResearchSource::class)
            ->withPivot('role', 'position')
            ->withTimestamps();
    }
}
