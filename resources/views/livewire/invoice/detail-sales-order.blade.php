<div>
    <x-loading :target="$target" />

    <div class="card mb-3">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col">
                    Detail Sales Order: <strong>{{ $noso }}</strong>
                </div>

                <div class="col d-flex justify-content-end">
                    <button wire:click="create_invoice('{{ $noso }}')" type="button" class="btn btn-success">
                        Buat Invoice
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    @foreach ([
        'No Sales Order' => $header->noso,
        'Kode / Nama Toko' => $header->kd_outlet . '/' . $header->nm_outlet,
        'Tanggal Jatuh Tempo' => date('d-m-Y', strtotime('+' . $header->jth_tempo . ' days')) . ($header->jth_tempo == 0 ? ' [CASH]' : ''),
    ] as $label => $value)
                        <div class="row mb-3">
                            <div class="col-4 col-md-4">{{ $label }}</div>
                            <div class="col-auto">:</div>
                            <div class="col-auto">{{ $value }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            Detail Material: <strong>{{ $noso }}</strong>
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
                        @php
                            $total = 0;
                        @endphp

                        @forelse ($items as $item)
                            @php
                                $total += $item->nominal_total;
                            @endphp
                            <tr>
                                <td class="text-nowrap">
                                    {{ $item->part_no }}
                                </td>
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
                            <td>{{ number_format($total, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
