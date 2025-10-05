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
                <option value="">All {{ ucfirst($key) }}</option>
                @foreach($options as $option)
                    <option value="{{ $option }}">{{ ucfirst($option) }}</option>
                @endforeach
            </select>
        @endforeach
    </div>

    <!-- Table -->
    <table class="{{ $table['class'] }}">
        <thead>
            <tr>
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
                                                role="switch" @checked($isTrue)>
                                            <label class="form-check-label" style="cursor: pointer;"
                                                wire:click.prevent="confirmToggle({{ $row->id }}, '{{ $col }}')">
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
                                    <button type="button" class="btn btn-sm btn-{{ $config['color'] ?? 'primary' }} me-1"
                                        wire:click="{{ $config['method'] }}({{ $row->id }})">
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
                                                <a href="#" class="dropdown-item"
                                                    wire:click.prevent="{{ $config['method'] }}({{ $row->id }})">
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
                    <td colspan="{{ count($selectedColumns) }}" class="text-center">
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
</div>

<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('confirm-toggle', ({ id, column }) => {
            if (confirm(`Are you sure you want to change ${column} for record ID ${id}?`)) {
                Livewire.dispatch('toggle-boolean', { id, column });
            }
        });
    });
</script>