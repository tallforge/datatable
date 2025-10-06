<div class="modal fade modal-sm" id="confirmRowActionModal" data-bs-backdrop="static"
    data-bs-keyboard="false" data-bs-container="body" tabindex="-1" style="display:none;" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" wire:click="cancelAction"></button>
            </div>
            <div class="modal-body">
                <p>{{ $confirmMessage }}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" wire:click="cancelAction">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal" wire:click="performConfirmedAction">Confirm</button>
            </div>
        </div>
    </div>
</div>
