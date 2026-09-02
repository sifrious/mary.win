<?php

namespace App\Providers;

use App\Services\MailingList\MailingListSyncer;
use App\Services\MailingList\NullMailingListSyncer;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Mailing list providers this application knows how to talk to. Adding a
     * real email service provider means adding one entry here and pointing
     * MAILING_LIST_SYNCER at it; no calling code changes.
     */
    protected const MAILING_LIST_SYNCERS = [
        'null' => NullMailingListSyncer::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(MailingListSyncer::class, function (): MailingListSyncer {
            $driver = (string) config('mailing-list.syncer');

            // Fail loudly at resolution rather than silently dropping people
            // from the downstream list because of a typo in configuration.
            if (! array_key_exists($driver, self::MAILING_LIST_SYNCERS)) {
                throw new InvalidArgumentException(
                    "Unsupported MAILING_LIST_SYNCER [{$driver}]. Supported: "
                    .implode(', ', array_keys(self::MAILING_LIST_SYNCERS)).'.'
                );
            }

            return $this->app->make(self::MAILING_LIST_SYNCERS[$driver]);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
