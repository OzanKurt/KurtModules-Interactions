<?php

declare(strict_types=1);

use Filament\Forms\Form;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Kurt\Modules\Interactions\Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature');

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
