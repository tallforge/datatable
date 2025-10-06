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
     * The currently pending action awaiting confirmation.
     */
    public ?string $confirmingAction = null;

    /**
     * Whether the confirmation modal is visible.
     */
    public bool $showConfirmModal = false;

    /**
     * Define available bulk actions for the DataTable.
     *
     * Example:
     * [
     *   'delete' => [
     *      'label' => 'Delete Selected',
     *      'confirm' => 'Are you sure you want to delete selected items?',
     *      'action' => fn($ids) => Model::destroy($ids),
     *   ],
     * ]
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
     * Called when user chooses a bulk action from dropdown.
     */
    public function confirmBulkAction(string $action): void
    {
        $this->confirmingAction = $action;

        $actions = $this->bulkActions();

        if (isset($actions[$action]['confirm'])) {
            $this->showConfirmModal = true; // open modal
        } else {
            // No confirmation needed, perform immediately
            $this->performBulkAction($action);
        }
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
     * Execute confirmed bulk action.
     */
    public function performBulkAction(string $action = null)
    {
        $action = $action ?? $this->confirmingAction;
        $actions = $this->bulkActions();

        if (! isset($actions[$action])) {
            $this->dispatch('notify', type: 'error', message: 'Invalid bulk action.');
            return;
        }

        $callback = $actions[$action]['action'] ?? null;

        if (is_callable($callback)) {
            $callback($this->selectedRows);
        } elseif (is_string($callback) && class_exists($callback)) {
            app($callback)->handle($this->selectedRows);
        }

        $this->afterBulkAction($action);
    }

    /**
     * Reset modal state.
     */
    public function resetBulkActionModal(): void
    {
        $this->showConfirmModal = false;
        $this->confirmingAction = null;
    }

    /**
     * Reset row selections.
     */
    public function resetSelection(): void
    {
        $this->selectedRows = [];
        $this->selectAll = false;
    }

    /**
     * Hook after executing bulk action.
     */
    protected function afterBulkAction(string $action): void
    {
        $this->resetSelection();
        $this->resetBulkActionModal();
        $this->dispatch('notify', type: 'success', message: ucfirst($action).' completed successfully.');
    }

    /**
     * Helper to check if rows are selected.
     */
    public function hasSelection(): bool
    {
        return count($this->selectedRows) > 0;
    }
}
