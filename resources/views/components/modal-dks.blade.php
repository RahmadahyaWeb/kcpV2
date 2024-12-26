<!-- Edit Modal -->
<div wire:ignore.self class="modal fade" id="modal-dks" tabindex="-1" aria-labelledby="modal-dksLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modal-dksLabel">DKS Scan</h1>
            </div>
            <div class="modal-body">
                <div id="placeholder" class="placeholder text-center">
                    <p id="scan-text">Click "Start Scanning" to begin.</p>
                    <div id="loading" class="text-center d-none">
                        <div class="spinner-border" role="status"></div>
                        <div>Loading...</div>
                    </div>
                </div>

                <div id="reader" class="img-fluid mb-3"></div>

                <div id="result" class="mb-3"></div>

                <div class="d-grid">
                    <button id="start-button" class="btn btn-success">Start Scanning</button>
                    <button id="stop-button" class="btn btn-danger d-none" style="display: none;">
                        Stop Scanning
                    </button>
                    <button class="btn btn-secondary my-2" data-bs-dismiss="modal" >Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
