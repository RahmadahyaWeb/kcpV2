<div>
    <x-alert-alpine />

    <x-loading :target="$target" />

    @hasanyrole(['storer', 'super-user'])
        @if ($status == 'N')
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
                                    name="part_number" id="part_number" wire:model="part_number"
                                    placeholder="Scan part number" autofocus
                                    @keyup.enter="$nextTick(() => $refs.kdRak.focus())">

                                @error('part_number')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="kd_rak" class="form-label">Kode Rak</label>
                                <input type="text" class="form-control @error('kd_rak') is-invalid @enderror"
                                    name="kd_rak" id="kd_rak" wire:model="kd_rak" placeholder="Scan kode rak"
                                    x-ref="kdRak" @keyup.enter="$nextTick(() => $wire.call('save'))">

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
        @endif
    @endhasanyrole

    <div class="card">
        <div class="card-header">
            Details <strong>{{ $label }}</strong>
        </div>

        <div class="card-body">
            @if ($status == 'N')
                <button class="btn btn-success mb-3" wire:click="update_status"
                    wire:confirm="Yakin ingin selesaikan?">Selesai</button>
            @else
                <button class="btn btn-success mb-3" wire:click="export">Export</button>
            @endif

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Part Number</th>
                            <th>Nama Part</th>
                            <th>Kode Rak</th>
                            <th>Scan By</th>
                            <th>Tanggal Scan</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td class="text-nowrap">{{ $item->part_number }}</td>
                                <td>{{ $item->nama_part }}</td>
                                <td>{{ $item->kd_rak }}</td>
                                <td>{{ $item->user_id }}</td>
                                <td>{{ $item->created_at }}</td>
                                <td>
                                    @if ($status == 'N')
                                        <button class="btn btn-sm btn-danger"
                                            wire:click="destroy('{{ $item->id }}')">Hapus</button>
                                    @endif
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
