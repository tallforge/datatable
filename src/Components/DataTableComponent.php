<?php

namespace TallForge\DataTable\Components;

use Illuminate\Support\Facades\Schema;
use Livewire\{
    Component,
    WithPagination
};
use TallForge\DataTable\Traits\{
    WithBulkActions,
    WithColumnFormatter,
    WithRelations
};


class DataTableComponent extends Component
{
    use WithPagination, WithBulkActions, WithColumnFormatter, WithRelations;

    public ?string $theme = null;
    public ?string $model = null;

    public array $columns = [];
    public array $selectedColumns = [];
    public array $columnLabels = [];
    public array $booleanColumns = [];
    public array $booleanColumnsState = [];
    public array $alignColumns = [];
    public array $statusColumns = [];
    public array $relationColumns = [];

    public ?string $search = null;
    public bool $showSearch = false;
    public ?string $searchPlaceholder = null;

    public array $filters = [];
    public array $selectedFilters = [];
    public array $filterLabels = [];
    public array $booleanFilters = [];

    public ?string $paginationMode = null;
    public int $limit = 0;
    public int $perPage = 0;
    public array $perPageOptions = [];

    public $sortField;
    public string $sortDirection = 'asc';

    public bool $showReset = false;
    public ?string $resetLabel = null;

    public bool $showCreate = false;
    public ?string $createLabel = null;

    public array $rowActions = [];
    public string $rowActionType = 'buttons'; // options: 'buttons' | 'dropdown'

    public $confirmingAction = false;
    public $actionToConfirm = null;
    public $confirmingRowId = null;
    public $confirmingColumn = null;
    public $confirmMessage = null;

    public function mount(
        $theme = null,
        $model = null,

        $columns = [],
        $selectedColumns = [],
        $columnLabels = [],
        $booleanColumns = [],
        $alignColumns = [],
        $statusColumns = [],
        $relationColumns = [],

        $showSearch = null,
        $searchPlaceholder = null,
        $filters = [],
        $filterLabels = [],
        $booleanFilters = [],

        $paginationMode = null,
        $perPageOptions = null,

        $sortField = null,
        $sortDirection = 'asc',

        $showReset = null,
        $resetLabel = null,

        $showCreate = null,
        $createLabel = null,

        $rowActions = [],
        $rowActionType = null
    ) {

        $this->theme = $theme ?? config('tallforge.datatable.theme');
        $this->model = $model;

        $this->columns = $columns ?: $this->resolveColumns();
        $this->selectedColumns = $selectedColumns ?: $this->columns;
        $this->relationColumns = $relationColumns ?? [];

        $this->columnLabels = $columnLabels;
        $this->booleanColumns = $booleanColumns ?? [];
        $this->alignColumns = $alignColumns ?? [];
        $this->statusColumns = $statusColumns ?? [];

        $this->showSearch = $showSearch ?? config('tallforge.datatable.search.show');
        $this->searchPlaceholder = $searchPlaceholder ?? config('tallforge.datatable.search.placeholder');

        $this->filters = $filters;
        $this->filterLabels = $filterLabels;
        $this->booleanFilters = $booleanFilters;

        $this->paginationMode = $paginationMode ?? config('tallforge.datatable.pagination_mode');
        $this->perPageOptions = $perPageOptions ?? config('tallforge.datatable.paginations.' . $this->paginationMode . '.per_page_options');
        $this->perPage = config('tallforge.datatable.paginations.' . $this->paginationMode . '.per_page');
        $this->limit = $this->perPage;  // For load more mode

        $this->sortField = $sortField;
        $this->sortDirection = $sortDirection;

        $this->showReset = $showReset ?? config('tallforge.datatable.reset.show');
        $this->resetLabel = $resetLabel ?? config('tallforge.datatable.reset.label');

        $this->showCreate = $showCreate ?? config('tallforge.datatable.create.show');
        $this->createLabel = $createLabel ?? config('tallforge.datatable.create.label');

        $this->rowActions = $rowActions ?? [];
        $this->rowActionType = $rowActionType ?? 'buttons';

        // Merge relation columns into columns (populates relationColumnsFlat too)
        $this->mergeRelationColumnsIntoColumns();

        // ensure selectedColumns contains something valid
        if (empty($this->selectedColumns)) {
            $this->selectedColumns = $this->columns;
        }

        $this->loadDynamicFilters();
    }

    /**
     * Automatically resolve columns from the model schema
     * Respects model's $hidden and $guarded properties
     * Can be customized by overriding getCustomHiddenColumns()
     *
     * @return array
     */
    protected function resolveColumns(): array
    {
        // If columns are manually defined, skip auto-detection
        if (! empty($this->columns)) {
            return $this->columns;
        }

        // If no model is defined, nothing to resolve
        if (empty($this->model) || ! class_exists($this->model)) {
            return [];
        }

        /** @var \Illuminate\Database\Eloquent\Model $modelInstance */
        $modelInstance = new $this->model;

        // Get table name from model
        $table = $modelInstance->getTable();

        // Fetch columns from schema
        try {
            $columns = Schema::getColumnListing($table);
        } catch (\Throwable $e) {
            $columns = [];
        }

        // Get hidden columns from multiple sources
        $hidden = $this->getHiddenColumns($modelInstance);

        // Construct valid columns array
        $resolvedColumns = collect($columns)
            ->reject(fn($col) => in_array($col, $hidden))
            ->map(function ($col) {
                $colString = is_string($col) ? $col : (is_callable($col) ? '' : strval($col));
                return $colString;
            })
            ->values()
            ->toArray();

        return $resolvedColumns;
    }

    /**
     * Get hidden columns from various sources
     *
     * @return array
     */
    protected function getHiddenColumns($modelInstance): array
    {
        // Default hidden columns
        $defaultHidden = [
            'created_at',
            'updated_at',
            'deleted_at',
            'created_by',
            'updated_by',
            'deleted_by',
            'password',
            'remember_token',
            'email_verified_at'
        ];

        // Get hidden from model's $hidden property
        $modelHidden = property_exists($modelInstance, 'hidden') ? $modelInstance->getHidden() : [];

        // Get hidden from model's $guarded property (often contains sensitive fields)
        $modelGuarded = property_exists($modelInstance, 'guarded') && $modelInstance->getGuarded() !== ['*']
            ? $modelInstance->getGuarded()
            : [];

        // Allow component-level override
        $componentHidden = $this->getCustomHiddenColumns();

        // Merge all hidden columns
        return array_unique(array_merge($defaultHidden, $modelHidden, $modelGuarded, $componentHidden));
    }

    /**
     * Override this method in child components to customize hidden columns
     *
     * @return array
     */
    protected function getCustomHiddenColumns(): array
    {
        return [];
    }

    /**
     * Load dynamic filters for the column of data table.
     *
     * Supports:
     * - 'self'                     → Distinct values from base model
     * - ['model'=>..., 'key'=>...] → External model source
     * - 'relation' or ['type'=>'relation'] → Distinct values from related model
     *
     * @return void
     */
    public function loadDynamicFilters()
    {
        foreach ($this->filters as $col => $config) {

            // ------------------------------------------
            // CASE 1: Dynamic from base/self model
            // ------------------------------------------
            if ($config === 'self') {
                $this->filters[$col] = $this->model::query()
                    ->select($col)
                    ->distinct()
                    ->orderBy($col)
                    ->pluck($col)
                    ->filter() // remove null/empty
                    ->toArray();

                continue;
            }

            // ------------------------------------------
            // CASE 2: Dynamic from external model
            // ------------------------------------------
            if (is_array($config) && isset($config['model'], $config['key'], $config['label'])) {
                $query = $config['model']::query();

                // Optional where conditions
                if (isset($config['where']) && is_array($config['where'])) {
                    foreach ($config['where'] as $condition) {
                        if (count($condition) === 3) {
                            [$column, $operator, $value] = $condition;
                            $query->where($column, $operator, $value);
                        } elseif (count($condition) === 2) {
                            [$column, $value] = $condition;
                            $query->where($column, $value);
                        }
                    }
                }

                $this->filters[$col] = $query
                    ->orderBy($config['label'])
                    ->pluck($config['label'], $config['key'])
                    ->toArray();

                continue;
            }

            // ------------------------------------------
            // CASE 3: Dynamic from relation (NEW)
            // ------------------------------------------
            if ($config === 'relation' || (is_array($config) && ($config['type'] ?? null) === 'relation')) {

                // Detect relation + column
                if (! str_contains($col, '.')) {
                    continue; // invalid format
                }

                [$relation, $relCol] = explode('.', $col, 2);

                $modelClass = $this->model;
                $instance = new $modelClass;

                // If developer provided an override model, use it
                $relatedModel = $config['model'] ?? optional($instance->{$relation}())->getModel()::class ?? null;

                if (! $relatedModel || ! class_exists($relatedModel)) {
                    continue;
                }

                $relatedQuery = $relatedModel::query();

                // Optional where conditions
                if (isset($config['where']) && is_array($config['where'])) {
                    foreach ($config['where'] as $condition) {
                        if (count($condition) === 3) {
                            [$column, $operator, $value] = $condition;
                            $relatedQuery->where($column, $operator, $value);
                        } elseif (count($condition) === 2) {
                            [$column, $value] = $condition;
                            $relatedQuery->where($column, $value);
                        }
                    }
                }

                // Determine key/label fields
                $keyField = $config['key'] ?? $relCol;
                $labelField = $config['label'] ?? $relCol;

                // Generate options: pluck distinct values from relation model
                $options = $relatedQuery
                    ->select($labelField)
                    ->distinct()
                    ->orderBy($labelField)
                    ->pluck($labelField, $keyField)
                    ->filter()
                    ->toArray();

                $this->filters[$col] = $options;
            }
        }
    }

    public function getAlignColumn($col)
    {
        return $this->alignColumns[$col] ?? 'left';
    }

    public function updatingPerPage($value)
    {
        $this->perPage = (int) $value;
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSelectedFilters()
    {
        $this->resetPage();
    }

    public function getRowActions(): array
    {
        return $this->rowActions;
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function getColumnLabel($col)
    {
        // relation column label takes precedence
        $relLabel = $this->getRelationColumnLabel($col);
        if ($relLabel) return $relLabel;
        
        // explicit column labels or fallback
        return $this->columnLabels[$col] ?? ucfirst(str_replace('_', ' ', $col));
    }

    public function loadMore()
    {
        $this->limit += $this->perPage;
    }

    public function resetTable()
    {
        $this->reset([
            'search',
            'selectedFilters',
            'sortField',
            'sortDirection',
            'perPage',
            'limit',
        ]);

        $this->resetConfirmProperties();
        $this->resetSelection();

        $this->perPage = config('tallforge.datatable.paginations.' . $this->paginationMode . '.per_page');
        $this->limit = $this->perPage;
        $this->sortDirection = 'asc';
        $this->resetPage();
    }

    public function confirmToggle($id, $column)
    {
        $this->actionToConfirm = 'toggleBoolean';
        $this->confirmingRowId = $id;
        $this->confirmingColumn = $column;

        $columnLabel = $this->filterLabels[$column] ?? $this->columnLabels[$column] ?? ucfirst(str_replace('_', ' ', $column));
        $this->confirmMessage = "Are you sure you want to change {$columnLabel} for this record?";
        $this->confirmingAction = true;
    }

    public function toggleBoolean($id)
    {
        $config = $this->booleanColumns[$this->confirmingColumn] ?? null;
        if (!$config)
            return;

        $trueValue = $config['true'] ?? 1;
        $falseValue = $config['false'] ?? 0;

        $model = $this->model::findOrFail($id);

        // Toggle & save
        $model->{$this->confirmingColumn} = ($model->{$this->confirmingColumn} == $trueValue) ? $falseValue : $trueValue;
        $model->save();

        $this->booleanColumnsState[$id][$this->confirmingColumn] = $model->{$this->confirmingColumn} == $trueValue;

        // force re-render
        $this->resetPage();

        $columnLabel = $this->filterLabels[$this->confirmingColumn] ?? $this->columnLabels[$this->confirmingColumn] ?? ucfirst(str_replace('_', ' ', $this->confirmingColumn));
        $this->dispatch('notify', type: 'success', message: "Updated {$columnLabel} successfully.");
    }

    public function confirmAction($action, $rowId)
    {
        $this->actionToConfirm = $action;
        $this->confirmingRowId = $rowId;

        $config = collect($this->rowActions)->firstWhere('method', $action);

        $this->confirmMessage = $config['confirm'] ?? 'Are you sure you want to perform this action?';
        $this->confirmingAction = true;
    }

    public function performConfirmedAction()
    {
        if (!$this->actionToConfirm || !$this->confirmingRowId) return;

        $method = $this->actionToConfirm;
        $id = $this->confirmingRowId;

        if (method_exists($this, $method)) {
            $this->$method($id);
        }

        $this->resetConfirmProperties();
    }

    // Perform action without any confirmation
    public function performAction($method, $id)
    {
        $this->confirmingAction = false;
        $this->actionToConfirm = $method;
        $this->confirmingRowId = $id;

        if (method_exists($this, $method)) {
            $this->$method($id);
        }
    }

    public function cancelAction()
    {
        $this->resetConfirmProperties();
    }

    public function resetConfirmProperties()
    {
        $this->reset([
            'confirmingAction',
            'actionToConfirm',
            'confirmingRowId',
            'confirmingColumn',
            'confirmMessage'
        ]);
    }

    public function edit($id) {}
    public function delete($id) {}

    protected function isDatabaseColumn(string $column): bool
    {
        try {
            $table = (new $this->model)->getTable();

            // Get column name (remove alias if relation.field)
            if (str_contains($column, '.')) {
                return false; // relation field, handled separately
            }

            static $tableColumnsCache = [];

            if (!isset($tableColumnsCache[$table])) {
                $tableColumnsCache[$table] = Schema::getColumnListing($table);
            }

            return in_array($column, $tableColumnsCache[$table]);
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function applySearch($query)
    {
        $search = trim($this->search ?? '');

        if ($search === '') {
            return $query;
        }

        $query->where(function ($q) use ($search) {
            foreach ($this->selectedColumns as $col) {

                // Handle relation column
                if ($this->isRelationColumn($col)) {
                    [$relation, $relCol] = explode('.', $col, 2);
                    $q->orWhereHas($relation, function ($r) use ($relCol, $search) {
                        $r->where($relCol, 'like', "%{$search}%");
                    });
                    continue;
                }

                // Skip non-existing columns safely
                if (! $this->isDatabaseColumn($col)) {
                    continue;
                }

                // Safe base table search
                $q->orWhere($col, 'like', "%{$search}%");
            }
        });

        return $query;
    }

    protected function applyFiltersOldLegacy($query)
    {
        foreach ($this->selectedFilters as $key => $value) {
            if ($value) {
                $query->where($key, $value);
            }
        }

        return $query;
    }

    public function applyFilters($query)
    {
        foreach ($this->selectedFilters as $column => $value) {
            if (blank($value)) {
                continue;
            }

            // Handle relation filters (e.g., addresses.city)
            if (str_contains($column, '.')) {
                [$relation, $relColumn] = explode('.', $column, 2);
                $query->whereHas($relation, function ($q) use ($relColumn, $value) {
                    $q->where($relColumn, $value);
                });
                continue;
            }

            // Handle boolean filters
            // if (isset($this->booleanFilters[$column])) {
            //     $trueValues = ['1', 1, true, 'true', 'yes', 'Y'];
            //     $query->where($column, in_array($value, $trueValues) ? 'Y' : 'N');
            //     continue;
            // }

            // Handle normal filters
            $query->where($column, $value);
        }

        return $query;
    }

    protected function applySorting($query)
    {
        if ($this->sortField) {
            $query->orderBy($this->sortField, $this->sortDirection);
        }

        return $query;
    }

    protected function applyPagination($query)
    {
        if ($this->paginationMode === 'load-more') {
            return $query->take($this->limit)->get();
        } else {
            return $query->paginate($this->perPage);
        }
    }

    protected function applyBooleanColumnsState($rows)
    {
        if (!empty($this->booleanColumns)) {
            foreach ($rows as $row) {
                foreach ($this->booleanColumns as $column => $config) {
                    $trueValue = $config['true'] ?? 1;
                    $this->booleanColumnsState[$row->id][$column] = $row->$column == $trueValue;
                }
            }
        }

        return $rows;
    }
    
    public function loadData()
    {
        $query = $this->model::query();

        // Eager load all declared relations
        $query = $this->eagerLoadRelations($query);

        // Apply search (new)
        $query = $this->applySearch($query);

        // Apply filters
        $query = $this->applyFilters($query);

        // Apply sorting
        $query = $this->applySorting($query);

        // Apply pagination or load more
        $rows = $this->applyPagination($query);

        // Initialize boolean columns state
        $rows = $this->applyBooleanColumnsState($rows);

        return $rows;
    }

    public function render()
    {
        // Dynamically load theme view
        return view("tallforge.datatable::themes.{$this->theme}.table", [
            'table' => config('tallforge.datatable.themes.' . $this->theme),
            'rows' => $this->loadData(),
            'relationColumnsFlat' => $this->relationColumnsFlat,
        ]);
    }
}
