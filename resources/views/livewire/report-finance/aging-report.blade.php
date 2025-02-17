<div>
    <x-loading :target="$target" />
    <x-alert />

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Aging
                </div>
                <div class="card-body">
                    <form wire:submit="export_to_excel">
                        <div class="row gap-3">
                            <div class="col-12">
                                <label for="jenis_laporan" class="form-label">Jenis Laporan</label>
                                <select name="jenis_laporan" id="jenis_laporan" class="form-select @error('jenis_laporan') is-invalid @enderror" wire:model="jenis_laporan">
                                    <option value="">Pilih Jenis Laporan</option>
                                    <option value="aging">Aging</option>
                                </select>

                                @error('jenis_laporan')
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
