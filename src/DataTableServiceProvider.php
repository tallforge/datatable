<?php

namespace TallForge\DataTable;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use TallForge\DataTable\Components\DataTableComponent;

class DataTableServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/datatable.php',
            'tallforge.datatable'
        );
    }

    public function boot(): void
    {
        // Load package views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'tallforge.datatable');

        // Register Livewire component (with safety check)
        if (class_exists(Livewire::class)) {
            Livewire::component('tallforge.datatable', DataTableComponent::class);
        }

        // Publish config for customization
        $this->publishes([
            __DIR__ . '/../config/datatable.php' => config_path('tallforge/datatable.php'),
        ], 'tallforge-datatable-config');

        // Publish views for customization
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/tallforge/datatable'),
        ], 'tallforge-datatable-views');

        // Publish all assets at once for customization (optional but convenient)
        $this->publishes([
            __DIR__ . '/../config/datatable.php' => config_path('tallforge/datatable.php'),
            __DIR__ . '/../resources/views' => resource_path('views/vendor/tallforge/datatable'),
        ], 'tallforge-datatable');
    }
}
