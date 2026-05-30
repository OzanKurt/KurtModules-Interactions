<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Filament;

use Filament\Contracts\Plugin;
use Kurt\Modules\Core\Support\FilamentVersion;
use RuntimeException;

/**
 * Version-dispatching facade for the Interactions Filament plugin. Register with
 * `->plugin(\Kurt\Modules\Interactions\Filament\InteractionsPlugin::make())`; the
 * correct V{n} plugin is resolved from the installed Filament major.
 */
final class InteractionsPlugin
{
    public static function make(): Plugin
    {
        return match (FilamentVersion::major()) {
            5 => new V5\InteractionsPlugin,
            4 => new V4\InteractionsPlugin,
            3 => new V3\InteractionsPlugin,
            default => throw new RuntimeException('Filament is not installed; cannot register the Interactions plugin.'),
        };
    }
}
