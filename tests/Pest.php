<?php

declare(strict_types=1);

use Filament\Forms\Form;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Route;
use Kurt\Modules\Core\Http\ApiRouteGroup;
use Kurt\Modules\Core\Support\ApiRateLimiter;
use Kurt\Modules\Interactions\Tests\Stubs\Post;
use Kurt\Modules\Interactions\Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| REST API test helpers
|--------------------------------------------------------------------------
|
| The module boots headless by default, so the API tests opt in at runtime:
| flip http.mode to api, register the module's named rate limiter, and load the
| route file inside the Core API kit's route group — exactly what the provider's
| registerModuleApi() does when a host enables the API. A morph map is registered
| so the Post stub is addressable by the "post" alias (the host's contract).
| enableInteractionsApi() is called from each API test's beforeEach; the alias is
| cleared afterwards so it never leaks into the headless / domain suites.
|
*/

if (! function_exists('enableInteractionsApi')) {
    /**
     * @param  array<string, class-string>  $morphMap
     */
    function enableInteractionsApi(array $morphMap = ['post' => Post::class]): void
    {
        config()->set('interactions.http.mode', 'api');

        Relation::morphMap($morphMap);

        ApiRateLimiter::register('interactions');

        Route::group(ApiRouteGroup::attributes('interactions'), function (): void {
            require __DIR__.'/../routes/api.php';
        });

        // Routes added after boot leave the name lookup stale; refresh it so
        // Route::has() and named-route generation see the new endpoints.
        Route::getRoutes()->refreshNameLookups();
    }

    function resetInteractionsMorphMap(): void
    {
        Relation::morphMap([], false);
    }
}

/*
|--------------------------------------------------------------------------
| Filament resource introspection helpers
|--------------------------------------------------------------------------
|
| Assert the structure of a resource's form/table without rendering a
| Livewire page (which is unreliable under Testbench + Filament v5/Livewire v4).
|
*/

if (! function_exists('formFilamentContainer')) {
    /**
     * @param  class-string  $pageClass
     */
    function formFilamentContainer(string $pageClass): object
    {
        $container = class_exists(Schema::class) ? Schema::class : Form::class;

        return $container::make(app($pageClass));
    }

    /**
     * @param  class-string  $resource
     * @param  class-string  $pageClass
     * @return array<int, string>
     */
    function formFieldNames(string $resource, string $pageClass): array
    {
        return array_keys($resource::form(formFilamentContainer($pageClass))->getFlatFields(withHidden: true));
    }

    /**
     * @param  class-string  $resource
     * @param  class-string  $pageClass
     * @return array<int, string>
     */
    function tableColumnNames(string $resource, string $pageClass): array
    {
        return array_keys($resource::table(Table::make(app($pageClass)))->getColumns());
    }

    /**
     * @param  class-string  $resource
     * @param  class-string  $pageClass
     * @return array<int, string>
     */
    function tableFilterNames(string $resource, string $pageClass): array
    {
        return array_keys($resource::table(Table::make(app($pageClass)))->getFilters());
    }

    /**
     * @param  iterable<mixed>  $actions
     * @return array<int, string>
     */
    function flattenActionNames(iterable $actions): array
    {
        $names = [];

        foreach ($actions as $action) {
            if (is_object($action) && method_exists($action, 'getName')) {
                $names[] = $action->getName();
            }

            if (is_object($action) && method_exists($action, 'getActions')) {
                $names = array_merge($names, flattenActionNames($action->getActions()));
            }
        }

        return array_values(array_filter($names));
    }

    /**
     * @param  class-string  $resource
     * @param  class-string  $pageClass
     * @return array<int, string>
     */
    function tableActionNames(string $resource, string $pageClass): array
    {
        $table = $resource::table(Table::make(app($pageClass)));

        if (method_exists($table, 'getFlatActions')) {
            return flattenActionNames($table->getFlatActions());
        }

        return flattenActionNames($table->getRecordActions());
    }
}
