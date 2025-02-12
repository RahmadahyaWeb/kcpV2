<div>
    <x-loading :target="$target" />
    <x-alert />

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Laporan Invoice
                </div>
                <div class="card-body">
                    <form wire:submit="export_to_excel">
                        <div class="row gap-3">
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
                            <div class="col-12">
                                <label class="form-label">Pilih Toko</label>

                                <input type="text" class="form-control mb-1" placeholder="Cari Toko" wire:model.live.debounce.150ms="search_toko">

                                <div class="checkbox-container"
                                    style="max-height: 200px; overflow-y: auto; border: 1px solid #ced4da; padding: 10px; border-radius: 4px;">
                                    @foreach ($master_toko as $toko)
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input"
                                                id="store_{{ $toko->kd_outlet }}" value="{{ $toko->kd_outlet }}"
                                                wire:model="selected_stores">
                                            <label class="form-check-label" for="store_{{ $toko->kd_outlet }}">
                                                {{ $toko->nm_outlet }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                @error('selected_stores')
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
