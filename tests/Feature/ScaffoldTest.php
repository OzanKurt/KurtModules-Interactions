<?php

declare(strict_types=1);

it('boots the provider and merges config defaults', function () {
    // Config presence proves the service provider registered + merged the file.
    expect(config('interactions.reactions.allow_unicode'))->toBeTrue();
    expect(config('interactions.comments.default_status'))->toBe('published');
    expect(config('interactions.counters.driver'))->toBe('table');
});
