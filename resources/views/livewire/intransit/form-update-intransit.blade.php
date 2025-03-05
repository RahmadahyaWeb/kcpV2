<div>
    <x-alert />
    <x-loading :target="$target" />

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Edit {{ $item->part_no }}
                </div>
                <div class="card-body">
                    <form wire:submit.prevent="save">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="qty_terima" class="form-label">Qty diterima</label>
                                <input type="number" class="form-control @error('qty_terima') is-invalid @enderror"
                                    wire:model="qty_terima">

                                @error('qty_terima')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="col-12 mb-3">
                                <label for="kd_rak" class="form-label">Kode Rak</label>
                                <select name="kd_rak" id="kd_rak"
                                    class="form-select @error('kd_rak') is-invalid @enderror" wire:model="kd_rak">
                                    <option value="">Pilih Kode Rak</option>

                                    @foreach ($lsit_rak as $rak)
                                        <option value="{{ $rak->kd_rak }}">{{ $rak->kd_rak }}</option>
                                    @endforeach
                                </select>

                                @error('kd_rak')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-12 d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">Simpan</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
