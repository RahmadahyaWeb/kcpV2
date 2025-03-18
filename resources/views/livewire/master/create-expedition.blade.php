<div>
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Tambah Expedition
                </div>

                <div class="card-body">
                    <form wire:submit.prevent="save">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="kd_expedition" class="form-label">Kode Expedition</label>
                                <input type="text" class="form-control @error('kd_expedition') is-invalid @enderror"
                                    id="kd_expedition" name="kd_expedition" wire:model="kd_expedition"
                                    placeholder="Kode Expedition">

                                @error('kd_expedition')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="col-12 mb-3">
                                <label for="nama_expedition" class="form-label">Nama Expedition</label>
                                <input type="text"
                                    class="form-control @error('nama_expedition') is-invalid @enderror"
                                    id="nama_expedition" name="nama_expedition" wire:model="nama_expedition"
                                    placeholder="Kode Expedition">

                                @error('nama_expedition')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="col-12 mb-3">
                                <label for="latitude" class="form-label">Latitude</label>
                                <input type="text" class="form-control @error('latitude') is-invalid @enderror"
                                    id="latitude" name="latitude" wire:model="latitude" placeholder="Kode Expedition">

                                @error('latitude')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="col-12 mb-3">
                                <label for="longitude" class="form-label">Longitude</label>
                                <input type="text" class="form-control @error('longitude') is-invalid @enderror"
                                    id="longitude" name="longitude" wire:model="longitude"
                                    placeholder="Kode Expedition">

                                @error('longitude')
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
