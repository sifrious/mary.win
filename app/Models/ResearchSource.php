<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Adopted from the clever/landing research library. Same table shape; only the
 * relations to tables mary.win carries are kept.
 */
class ResearchSource extends Model
{
    public const TYPE_PODCAST = 'podcast';

    public const TYPE_TALK = 'talk';

    public const TYPE_ARTICLE = 'article';

    public const TYPE_VIDEO = 'video';

    public const TYPE_GITHUB = 'github';

    public const TYPE_DOC = 'doc';

    public const TYPE_BOOK = 'book';

    public const TYPE_CHAT = 'chat';

    public const STATUS_FULL_TEXT = 'full-text';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_SHOW_NOTES = 'show-notes-only';

    public const STATUS_UNAVAILABLE = 'unavailable';

    public const STATUS_UNKNOWN = 'unknown';

    protected $fillable = [
        'source_tag', 'title', 'author', 'type', 'is_visible', 'url', 'date_published', 'retrieved_at',
        'retrieval_method', 'artifact_status', 'vault_path', 'summary', 'body_markdown',
        'body_chars', 'body_hash', 'missing_from_vault_at',
    ];

    protected function casts(): array
    {
        return [
            'date_published' => 'date',
            'retrieved_at' => 'datetime',
            'missing_from_vault_at' => 'datetime',
            'body_chars' => 'integer',
            'is_visible' => 'boolean',
        ];
    }

    /**
     * The talks worth pointing at from the public site: type `talk`, visible.
     * Unordered on purpose — callers decide (the home page shuffles).
     *
     * @param  Builder<$this>  $query
     */
    public function scopeLovedTalks(Builder $query): void
    {
        $query->where('type', self::TYPE_TALK)
            ->where('is_visible', true);
    }

    /**
     * @return BelongsToMany<Person, $this>
     */
    public function people(): BelongsToMany
    {
        return $this->belongsToMany(Person::class)
            ->withPivot('role', 'position')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Talk, $this>
     */
    public function talks(): BelongsToMany
    {
        return $this->belongsToMany(Talk::class, 'research_source_talk')
            ->withPivot('source_tag_local')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Topic, $this>
     */
    public function topics(): BelongsToMany
    {
        return $this->belongsToMany(Topic::class, 'research_source_topic')
            ->withTimestamps();
    }
}
