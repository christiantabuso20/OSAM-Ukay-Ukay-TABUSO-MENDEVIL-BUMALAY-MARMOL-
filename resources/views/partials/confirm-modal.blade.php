{{-- Shared confirmation dialog. Opened automatically for any <form> that has data-confirm-* attributes (see public/js/app.js). --}}
<div class="modal fade confirm-modal" id="confirmModal" tabindex="-1" aria-labelledby="confirmTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4 pb-3">
                <div class="modal-icon is-berry" id="confirmIcon"><i class="fa-solid fa-circle-question"></i></div>
                <h5 class="mb-2" id="confirmTitle">Are you sure?</h5>
                <p class="text-muted mb-0" id="confirmText"></p>
                <div class="confirm-name d-none" id="confirmName"></div>
            </div>
            <div class="modal-footer justify-content-center border-0 pt-0 pb-4 gap-2">
                <button type="button" class="btn btn-light px-4" id="confirmCancel">Cancel</button>
                <button type="button" class="btn btn-berry px-4" id="confirmOk">Confirm</button>
            </div>
        </div>
    </div>
</div>
