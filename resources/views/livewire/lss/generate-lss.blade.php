<div>
    <x-alert />
    <x-loading :target="$target" />

    <div class="row">
        <x-lss-navigation />

        <div class="col-6 mb-3">
            <div class="card">
                <div class="card-header">
                    Generate Pembelian
                </div>
                <div class="card-body">
                    <form wire:submit="seedFifoLayers">
                        <div class="row">
                            <div class="col-6 mb-3">
                                <select class="form-select @error('bulan') is-invalid @enderror" wire:model="bulan">
                                    <option value="01">Januari</option>
                                    <option value="02">Februari</option>
                                </select>

                                @error('bulan')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="col-6 mb-3">
                                <select class="form-select @error('tahun') is-invalid @enderror" wire:model="tahun">
                                    <option value="2025">2025</option>
                                </select>

                                @error('tahun')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="col-12 d-flex justify-content-end">
                                <button type="submit" class="btn btn-success">GENERATE PEMBELIAN</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-6 mb-3">
            <div class="card">
                <div class="card-header">
                    Generate Penjualan
                </div>
                <div class="card-body">
                    <form wire:submit="prosesPenjualanFifo">
                        <div class="row">
                            <div class="col-6 mb-3">
                                <select class="form-select @error('bulan') is-invalid @enderror" wire:model="bulan">
                                    <option value="01">Januari</option>
                                    <option value="02">Februari</option>
                                </select>

                                @error('bulan')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="col-6 mb-3">
                                <select class="form-select @error('tahun') is-invalid @enderror" wire:model="tahun">
                                    <option value="2025">2025</option>
                                </select>

                                @error('tahun')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="col-12 d-flex justify-content-end">
                                <button type="submit" class="btn btn-success">GENERATE PENJUALAN</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
