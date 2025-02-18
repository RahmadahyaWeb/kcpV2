<div>
    <x-alert-alpine />

    <x-loading :target="$target" />

    @hasanyrole(['storer', 'super-user'])
        <div class="card mb-3">
            <div class="card-header">
                Form scan part number & rak
            </div>
            <div class="card-body">
                <form wire:submit.prevent="save">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="part_number" class="form-label">Part Number</label>
                            <input type="text" class="form-control @error('part_number') is-invalid @enderror"
                                name="part_number" id="part_number" wire:model="part_number" placeholder="Scan part number"
                                autofocus @keyup.enter="$nextTick(() => $refs.kdRak.focus())">

                            @error('part_number')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="kd_rak" class="form-label">Kode Rak</label>
                            <input type="text" class="form-control @error('kd_rak') is-invalid @enderror" name="kd_rak"
                                id="kd_rak" wire:model="kd_rak" placeholder="Scan kode rak" x-ref="kdRak"
                                @keyup.enter="$nextTick(() => $wire.call('save'))">

                            @error('kd_rak')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endhasanyrole

    <div class="card">
        <div class="card-header">
            List Data
        </div>

        <div class="card-body">
            @hasanyrole(['inventory'])
                <form wire:submit.prevent="export">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="from_date" class="form-label">Dari Tanggal</label>
                            <input type="date" class="form-control" name="from_date" id="from_date"
                                wire:model.change="from_date">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="to_date" class="form-label">Sampai Tanggal</label>
                            <input type="date" class="form-control" name="to_date" id="to_date"
                                wire:model.change="to_date">
                        </div>
                        <div class="col-12 mb-3 d-flex justify-content-end">
                            <button type="submit" class="btn btn-success">Download Excel</button>
                        </div>
                    </div>
                </form>
            @else
                <div class="d-flex gap-2 mb-3 py-4" style="overflow-x: auto; white-space: nowrap;">
                    <button type="button" class="btn btn-primary" wire:click="update_status"
                        wire:confirm="Yakin ingin update status?">
                        Selesai
                    </button>
                </div>
            @endhasanyrole

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Part Number</th>
                            <th>Nama Part</th>
                            <th>Kode Rak</th>
                            <th>Scan by</th>
                            <th>Scanned at</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td style="white-space: nowrap">{{ $item->part_number }}</td>
                                <td>{{ $item->nama_part }}</td>
                                <td>{{ $item->kd_rak }}</td>
                                <td>{{ $item->username }}</td>
                                <td>{{ $item->created_at }}</td>
                                <td>
                                    <button type="button" wire:click="destroy({{ $item->id }})"
                                        class="btn btn-sm btn-danger">Hapus</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No Data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer">
            {{ $items->links() }}
        </div>
    </div>
</div>
