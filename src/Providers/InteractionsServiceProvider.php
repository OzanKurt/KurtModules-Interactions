<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Providers;

use Kurt\Modules\Core\Providers\PackageServiceProvider;
use Spatie\LaravelPackageTools\Package;

final class InteractionsServiceProvider extends PackageServiceProvider
{
    protected function module(): string
    {
        return 'interactions';
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-modules-interactions')
            ->hasConfigFile('interactions')
            ->discoversMigrations();
    }
}
