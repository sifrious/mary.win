<?php

use App\Models\MailingListSubscription;
use App\Services\MailingList\MailingListSyncer;
use App\Services\MailingList\MailingListSyncException;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
 // ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Stand-in for a real email service provider, so the sync boundary can be
 * exercised in both its healthy and its failing state.
 */
function fakeSyncer(bool $failing = false): object
{
    $syncer = new class($failing) implements MailingListSyncer
    {
        public array $subscribed = [];

        public array $unsubscribed = [];

        public function __construct(private bool $failing) {}

        public function subscribe(MailingListSubscription $subscription): void
        {
            if ($this->failing) {
                throw new MailingListSyncException('provider unavailable');
            }

            $this->subscribed[] = $subscription->email;
        }

        public function unsubscribe(MailingListSubscription $subscription): void
        {
            if ($this->failing) {
                throw new MailingListSyncException('provider unavailable');
            }

            $this->unsubscribed[] = $subscription->email;
        }
    };

    app()->instance(MailingListSyncer::class, $syncer);

    return $syncer;
}
