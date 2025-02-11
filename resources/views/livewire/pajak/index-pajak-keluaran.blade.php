<div>
    <x-loading :target="$target" />
    <x-alert />

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Pajak Keluaran
                </div>
                <div class="card-body">
                    <form wire:submit="export_to_excel">
                        <div class="row gap-3">
                            <div class="col-12">
                                <label for="type_invoice" class="form-label">Tipe Invoice</label>
                                <select name="type_invoice" id="type_invoice" class="form-select @error('type_invoice') is-invalid @enderror" wire:model="type_invoice">
                                    <option value="">Pilih Tipe Invoice</option>

                                    <option value="non-kanvas">NON KANVAS</option>
                                    <option value="kanvas">KANVAS</option>
                                </select>

                                @error('type_invoice')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label for="from_date" class="form-label">Dari Tanggal</label>
                                <input type="date" class="form-control @error('from_date') is-invalid @enderror"
                                    name="from_date" id="from_date" wire:model="from_date">

                                @error('from_date')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label for="to_date" class="form-label">Sampai Tanggal</label>
                                <input type="date" class="form-control @error('to_date') is-invalid @enderror"
                                    name="to_date" id="to_date" wire:model="to_date">

                                @error('to_date')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="col-12 d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">Export</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
