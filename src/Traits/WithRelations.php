<?php

namespace TallForge\DataTable\Traits;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

trait WithRelations
{
    /**
     * Relation configuration provided by User:
     * [
     *   'addresses' => [
     *       'columns' => [
     *           'address' => ['label' => 'Address'],
     *           'city' => ['label' => 'City'],
     *           'pincode' => ['label' => 'Pincode'],
     *           'state' => ['label' => 'State'],
     *           'country' => ['label' => 'Country'],
     *       ],
     *       'separator' => ', ',
     *   ],
     *   'roles' => [
     *       'columns' => [
     *           'name' => ['label' => 'Roles']
     *       ],
     *       'separator' => ' | ',
     *   ],
     * ]
     */
    public array $relationColumns = [];

    /**
     * Flattened map: keys like "addresses.city" => meta...
     * Populated via getRelationColumnsFlat()
     */
    protected array $relationColumnsFlat = [];

    /**
     * Merge relation-columns into columns to avoid duplication.
     * Call this in mount() after columns and relationColumns are set.
     */
    protected function mergeRelationColumnsIntoColumns(): void
    {
        $this->relationColumnsFlat = $this->getRelationColumnsFlat();

        // Ensure both arrays exist
        $this->columns = $this->columns ?? [];
        $this->selectedColumns = $this->selectedColumns ?? [];

        foreach (array_keys($this->relationColumnsFlat) as $colKey) {
            // Ensure column exists in all columns
            if (! in_array($colKey, $this->columns, true)) {
                $this->columns[] = $colKey;
            }

            if (empty($this->selectedColumns) || ! in_array($colKey, $this->selectedColumns, true)) {
                $this->selectedColumns[] = $colKey;
            }
        }
    }

    /**
     * Return flattened relation columns:
     * [
     *   'addresses.city' => [
     *       'label' => 'City',
     *       'relation' => 'addresses',
     *       'column' => 'city',
     *       'separator' => ' / ',
     *   ],
     *   ...
     * ]
     */
    protected function getRelationColumnsFlat(): array
    {
        $flat = [];

        foreach ($this->relationColumns as $relation => $config) {
            $cols = $config['columns'] ?? [];
            $separator = $config['separator'] ?? ', ';

            foreach ($cols as $colName => $meta) {
                $label = $meta['label'] ?? (string) \Illuminate\Support\Str::title(str_replace('_', ' ', $colName));
                $flatKey = "{$relation}.{$colName}";

                $flat[$flatKey] = [
                    'label' => $label,
                    'relation' => $relation,
                    'column' => $colName,
                    'separator' => $separator,
                ];
            }
        }

        return $flat;
    }

    /**
     * Eager load relations declared in relationColumns.
     * Use this before executing the query to avoid N+1.
     *
     * Example usage: $query = $this->eagerLoadRelations($query);
     */
    protected function eagerLoadRelations($query)
    {
        // Automatically eager load all relations
        $relations = array_keys($this->relationColumns);

        if (! empty($relations)) {
            // Allow nested relation configs (if user passes nested arrays)
            return $query->with($relations);
        }

        return $query;
    }

    /**
     * Format relation value for a given $row, relation and column.
     * For collection relations (hasMany / belongsToMany) it will join values.
     * For single relations (belongsTo / hasOne) it will return the attribute.
     */
    public function formatRelationValue($row, string $relation, string $column)
    {
        if (! isset($row->{$relation})) {
            return null;
        }

        $related = $row->{$relation};

        // If collection, pluck and join
        if ($related instanceof Collection) {
            return $related->pluck($column)->filter()->join(
                $this->relationColumns[$relation]['separator'] ?? ', '
            );
        }

        // Single model or null
        return optional($related)->{$column};
    }

    /**
     * Helper to check if a column key (like 'addresses.city') is a relation column.
     */
    protected function isRelationColumn(string $columnKey): bool
    {
        if (empty($this->relationColumnsFlat)) {
            $this->relationColumnsFlat = $this->getRelationColumnsFlat();
        }

        return isset($this->relationColumnsFlat[$columnKey]);
    }

    /**
     * Get pretty label for columns (will be used by DataTable component).
     * If the columnKey is a relation column, use relation label.
     */
    protected function getRelationColumnLabel(string $columnKey): ?string
    {
        if ($this->isRelationColumn($columnKey)) {
            return $this->relationColumnsFlat[$columnKey]['label'] ?? null;
        }

        return null;
    }
}
