<div>
    <x-alert />
    <x-loading :target="$target" />

    <div class="card mb-3">
        <div class="card-header">
            Detail Retur: <strong>{{ $header->noretur }}</strong>
        </div>

        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    @foreach ([
        'No Retur' => $header->noretur,
        'No Invoice' => $header->noinv,
        'Toko' => $header->nm_outlet,
    ] as $label => $value)
                        <div class="row mb-3">
                            <div class="col-4 col-md-4">{{ $label }}</div>
                            <div class="col-auto">:</div>
                            <div class="col-auto">{{ $value }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Send to Bosnet Button (Only for KCP Status) -->
            @if (isset($header->flag_bosnet) && $header->flag_bosnet == 'N' || $header->flag_bosnet == 'F')
                <div class="row">
                    <form wire:submit="sendToBosnet" wire:confirm="Yakin ingin kirim data ke Bosnet?">
                        <div class="col d-grid">
                            <hr>
                            <button type="submit" class="btn btn-warning" wire:offline.attr="disabled">
                                <span wire:loading.remove wire:target="sendToBosnet">Kirim ke Bosnet</span>
                                <span wire:loading wire:target="sendToBosnet">Loading...</span>
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            Detail Material Retur: <strong>{{ $header->noretur }}</strong>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>No Part</th>
                            <th>Nama Part</th>
                            <th>Qty</th>
                            <th>Harga / Pcs (Rp)</th>
                            <th>Disc (%)</th>
                            <th>Nominal (Rp)</th>
                            <th>Nominal Discount (Rp)</th>
                            <th>Nominal Total (Rp)</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td>{{ $item->part_no }}</td>
                                <td>{{ $item->nm_part }}</td>
                                <td>{{ $item->qty }}</td>
                                <td>{{ number_format($item->hrg_pcs, 0, ',', '.') }}</td>
                                <td>{{ $item->disc }}</td>
                                <td>{{ number_format($item->nominal, 0, ',', '.') }}</td>
                                <td>{{ number_format($item->nominal_disc, 0, ',', '.') }}</td>
                                <td>{{ number_format($item->nominal_total, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">No Data</td>
                            </tr>
                        @endforelse
                        <tr>
                            <td colspan="7" class="fw-bold">Total</td>
                            <td>{{ number_format($nominal_total, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
