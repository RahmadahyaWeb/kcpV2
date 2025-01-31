<div>
    <x-alert />
    <x-loading :target="$target" />

    <div class="card mb-3">
        <div class="card-header">
            Detail Customer Payment: <b>{{ $customer_payment_header->no_piutang }}</b>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    @foreach ([
        'No Piutang' => $customer_payment_header->no_piutang,
        'Kode Toko' => $customer_payment_header->kd_outlet,
        'Nama Toko' => $customer_payment_header->nm_outlet,
        'Nominal Potong' => number_format($customer_payment_header->nominal_potong, 0, ',', '.'),
        'Pembayaran Via' => $customer_payment_header->pembayaran_via,
        'No BG' => $customer_payment_header->no_bg,
        'Jatuh Tempo BG' => $customer_payment_header->tgl_jth_tempo_bg,
        'Tanggal' => $customer_payment_header->crea_date,
    ] as $label => $value)
                        <div class="row mb-3">
                            <div class="col-4 col-md-4">{{ $label }}</div>
                            <div class="col-auto">:</div>
                            <div class="col-auto">{{ $value }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="row g-2 mb-3">
                @if ($customer_payment_header->status == 'O')
                    <div class="col-12">
                        <hr>
                    </div>
                    <div class="col d-grid">
                        <button class="btn btn-success" wire:click="potong_piutang"
                            wire:confirm="Yakin ingin potong piutang toko?">
                            Potong Piutang Toko
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            Detail Customer Payment: <b>{{ $customer_payment_header->no_piutang }}</b>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead">
                        <tr>
                            <th>No Invoice</th>
                            <th>Nominal Potong</th>
                            <th>Nominal Pembayaran Sebelumnya</th>
                            <th>Nominal Invoice</th>
                            <th>Sisa Piutang (Invoice)</th>
                            <th>No BG</th>
                            <th>Bank</th>
                            <th>Keterangan</th>
                        </tr>
                        </thead>
                        <tbody>
                            @foreach ($customer_payment_details as $item)
                                @php
                                    $nominal_pembayaran_sebelumnya = $model::get_nominal_pembayaran($item->noinv);
                                    $nominal_invoice = $model::get_nominal_invoice($item->noinv);
                                @endphp
                                <tr>
                                    <td style="white-space: nowrap">
                                        {{ $item->noinv }}
                                    </td>
                                    <td>{{ number_format($item->nominal, 0, ',', '.') }}</td>
                                    <td>
                                        {{ number_format($nominal_pembayaran_sebelumnya, 0, ',', '.') }}
                                    </td>
                                    <td>{{ number_format($nominal_invoice, 0, ',', '.') }}</td>
                                    <td>
                                        {{ number_format($nominal_invoice - $nominal_pembayaran_sebelumnya, 0, ',', '.') }}
                                    </td>
                                    <td style="white-space: nowrap">
                                        {{ $item->no_bg }}
                                    </td>
                                    <td>{{ $item->bank }}</td>
                                    <td>{{ $item->keterangan }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
