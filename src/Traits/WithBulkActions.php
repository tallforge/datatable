<?php

namespace TallForge\DataTable\Traits;

use Illuminate\Support\Collection;

trait WithBulkActions
{
    /**
     * Selected row IDs.
     */
    public array $selectedRows = [];

    /**
     * Whether "Select All" is active.
     */
    public bool $selectAll = false;

    /**
     * Define available bulk actions for the DataTable.
     *
     * @return array<string, callable|string|array>
     */
    public function bulkActions(): array
    {
        return [
            // Example:
            // 'delete' => [
            //     'label' => 'Delete Selected',
            //     'action' => fn($ids) => $this->model::destroy($ids),
            //     'confirm' => 'Are you sure you want to delete selected records?',
            // ],
        ];
    }

    /**
     * Toggle select all (for current page or globally later).
     */
    public function updatedSelectAll($value)
    {
        if (! $value) {
            $this->selectedRows = [];
            return;
        }

        $this->selectedRows = $this->getCurrentPageRows()
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->toArray();
    }

    /**
     * Returns current page rows collection.
     * Relies on DataTableComponent’s query() or data source.
     */
    protected function getCurrentPageRows(): Collection
    {
        if (property_exists($this, 'rows') && $this->rows instanceof Collection) {
            return $this->rows;
        }

        // fallback for components using pagination
        return collect($this->model::paginate($this->perPage ?? 10)->items());
    }

    /**
     * Perform a bulk action.
     */
    public function performBulkAction(string $action)
    {
        $actions = $this->bulkActions();

        if (! isset($actions[$action])) {
            $this->dispatch('notify', type: 'error', message: 'Invalid bulk action.');
            return;
        }

        $definition = $actions[$action];
        $callback = $definition['action'] ?? null;

        if (is_callable($callback)) {
            $callback($this->selectedRows);
        } elseif (is_string($callback) && class_exists($callback)) {
            app($callback)->handle($this->selectedRows);
        }

        $this->afterBulkAction($action);
    }

    /**
     * Hook after executing bulk action.
     */
    protected function afterBulkAction(string $action): void
    {
        $this->resetSelection();
        $this->dispatch('notify', type: 'success', message: ucfirst($action).' completed successfully.');
    }

    /**
     * Reset selection state.
     */
    public function resetSelection(): void
    {
        $this->selectedRows = [];
        $this->selectAll = false;
    }

    /**
     * Helper to check if rows are selected.
     */
    public function hasSelection(): bool
    {
        return count($this->selectedRows) > 0;
    }
}
