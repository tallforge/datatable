<div class="p-4">

    <!-- ToolBar -->
    <div class="flex justify-between items-center mb-4">

        <!-- Search -->
        @if($showSearch)
            <input type="text" wire:model.debounce.500ms="search"
                class="w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-indigo-200"
                placeholder="{{ $searchPlaceholder }}">
        @endif

        <div class="flex gap-3 items-center">
            
            <!-- Column Selector -->
            <div class="relative mb-4">
                <button type="button" class="px-3 py-2 bg-gray-200 rounded-md text-gray-700 hover:bg-gray-300">
                    Columns
                </button>
                <div class="absolute mt-2 bg-white border rounded shadow p-3 z-10">
                    @foreach($columns as $col)
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" 
                                wire:model.live="selectedColumns" 
                                value="{{ $col }}"
                                class="rounded border-gray-300"
                                @checked(in_array($col, $selectedColumns))>
                            <span>{{ $this->getColumnLabel($col) }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Per Page Options -->
            @if($paginationMode === 'pagination')
                <div>
                    <select wire:model.live="perPage"
                        class="rounded-md border-gray-300 shadow-sm focus:ring focus:ring-indigo-200">
                        @foreach($perPageOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <!-- Reset Button -->
            @if($showReset)
                <button wire:click="resetTable" class="px-3 py-2 bg-gray-200 rounded-md text-gray-700 hover:bg-gray-300">
                    {{ $resetLabel }}
                </button>
            @endif
        </div>
    </div>

    <!-- Filters ToolBar -->
    <div class="flex gap-3 mb-4">
        @foreach($filters as $key => $options)
            <select wire:model="selectedFilters.{{ $key }}"
                class="rounded-md border-gray-300 shadow-sm focus:ring focus:ring-indigo-200">
                <option value="">Select {{ $filterLabels[$key] ?? $columnLabels[$key] ?? ucfirst(str_replace('_', ' ', $key)) }}</option>
                @foreach($options as $option)
                    <option value="{{ $option }}">{{ ucfirst($option) }}</option>
                @endforeach
            </select>
        @endforeach
    </div>

    <!-- Bulk Actions Toolbar -->
    @if($this->hasSelection())
        <div class="flex items-center justify-between mb-3 p-2 bg-gray-50 border border-gray-200 rounded-lg">
            <div class="flex items-center space-x-2">
                <input type="checkbox" wire:model.live="selectAll" class="rounded border-gray-300">
                <span class="text-sm text-gray-600">
                    {{ count($selectedRows) }} selected
                </span>
            </div>

            <div>
                <select wire:change="performBulkAction($event.target.value)"
                    class="rounded-md border-gray-300 shadow-sm text-sm focus:ring focus:ring-indigo-200">
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
    <div class="overflow-x-auto">
        <table class="{{ $table['class'] }}">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-3 py-2 w-6">
                        <input type="checkbox" wire:model.live="selectAll" class="rounded border-gray-300">
                    </th>
                    @foreach($columns as $col)
                        @if(in_array($col, $selectedColumns))
                            <th wire:click="sortBy('{{ $col }}')" style="cursor:pointer;" wire:key='thCol-{{ $loop->index }}'
                                class="px-4 py-2 text-left text-sm font-semibold text-gray-700 uppercase tracking-wider cursor-pointer select-none">
                                {{ $this->getColumnLabel($col) }}
                                @if($sortField === $col)
                                    <span class="ml-1">{!! $sortDirection === 'asc' ? '▲' : '▼' !!}</span>
                                @endif
                            </th>
                        @endif
                    @endforeach
                    @if(count($rowActions))
                        <th class="px-4 py-2 text-center">Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($rows as $row)
                    <tr wire:key='row-{{ $loop->index }}' class="hover:bg-gray-50">
                        <td class="px-3 py-2 text-center">
                            <input type="checkbox"
                                wire:model.live="selectedRows"
                                aria-label="Select row"
                                value="{{ $row->id }}"
                                class="rounded border-gray-300">
                        </td>
                        @foreach($columns as $col)
                            @if(in_array($col, $selectedColumns))
                                <td class="px-4 py-2 text-sm text-gray-700 text-{{ $this->getAlignColumn($col) }}"
                                    wire:key='rowCol-{{ $loop->parent->index }}-{{ $loop->index }}'>

                                    @if(isset($statusColumns[$col]))
                                        @php
                                            $statusValue = $row->$col;
                                            $statusClass = match($statusColumns[$col][$statusValue] ?? '') {
                                                'success' => 'bg-green-100 text-green-800',
                                                'danger'  => 'bg-red-100 text-red-800',
                                                'warning' => 'bg-yellow-100 text-yellow-800',
                                                default   => 'bg-gray-100 text-gray-800',
                                            };
                                        @endphp
                                        <span class="px-2 inline-flex text-xs font-semibold leading-5 rounded-full {{ $statusClass }}">
                                            {{ $statusValue }}
                                        </span>

                                    @elseif(isset($booleanColumns[$col]))
                                        @php
                                            $config = $booleanColumns[$col];
                                            $trueValue = $config['true'] ?? 1;
                                            $falseValue = $config['false'] ?? 0;
                                            $isTrue = $row->$col == $trueValue;
                                        @endphp
                                        <div class="flex justify-{{ $this->getAlignColumn($col) }}">
                                            <label class="inline-flex items-center cursor-pointer"
                                                wire:click.prevent="confirmToggle({{ $row->id }}, '{{ $col }}')">
                                                <input type="checkbox" wire:key='cbToggle-{{ $loop->parent->index }}-{{ $loop->index }}'
                                                    wire:click.prevent="confirmToggle({{ $row->id }}, '{{ $col }}')"
                                                    wire:loading.attr="disabled"
                                                    wire:model.live='booleanColumnsState.{{ $row->id }}.{{ $col }}'
                                                    class="sr-only peer" @checked($isTrue)>
                                                <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-green-500 transition"></div>
                                                <span class="ml-2">
                                                    {{ $isTrue ? ($config['label_true'] ?? 'Yes') : ($config['label_false'] ?? 'No') }}
                                                </span>
                                            </label>
                                        </div>
                                    @else
                                        {!! $this->renderColumn($col, $row) ?? $this->defaultColumnRender($col, $row) !!}
                                    @endif
                                </td>
                            @endif
                        @endforeach
                        @if(count($rowActions))
                            <td class="px-4 py-2 text-center">
                                @if($rowActionType === 'buttons')
                                    @foreach($rowActions as $config)
                                        <button type="button"
                                            class="px-3 py-1 text-sm text-white rounded-md
                                            bg-{{ $config['color'] ?? 'blue' }}-600 hover:bg-{{ $config['color'] ?? 'blue' }}-700 mr-1"
                                            @if(isset($config['confirm']))
                                                wire:click="confirmAction('{{ $config['method'] }}', {{ $row->id }})"
                                            @endif
                                            >
                                            {{ $config['label'] }}
                                        </button>
                                    @endforeach
                                @else
                                    <div class="relative inline-block text-left" x-data="{ open: false }">
                                        <button type="button"
                                                class="px-3 py-1 bg-gray-200 rounded-md text-gray-700 hover:bg-gray-300"
                                                x-on:click="open = !open">
                                            Actions
                                        </button>
                                        <div x-show="open" x-transition
                                            class="absolute right-0 mt-2 w-28 bg-white border rounded-md shadow-lg z-10">
                                            @foreach($rowActions as $config)
                                                <a href="#"
                                                    class="block px-3 py-1 text-sm text-gray-700 hover:bg-gray-100"
                                                    @if(isset($config['confirm'])) 
                                                        wire:click="confirmAction('{{ $config['method'] }}', {{ $row->id }})"
                                                    @endif
                                                    x-on:click="open = false">
                                                    {{ $config['label'] }}
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($selectedColumns) }}"
                            class="px-4 py-2 text-center text-gray-500">
                            No results found
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination or Load More -->
    <div class="mt-4 text-center">
        @if($paginationMode === 'load-more')
            @if($rows->count() >= $limit)
                <button wire:click="loadMore" class="px-4 py-2 bg-indigo-600 text-white rounded-lg shadow hover:bg-indigo-700">
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

{{-- <script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('confirm-toggle', ({ id, column }) => {
            if (confirm(`Are you sure you want to change ${column} for record ID ${id}?`)) {
                Livewire.dispatch('toggle-boolean', { id, column });
            }
        });
    });
</script> --}}