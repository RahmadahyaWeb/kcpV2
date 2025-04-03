<div>
    <x-alert />
    <x-loading :target="$target" />

    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col">
                    Stock Part
                </div>
                <div class="col d-flex justify-content-end">
                    <button wire:click="export" class="btn btn-sm btn-success">Export</button>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4 mb-3">
                    <label for="search" class="form-label">Cari Part</label>
                    <input type="text" class="form-control" placeholder="Cari Part"
                        wire:model.live.debounce.150ms="search">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>KODE GUDANG</th>
                            <th>PART NO</th>
                            <th>NAMA PART</th>
                            <th>GROUP PART</th>
                            <th>KATEGORI PART</th>
                            <th>KODE LEVEL 4 AOP</th>
                            <th>HARGA / PCS</th>
                            <th>STOCK ON HAND</th>
                            <th>STOCK BOOKING</th>
                            <th>STOCK</th>
                            <th>INTRANSIT</th>
                            <th>KETERANGAN</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($paginatedItems as $item)
                            <tr>
                                <td class="text-nowrap">{{ $item->kd_gudang == 'GD1' ? 'KAL-SEL' : 'KAL-TENG' }}</td>
                                <td class="text-nowrap">{{ $item->part_no }}</td>
                                <td>{{ $item->nm_part }}</td>
                                <td>{{ $item->group_part }}</td>
                                <td>{{ $item->kategori_part }}</td>
                                <td>{{ $item->level4 }}</td>
                                <td class="text-nowrap">Rp {{ number_format($item->hrg_jual_pcs, 0, ',', '.') }}</td>
                                <td>{{ $item->stock - $item->stock_booking }}</td>
                                <td>{{ $item->stock_booking }}</td>
                                <td>{{ $item->stock }}</td>
                                <td>{{ $item->qty_intransit }}</td>
                                <td>{{ $item->ket_status }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-center" colspan="12">No Data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer">
            {{ $paginatedItems->links() }}
        </div>
    </div>
</div>
