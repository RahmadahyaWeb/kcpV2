<div>
    <x-alert />
    <x-loading :target="$target" />

    <div class="row gap-3">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Laporan Stock
                </div>
                <div class="card-body">
                    <form wire:submit="export">
                        <div class="row gap-3">
                            <div class="col-12">
                                <label class="form-label">Pilih Supplier</label>

                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="supplier_ASTRA_OTOPART"
                                        value="ASTRA OTOPART" wire:model="selected_suppliers">
                                    <label class="form-check-label" for="supplier_ASTRA_OTOPART">
                                        ASTRA OTOPART
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="supplier_ABM"
                                        value="ABM" wire:model="selected_suppliers">
                                    <label class="form-check-label" for="supplier_ABM">
                                        ABM
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="supplier_SSI"
                                        value="SSI" wire:model="selected_suppliers">
                                    <label class="form-check-label" for="supplier_SSI">
                                        SSI
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="supplier_KMC"
                                        value="KMC" wire:model="selected_suppliers">
                                    <label class="form-check-label" for="supplier_KMC">
                                        KMC
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="supplier_NON_AOP"
                                        value="NON AOP" wire:model="selected_suppliers">
                                    <label class="form-check-label" for="supplier_NON_AOP">
                                        NON AOP
                                    </label>
                                </div>
                                @error('selected_suppliers')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Pilih Kategori</label>

                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="kategori_2W"
                                        value="2W" wire:model="selected_categories">
                                    <label class="form-check-label" for="kategori_2W">
                                        2W
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="kategori_4W"
                                        value="4W" wire:model="selected_categories">
                                    <label class="form-check-label" for="kategori_4W">
                                        4W
                                    </label>
                                </div>
                                @error('selected_categories')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-12 d-flex justify-content-end">
                                <button type="submit" class="btn btn-success">Export</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    Stock Part
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
                                        <td class="text-nowrap">{{ $item->kd_gudang == 'GD1' ? 'KAL-SEL' : 'KAL-TENG' }}
                                        </td>
                                        <td class="text-nowrap">{{ $item->part_no }}</td>
                                        <td>{{ $item->nm_part }}</td>
                                        <td>{{ $item->group_part }}</td>
                                        <td>{{ $item->kategori_part }}</td>
                                        <td>{{ $item->level4 }}</td>
                                        <td class="text-nowrap">Rp
                                            {{ number_format($item->hrg_jual_pcs, 0, ',', '.') }}
                                        </td>
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
    </div>
</div>
