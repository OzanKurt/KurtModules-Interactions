<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Tests\Fixtures;

use Filament\Panel;
use Filament\PanelProvider;
use Kurt\Modules\Interactions\Filament\InteractionsPlugin;

/**
 * Minimal Filament panel for the resource smoke tests; registers the
 * version-dispatching Interactions plugin for whichever Filament major is
 * installed in the current CI cell.
 */
final class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->default()
            ->plugin(InteractionsPlugin::make());
    }
}
