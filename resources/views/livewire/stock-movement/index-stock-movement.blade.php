<div>
    <x-alert />
    <x-loading :target="$target" />

    <div class="card">
        <div class="card-header">
            Data Stock Movement From Bosnet
        </div>
        <div class="card-body">
            <div class="row mb-3 g-2">
                <div class="col-md-4">
                    <label class="form-label">Part Number</label>
                    <input type="text" class="form-control" wire:model.live.debounce.1000ms="part_number"
                        placeholder="Cari berdasarkan part number">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Part Number</th>
                            <th>Status</th>
                            <th>Keterangan</th>
                            <th>Qty</th>
                            <th>Debet</th>
                            <th>Kredit</th>
                            <th>Stock Sebelum</th>
                            <th>Stock Sesudah</th>
                            <th>Stock On Hand</th>
                            <th>Stock Booking</th>
                            <th>Stock In Transit</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($log_stock as $item)
                            <tr>
                                <td style="white-space: nowrap">
                                    {{ $item->part_no }}
                                </td>
                                <td>{{ $item->status }}</td>
                                <td>{{ $item->keterangan }}</td>
                                <td>{{ $item->qty }}</td>
                                <td>{{ $item->debet_qty }}</td>
                                <td>{{ $item->kredit_qty }}</td>
                                <td>{{ $item->stock_sebelum }}</td>
                                <td>{{ $item->stock_sesudah }}</td>
                                <td>{{ $item->stock_on_hand }}</td>
                                <td>{{ $item->stock_booking }}</td>
                                <td>{{ $item->stock_in_transit }}</td>
                                <td>
                                    <span style="font-size: 0.9375rem; color: #646e78;" class="badge p-0">
                                        {{ $item->crea_date }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center">No Data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $log_stock->links() }}
        </div>
    </div>
</div>
