<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

// The home page reads the research library, so it needs a schema.
uses(RefreshDatabase::class);

it('returns a successful response', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
