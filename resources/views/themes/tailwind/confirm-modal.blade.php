<div x-data="{ open: @entangle('confirmingAction') }">
    <div x-show="open" class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-sm">
            <h2 class="text-lg font-semibold mb-3">Confirm Action</h2>
            <p class="text-gray-600 mb-5">{{ $confirmMessage }}</p>
            <div class="flex justify-end gap-3">
                <button wire:click="cancelAction"
                        class="px-4 py-1 text-sm rounded bg-gray-200 hover:bg-gray-300">
                    Cancel
                </button>
                <button wire:click="performConfirmedAction"
                        class="px-4 py-1 text-sm rounded bg-red-600 text-white hover:bg-red-700">
                    Confirm
                </button>
            </div>
        </div>
    </div>
</div>
