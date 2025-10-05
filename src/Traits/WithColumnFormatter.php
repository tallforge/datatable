<?php

namespace TallForge\DataTable\Traits;

trait WithColumnFormatter
{
    /**
     * Override this in your extended component to customize a column.
     * 
     * @param string $col Column name
     * @param object $row Current row object
     * @return string|null
     */
    public function renderColumn($col, $row)
    {
        return null; // fallback if not overridden
    }

    /**
     * Fallback default rendering.
     * 
     * @param string $col Column name
     * @param object $row Current row object
     * @return string|null
     */
    public function defaultColumnRender($col, $row)
    {
        return $row->$col;
    }
}
