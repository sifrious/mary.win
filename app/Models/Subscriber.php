<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A mailing-list address. Signups are ingested from the other sites, so an
 * address is unique across every source that might send it.
 */
class Subscriber extends Model
{
    protected $fillable = ['email', 'source', 'unsubscribed_at'];

    protected function casts(): array
    {
        return [
            'unsubscribed_at' => 'datetime',
        ];
    }

    public function isSubscribed(): bool
    {
        return $this->unsubscribed_at === null;
    }
}
