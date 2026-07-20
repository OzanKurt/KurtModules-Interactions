<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
 * Bound to the base TestCase, which leaves interactions.http.mode at its
 * 'headless' default — so the module registers no HTTP surface at all.
 */

it('registers no API routes in the default headless mode', function () {
    expect(Route::has('interactions.api.reactions.store'))->toBeFalse();
    expect(Route::has('interactions.api.counts'))->toBeFalse();
    expect(Route::has('interactions.api.engagement.toggle'))->toBeFalse();
});

it('does not resolve an API URL in headless mode', function () {
    $this->postJson('/api/interactions/post/1/reactions', ['emoji' => '🎉'])
        ->assertNotFound();
});
