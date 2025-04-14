<div>
    <x-alert />
    <x-loading :target="$target" />

    <div class="row gap-3">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Laporan LSS
                </div>
                <div class="card-body">
                    <form wire:submit="export">
                        <div class="row gap-3">
                            <div class="col-12">
                                <label for="from_date" class="form-label">Dari Tanggal</label>
                                <input type="date" class="form-control @error('from_date') is-invalid @enderror"
                                    name="from_date" id="from_date" wire:model="from_date">

                                @error('from_date')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label for="to_date" class="form-label">Sampai Tanggal</label>
                                <input type="date" class="form-control @error('to_date') is-invalid @enderror"
                                    name="to_date" id="to_date" wire:model="to_date">

                                @error('to_date')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="col-12 d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">Export</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    Detail LSS / Part
                </div>

                <div class="card-body">
                    <div class="mb-3">
                        <form wire:submit="lihat_detail">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="part_no_detail" class="form-label">Part No</label>
                                    <input type="text"
                                        class="form-control @error('part_no_detail') is-invalid @enderror"
                                        name="part_no_detail" id="part_no_detail" wire:model="part_no_detail">

                                    @error('part_no_detail')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="from_date_detail" class="form-label">Dari Tanggal</label>
                                    <input type="date"
                                        class="form-control @error('from_date_detail') is-invalid @enderror"
                                        name="from_date_detail" id="from_date_detail" wire:model="from_date_detail">

                                    @error('from_date_detail')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="to_date_detail" class="form-label">Sampai Tanggal</label>
                                    <input type="date"
                                        class="form-control @error('to_date_detail') is-invalid @enderror"
                                        name="to_date_detail" id="to_date_detail" wire:model="to_date_detail">

                                    @error('to_date_detail')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                                <div class="col-12 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary">Lihat</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>TANGGAL</th>
                                    <th>KETERANGAN</th>
                                    <th>IN</th>
                                    <th>OUT</th>
                                    <th>STOCK</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (count($items) > 0)
                                    <tr>
                                        <td>-</td>
                                        <td>AWAL QTY</td>
                                        <td>-</td>
                                        <td>-</td>
                                        <td>
                                            {{ number_format($items[0]->stock + $items[0]->kredit - $items[0]->debet, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                    @foreach ($items as $item)
                                        <tr>
                                            <td>{{ date('d-m-Y', strtotime($item->crea_date)) }}</td>
                                            <td>{{ $item->keterangan }}</td>
                                            <td>{{ $item->debet ?? '-' }}</td>
                                            <td>{{ $item->kredit ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
