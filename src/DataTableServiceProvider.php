<?php

namespace TallForge\DataTable;

use Illuminate\Support\ServiceProvider;
use IamIlyasKazi\LivewireDataTable\Components\DataTableComponent;
use Livewire\Livewire;

class DataTableServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Load package views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'datatable');

        // Register Livewire component
        Livewire::component('data-table-component', DataTableComponent::class);

        // Publish config
        $this->publishes([
            __DIR__ . '/../config/datatable.php' => config_path('datatable.php'),
        ], 'datatable-config');

        // Publish views for customization
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/datatable'),
        ], 'datatable-views');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/datatable.php',
            'datatable'
        );
    }
}
