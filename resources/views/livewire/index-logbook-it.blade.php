<div>
    <x-alert />
    <x-loading :target="$target" />

    @if (Auth::user()->username == 'rahmadahya' || Auth::user()->username == 'khaidir')
        <div class="row mb-3">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        Form Logbook IT
                    </div>

                    <div class="card-body">
                        <form wire:submit="store_logbook">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label for="kegiatan" class="form-label">Kegiatan</label>
                                    <input type="text" class="form-control @error('kegiatan') is-invalid @enderror"
                                        wire:model="kegiatan">

                                    @error('kegiatan')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="col-6 mb-3">
                                    <label for="tanggal" class="form-label">Tanggal</label>
                                    <input type="date" class="form-control @error('tanggal') is-invalid @enderror"
                                        wire:model="tanggal">

                                    @error('tanggal')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="col-6 mb-3">
                                    <label for="jam" class="form-label">Jam</label>
                                    <input type="time" class="form-control @error('jam') is-invalid @enderror"
                                        wire:model="jam">

                                    @error('jam')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="col-12 mb-3">
                                    <label for="requested_by" class="form-label">Requested By</label>
                                    <input type="text" class="form-control @error('requested_by') is-invalid @enderror"
                                        wire:model="requested_by">

                                    @error('requested_by')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="col-12 mb-3">
                                    <label for="foto_kegiatan" class="form-label">Foto Kegiatan</label>
                                    <input type="file"
                                        class="form-control @error('foto_kegiatan') is-invalid @enderror"
                                        wire:model="foto_kegiatan">

                                    @error('foto_kegiatan')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="col-12 mb-3 d-flex justify-content-end">
                                    <button class="btn btn-primary">Submit</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    Logbook IT
                </div>

                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-6">
                            <label for="filter_tanggal_mulai" class="form-label">Dari Tanggal</label>
                            <input type="date" class="form-control" wire:model.change="filter_tanggal_mulai">

                        </div>

                        <div class="col-6">
                            <label for="filter_tanggal_akhir" class="form-label">Sampai Tanggal</label>
                            <input type="date" class="form-control" wire:model.change="filter_tanggal_akhir"
                                @disabled(!$filter_tanggal_mulai)>
                        </div>
                    </div>


                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Kegiatan</th>
                                    <th>Tanggal</th>
                                    <th>Jam</th>
                                    <th>Requested By</th>
                                    <th>Foto Kegiatan</th>
                                </tr>
                            </thead>

                            @forelse ($items as $item)
                                <tr>
                                    <td><i>{{ $item->crea_by }}</i></td>
                                    <td>{{ $item->kegiatan }}</td>
                                    <td>{{ $item->tanggal }}</td>
                                    <td>{{ \Carbon\Carbon::parse($item->jam)->format('H:i') }}</td>
                                    <td>{{ $item->requested_by ?? '-' }}</td>
                                    <td>
                                        @if ($item->foto_kegiatan && $item->foto_kegiatan !== '-')
                                            <a href="{{ Storage::url($item->foto_kegiatan) }}" target="_blank">
                                                Lihat foto kegiatan
                                            </a>
                                        @else
                                            <span>Tidak ada foto kegiatan</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">No Data</td>
                                </tr>
                            @endforelse
                        </table>
                    </div>
                </div>

                <div class="card-footer">
                    {{ $items->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
