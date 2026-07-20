<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

beforeEach(fn () => enableInteractionsApi());
afterEach(fn () => resetInteractionsMorphMap());

it('registers the API routes when mode is api', function () {
    expect(Route::has('interactions.api.reactions.store'))->toBeTrue();
    expect(Route::has('interactions.api.reactions.summary'))->toBeTrue();
    expect(Route::has('interactions.api.counts'))->toBeTrue();
    expect(Route::has('interactions.api.engagement.show'))->toBeTrue();
    expect(Route::has('interactions.api.engagement.toggle'))->toBeTrue();
});
