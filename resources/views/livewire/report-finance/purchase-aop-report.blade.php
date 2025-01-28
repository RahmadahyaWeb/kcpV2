<div>
    <x-loading :target="$target" />
    <x-alert />

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Invoice AOP
                </div>
                <div class="card-body">
                    <label for="from_date" class="form-label">Dari Tanggal</label>
                    <input type="date" class="form-control" name="from_date" id="from_date" wire:model="from_date">
                </div>
            </div>
        </div>
    </div>
</div>
