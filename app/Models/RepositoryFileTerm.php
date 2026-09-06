<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepositoryFileTerm extends Model
{
    use HasFactory;

    protected $fillable = [
        'repository_file_id',
        'term_id',
        'user_id',
        'frequency',
    ];

    protected $casts = [
        'frequency' => 'integer',
    ];

    /**
     * Get the repository file that owns this term relationship
     */
    public function repositoryFile(): BelongsTo
    {
        return $this->belongsTo(RepositoryFiles::class, 'repository_file_id');
    }

    /**
     * Get the term that owns this relationship
     */
    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    /**
     * Get the user that owns this relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Increment or create term frequency for a file
     */
    public static function incrementTermFrequency(int $repositoryFileId, int $termId, int $userId, int $increment = 1): self
    {
        $relationship = static::where([
            'repository_file_id' => $repositoryFileId,
            'term_id' => $termId,
            'user_id' => $userId,
        ])->first();

        if ($relationship) {
            $relationship->increment('frequency', $increment);
        } else {
            $relationship = static::create([
                'repository_file_id' => $repositoryFileId,
                'term_id' => $termId,
                'user_id' => $userId,
                'frequency' => $increment,
            ]);
        }

        return $relationship;
    }
}
