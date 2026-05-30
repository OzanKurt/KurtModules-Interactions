<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Tests;

use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Foundation\Application;
use Kurt\Modules\Core\Providers\CoreServiceProvider;
use Kurt\Modules\Core\Support\FilamentVersion;
use Kurt\Modules\Core\Testing\PackageTestCase;
use Kurt\Modules\Interactions\Providers\InteractionsServiceProvider;
use Kurt\Modules\Interactions\Tests\Fixtures\AdminPanelProvider;
use Kurt\Modules\Interactions\Tests\Stubs\User;
use Livewire\LivewireServiceProvider;

abstract class TestCase extends PackageTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Restore Livewire's container singletons under Testbench so Filament
        // component introspection does not throw on a null error bag. No-op
        // when Filament (and Livewire) is not installed.
        if (FilamentVersion::major() !== null && class_exists(LivewireServiceProvider::class)) {
            (new LivewireServiceProvider($this->app))->register();
        }
    }

    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return array_merge([
            CoreServiceProvider::class,
            InteractionsServiceProvider::class,
        ], $this->filamentProviders());
    }

    /**
     * @return array<int, class-string>
     */
    protected function filamentProviders(): array
    {
        if (FilamentVersion::major() === null) {
            return [];
        }

        $candidates = [
            LivewireServiceProvider::class,
            SupportServiceProvider::class,
            ActionsServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            FilamentServiceProvider::class,
            AdminPanelProvider::class,
        ];

        return array_values(array_filter(
            $candidates,
            static fn (string $provider): bool => class_exists($provider),
        ));
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('session.driver', 'array');
        $app['config']->set('kurtmodules.user_model', User::class);
        $app['config']->set('interactions.mentions.pool', [
            ['model' => User::class, 'column' => 'username'],
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();
        $this->loadMigrationsFrom(__DIR__.'/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
