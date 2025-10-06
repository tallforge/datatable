@if($showConfirmModal)
    <div class="modal fade show d-block" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                @php
                    $action = $confirmingAction ? $this->bulkActions()[$confirmingAction] ?? null : null;
                    $message = $action['confirm'] ?? 'Are you sure you want to perform this action?';
                    $label = $action['label'] ?? ucfirst($confirmingAction);
                  @endphp

                <div class="modal-header">
                    <h5 class="modal-title">{{ $label }}</h5>
                    <button type="button" wire:click="resetBulkActionModal" class="btn-close"></button>
                </div>

                <div class="modal-body">
                    <p>{{ $message }}</p>
                </div>

                <div class="modal-footer">
                    <button type="button" wire:click="resetBulkActionModal" class="btn btn-secondary">Cancel</button>
                    <button type="button" wire:click="performBulkAction" class="btn btn-danger">Confirm</button>
                </div>
            </div>
        </div>
    </div>
@endif