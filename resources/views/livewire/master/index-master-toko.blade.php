<div>
    <x-alert />
    <x-loading :target="$target" />

    <div class="card">
        <div class="card-header">
            Master Toko
        </div>
        <div class="card-body">
            {{-- <div class="d-flex gap-2 mb-3 py-4" style="overflow-x: auto; white-space: nowrap;">
                <button type="button" class="btn btn btn-primary" wire:click="sync_lokasi">Sync Lokasi</button>
            </div> --}}

            <div class="row mb-3">
                <div class="col-md-4 mb-3">
                    <label for="kode_toko" class="form-label">Kode Toko</label>
                    <input type="text" class="form-control" placeholder="Kode Toko" wire:model.live.debounce.150ms="kode_toko">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="nama_toko" class="form-label">Nama Toko</label>
                    <input type="text" class="form-control" placeholder="Nama Toko" wire:model.live.debounce.150ms="nama_toko">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" wire:model.change="status">
                        <option value="">Pilih status</option>
                        <option value="Y">Aktif</option>
                        <option value="N">Nonaktif</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Kode Toko</th>
                            <th>Nama Toko</th>
                            <th>Provinsi</th>
                            <th>Kabupaten</th>
                            <th>Status</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr>
                                <td style="white-space: nowrap">{{ $item->kd_outlet }}</td>
                                <td style="white-space: nowrap">{{ $item->nm_outlet }}</td>
                                <td>{{ $item->provinsi }}</td>
                                <td>{{ $item->nm_area }}</td>
                                <td>{{ $item->status }}</td>

                                <td class="text-end" style="white-space: nowrap">
                                    <a href="{{ route('master-toko.edit', $item->kd_outlet) }}"
                                        class="btn btn-sm btn-warning" wire:navigate>Edit</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $items->links(data: ['scrollTo' => false]) }}
        </div>
    </div>
</div>
