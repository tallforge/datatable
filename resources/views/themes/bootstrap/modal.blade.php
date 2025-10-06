<div wire:ignore.self class="modal fade @if($confirmingAction) show d-block @endif"
     tabindex="-1" style="@if(!$confirmingAction) display:none; @endif background:rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Action</h5>
                <button type="button" class="btn-close" wire:click="cancelAction"></button>
            </div>
            <div class="modal-body">
                <p>{{ $confirmMessage }}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" wire:click="cancelAction">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm" wire:click="performConfirmedAction">Confirm</button>
            </div>
        </div>
    </div>
</div>
