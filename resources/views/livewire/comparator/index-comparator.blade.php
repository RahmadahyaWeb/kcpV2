<div>
    <div class="card">
        <div class="card-header">
            Comparator
        </div>
        <div class="card-body">

            <div class="d-flex gap-2 mb-3 py-4" style="overflow-x: auto; white-space: nowrap;">
                @hasanyrole('head-warehouse|super-user')
                    <button type="button" class="btn btn-sm btn-danger" wire:click="resetComparator"
                        wire:confirm="Yakin ingin reset?">
                        Reset
                    </button>
                @endhasanyrole

                @hasanyrole('head-warehouse|inventory|super-user')
                    <button type="button" class="btn btn-sm btn-success" wire:click="export">Download Excel</button>
                @endhasanyrole
            </div>

            <div class="mb-3">
                <input type="text" id="scan-barcode" class="form-control" wire:model="barcode"
                    wire:keydown.enter="store" placeholder="Scan barcode here" wire:loading.attr="disabled" autofocus>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Part Number</th>
                            <th>Nama Part</th>
                            <th>Qty</th>
                            <th>Edit Qty</th>
                            <th>Scan By</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td>{{ $item->part_number }}</td>
                                <td>{{ $item->nm_part }}</td>
                                <td>{{ $item->qty }}</td>
                                <td>
                                    <button wire:click="edit('{{ $item->part_number }}')" class="btn btn-sm btn-warning"
                                        data-bs-toggle="modal" data-bs-target="#modal-edit-qty">Edit</button>
                                </td>
                                <td>{{ $item->scan_by }}</td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-danger"
                                            wire:click="destroy('{{ $item->part_number }}')">
                                            Hapus
                                        </button>
                                    </div>
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
    </div>

    <div wire:ignore.self class="modal fade" id="modal-edit-qty" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="modal-edit-qtyLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="modal-edit-qtyLabel">Edit Qty</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit="updateQty">
                        @csrf
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label">Part Number</label>
                                <input type="text" class="form-control" value="{{ $part_number }}" disabled>
                                <input type="hidden" value="{{ $part_number }}" name="part_number">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Qty saat ini</label>
                                <input type="text" class="form-control" value="{{ $qty }}" disabled>
                            </div>
                            <div class="col-12">
                                <label for="edited_qty" class="form-label">Qty baru</label>
                                <input type="number" class="form-control @error('edited_qty') is-invalid @enderror"
                                    name="edited_qty" wire:model="edited_qty">
                                @error('edited_qty')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="col-12 d-grid">
                                <button type="submit" class="btn btn-primary">Simpan</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    @push('script')
        @livewireScripts
        <script>
            Livewire.on('qty-saved', () => {
                document.getElementById('scan-barcode').focus();
                $('#modal-edit-qty').modal('hide');
            });
        </script>
    @endpush
</div>
