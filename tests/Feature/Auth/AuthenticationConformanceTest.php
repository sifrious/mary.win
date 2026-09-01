<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Sifrious\AccountsClient\Testing\AuthenticationConformance;
use Sifrious\AccountsClient\Testing\ConsumerUnderTest;
use Tests\Conformance\MaryWinConsumer;
use Tests\TestCase;

/**
 * mary.win's auth release gate — the same eighteen cases Logres and Burdgen
 * run, unchanged, on a different Laravel major.
 */
final class AuthenticationConformanceTest extends TestCase
{
    use AuthenticationConformance, RefreshDatabase;

    private ?MaryWinConsumer $consumer = null;

    protected function consumerUnderTest(): ConsumerUnderTest
    {
        return $this->consumer ??= new MaryWinConsumer($this);
    }

    protected function tearDown(): void
    {
        $this->consumer = null;
        parent::tearDown();
    }
}
