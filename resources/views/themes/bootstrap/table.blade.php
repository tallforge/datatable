<div>

    <!-- ToolBar -->
    <div class="d-flex justify-content-between align-items-center mb-3">

        <!-- Search -->
        @if($showSearch)
            <input type="text" wire:model.live.debounce.500ms="search" class="form-control w-25"
                placeholder="{{ $searchPlaceholder }}">
        @endif

        <!-- Column Selector -->
        <div class="dropdown mb-3">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                Columns
            </button>
            <ul class="dropdown-menu p-3">
                @foreach($columns as $col)
                    <li>
                        <label class="form-check-label">
                            <input type="checkbox" class="form-check-input" wire:model.live="selectedColumns"
                                value="{{ $col }}" @checked(in_array($col, $selectedColumns))>
                            {{ $this->getColumnLabel($col) }}
                        </label>
                    </li>
                @endforeach
            </ul>
        </div>

        <!-- Per Page Options -->
        @if($paginationMode === 'pagination')
            <div>
                <select wire:model.live="perPage" class="form-select">
                    @foreach($perPageOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <!-- Reset Button -->
        @if($showReset)
            <button wire:click="resetTable" type="button" class="btn btn-outline-secondary">
                {{ $resetLabel }}
            </button>
        @endif
    </div>

    <!-- Filters ToolBar -->
    <div class="mb-3 d-flex gap-2">
        @foreach($filters as $key => $options)
            <select wire:model.live="selectedFilters.{{ $key }}" class="form-select">
                <option value="">Select {{ $filterLabels[$key] ?? $columnLabels[$key] ?? ucfirst(str_replace('_', ' ', $key)) }}</option>
                @foreach($options as $option)
                    <option value="{{ $option }}">{{ ucfirst($option) }}</option>
                @endforeach
            </select>
        @endforeach
    </div>

    <!-- Bulk Actions Toolbar -->
    @if($this->hasSelection())
        <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light border rounded">
            <div class="d-flex align-items-center">
                <input type="checkbox" wire:model.live="selectAll" class="form-check-input me-2">
                <span class="text-muted small">
                    {{ count($selectedRows) }} selected
                </span>
            </div>

            <div>
                <select wire:change="confirmBulkAction($event.target.value)" class="form-select form-select-sm">
                    <option value="">Bulk Actions</option>
                    @foreach($this->bulkActions() as $key => $action)
                        <option value="{{ $key }}">
                            {{ is_array($action) ? $action['label'] : ucfirst($key) }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    @endif


    <!-- Table -->
    <table class="{{ $table['class'] }}">
        <thead>
            <tr>
                <th style="width:40px;">
                    <input type="checkbox" wire:model.live="selectAll" class="form-check-input">
                </th>
                @foreach($columns as $col)
                    @if(in_array($col, $selectedColumns))
                        <th wire:click="sortBy('{{ $col }}')" style="cursor:pointer;" wire:key='thCol-{{ $loop->index }}'>
                            {{ $this->getColumnLabel($col) }}
                            @if($sortField === $col)
                                {!! $sortDirection === 'asc' ? '▲' : '▼' !!}
                            @endif
                        </th>
                    @endif
                @endforeach
                @if(count($rowActions))
                    <th class="text-center">Actions</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr wire:key='row-{{ $loop->index }}'>
                    <td>
                        <input type="checkbox"
                            wire:model.live="selectedRows"
                            aria-label="Select row"
                            value="{{ $row->id }}"
                            class="form-check-input">
                    </td>
                    @foreach($columns as $col)
                        @if(in_array($col, $selectedColumns))
                            <td class="text-{{ $this->getAlignColumn($col) }}"
                                wire:key='rowCol-{{ $loop->parent->index }}-{{ $loop->index }}'>

                                @if(isset($statusColumns[$col]))
                                    @php
                                        $statusValue = $row->$col;
                                        $badgeClass = $statusColumns[$col][$statusValue] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $badgeClass }}">
                                        {{ $statusValue }}
                                    </span>

                                @elseif(isset($booleanColumns[$col]))
                                    @php
                                        $config = $booleanColumns[$col];
                                        $trueValue = $config['true'] ?? 1;
                                        $falseValue = $config['false'] ?? 0;
                                        $isTrue = $row->$col == $trueValue;
                                    @endphp
                                    <div class="d-flex justify-content-{{ $this->getAlignColumn($col) }}">
                                        <div class="form-check form-switch">
                                            <input type="checkbox" wire:key='cbToggle-{{ $loop->parent->index }}-{{ $loop->index }}'
                                                wire:click.prevent="confirmToggle({{ $row->id }}, '{{ $col }}')"
                                                wire:loading.attr="disabled"
                                                wire:model.live='booleanColumnsState.{{ $row->id }}.{{ $col }}' class="form-check-input"
                                                data-bs-toggle="modal" data-bs-target="#confirmRowActionModal"
                                                role="switch" @checked($isTrue)>

                                            <label class="form-check-label" style="cursor: pointer;"
                                                wire:click.prevent="confirmToggle({{ $row->id }}, '{{ $col }}')"
                                                wire:key="confirmToggleKey-{{ $loop->index }}"
                                                data-bs-toggle="modal" data-bs-target="#confirmRowActionModal">
                                                {{ $isTrue
                                                    ? ($config['label_true'] ?? 'Yes')
                                                    : ($config['label_false'] ?? 'No') }}
                                            </label>
                                        </div>
                                    </div>

                                @else
                                    {!! $this->renderColumn($col, $row) ?? $this->defaultColumnRender($col, $row) !!}
                                @endif
                            </td>
                        @endif
                    @endforeach

                    @if(count($rowActions))
                        <td class="text-center">
                            @if($rowActionType === 'buttons')
                                @foreach($rowActions as $config)
                                    <button type="button"
                                            class="btn btn-sm btn-{{ $config['color'] ?? 'primary' }} me-1"
                                            @if(isset($config['confirm']))
                                                wire:click="confirmAction('{{ $config['method'] }}', {{ $row->id }})"
                                                data-bs-toggle="modal" data-bs-target="#confirmRowActionModal"
                                            @else
                                                wire:click="performAction('{{ $config['method'] }}', {{ $row->id }})"
                                            @endif
                                            wire:key="actionKey-{{ $loop->index }}">
                                        {{ $config['label'] }}
                                    </button>
                                @endforeach
                            @else
                                {{-- ($rowActionType === 'dropdown') --}}
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button"
                                        data-bs-toggle="dropdown">
                                        Actions
                                    </button>
                                    <ul class="dropdown-menu">
                                        @foreach($rowActions as $config)
                                            <li>
                                                <a href="#"
                                                    class="dropdown-item"
                                                    @if(isset($config['confirm']))
                                                        wire:click="confirmAction('{{ $config['method'] }}', {{ $row->id }})"
                                                        data-bs-toggle="modal" data-bs-target="#confirmRowActionModal"
                                                    @else
                                                        wire:click="performAction('{{ $config['method'] }}', {{ $row->id }})"
                                                    @endif
                                                    wire:key="actionKey-{{ $loop->index }}">
                                                    {{ $config['label'] }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($selectedColumns) + 2 }}" class="text-center">
                        No results found
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Pagination or Load More -->
    <div class="mt-3 text-center">
        @if($paginationMode === 'load-more')
            @if($rows->count() >= $limit)
                <button wire:click="loadMore" class="btn btn-secondary">
                    Load More
                </button>
            @endif
        @else
            {{ $rows->links() }}
        @endif
    </div>

    <!-- Include Modal Dialog -->
    @includeIf("tallforge.datatable::themes.{$theme}.confirm-modal")
    @includeIf("tallforge.datatable::themes.{$theme}.bulk-confirm-modal")

</div>
