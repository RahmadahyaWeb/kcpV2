<div wire:loading.flex wire:target="{{ $target ?? '' }}, gotoPage"
    class="text-center justify-content-center align-items-center"
    style="position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.1); z-index: 9999;">
    <div class="spinner-border text-primary" role="status"
        style="width: 3rem; height: 3rem; border-width: 0.4em; margin: auto;">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>

<div wire:offline>
    <div class="d-flex flex-column text-center justify-content-center align-items-center"
        style="position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background-color: rgba(0, 0, 0, 0.1); z-index: 9999;">
        <div class="card">
            <div class="card-body">
                <p class="text-dark fw-bold m-0">Anda sedang offline. Pastikan perangkat terhubung
                    ke internet.</p>
            </div>
        </div>
    </div>
</div>
