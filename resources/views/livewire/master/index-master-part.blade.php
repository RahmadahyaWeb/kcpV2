<div>

    <x-alert />
    <x-loading :target="$target" />

    <div class="card">
        <div class="card-header">
            Master Part
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
                            <th>PART NO</th>
                            <th>NAMA PART</th>
                            <th>KATEGORI</th>
                            <th>GROUP PART</th>
                            <th>PRODUK PART</th>
                            <th>KELOMPOK PART</th>
                            <th>SUB KELOMPOK PART</th>
                            <th>SUB KELOMPOK PART 1</th>
                            <th>KODE LEVEL 4 AOP</th>
                            <th>HARGA BELI / PCS</th>
                            <th>HARGA JUAL / PCS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td class="text-nowrap">{{ $item->part_no }}</td>
                                <td>{{ $item->nm_part }}</td>
                                <td>{{ $item->kategori_part }}</td>
                                <td>{{ $item->group_part }}</td>
                                <td>{{ $item->produk_part }}</td>
                                <td>{{ $item->kelompok_part }}</td>
                                <td>{{ $item->sub_kel_part }}</td>
                                <td>{{ $item->sub_kel_part1 }}</td>
                                <td>{{ $item->level4 }}</td>
                                <td class="text-nowrap">Rp {{ number_format($item->hrg_beli_pcs, 0, ',', '.') }}
                                </td>
                                <td class="text-nowrap">Rp {{ number_format($item->hrg_jual_pcs, 0, ',', '.') }}
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td class="text-center" colspan="11">No Data</td>
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
