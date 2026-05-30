<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Providers;

use Kurt\Modules\Core\Providers\PackageServiceProvider;
use Kurt\Modules\Interactions\Engagement\CounterSync;
use Kurt\Modules\Interactions\Engagement\InteractionManager;
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

    public function packageRegistered(): void
    {
        $this->app->singleton(CounterSync::class);
        $this->app->singleton(InteractionManager::class);
    }
}
