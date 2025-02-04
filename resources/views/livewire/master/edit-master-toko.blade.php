<div>
    <x-loading target="" />

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Edit Toko | {{ $nama_toko }} ({{ $kode_toko }})
                </div>
                <div class="card-body">
                    <form wire:submit.prevent="save">
                        <div class="row gap-2">
                            <div class="col-12">
                                <label for="latitude" class="form-label">latitude</label>
                                <input type="text" class="form-control @error('latitude') is-invalid @enderror"
                                    placeholder="Latitude" wire:model="latitude">

                                @error('latitude')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label for="longitude" class="form-label">Longitude</label>
                                <input type="text" class="form-control @error('longitude') is-invalid @enderror"
                                    placeholder="longitude" wire:model="longitude">

                                @error('longitude')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Simpan</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
