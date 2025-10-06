@if($showConfirmBulkActionModal)
    <div class="fixed inset-0 flex items-center justify-center bg-gray-800/50 z-50">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
            @php
                $action = $confirmingBulkAction ? $this->bulkActions()[$confirmingBulkAction] ?? null : null;
                $message = $action['confirm'] ?? 'Are you sure you want to perform this action?';
                $label = $action['label'] ?? ucfirst($confirmingBulkAction);
            @endphp

            <h2 class="text-lg font-semibold text-gray-800 mb-3">{{ $label }}</h2>
            <p class="text-gray-600 mb-6">{{ $message }}</p>

            <div class="flex justify-end gap-3">
                <button wire:click="resetBulkActionModal"
                    class="px-4 py-2 text-sm rounded-md border border-gray-300 hover:bg-gray-100">
                    Cancel
                </button>

                <button wire:click="performBulkAction"
                    class="px-4 py-2 text-sm rounded-md bg-red-600 text-white hover:bg-red-700">
                    Confirm
                </button>
            </div>
        </div>
    </div>
@endif