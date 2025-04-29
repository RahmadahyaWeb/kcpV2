<div>
    <x-alert />
    <x-loading :target="$target" />

    <div class="row">
        <x-lss-navigation />

        <div class="col-12 mb-3">
            <div class="card">
                <div class="card-header">
                    Detail LSS / Part
                </div>

                <div class="card-body">
                    <div class="mb-3">
                        <form wire:submit="generateLaporanFifo">
                            <div class="row">
                                <div class="col-4 mb-3">
                                    <input type="text" class="form-control @error('part_no') is-invalid @enderror"
                                        wire:model="part_no">

                                    @error('part_no')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                                <div class="col-4 mb-3">
                                    <select class="form-select @error('bulan') is-invalid @enderror" wire:model="bulan">
                                        <option value="01">Januari</option>
                                        <option value="02">Februari</option>
                                    </select>

                                    @error('bulan')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                                <div class="col-4 mb-3">
                                    <select class="form-select @error('tahun') is-invalid @enderror" wire:model="tahun">
                                        <option value="2025">2025</option>
                                    </select>

                                    @error('tahun')
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

                    @if (!empty($resultsPerPart))
                        @foreach ($resultsPerPart as $results)
                            <h4>Ringkasan {{ $results['part_no'] }}</h4>
                            <div class="mb-3">
                                <table class="table table-bordered table-striped">
                                    <tbody>
                                        <tr>
                                            <th>Part Number</th>
                                            <td>{{ $results['part_no'] }}</td>
                                        </tr>
                                        <tr>
                                            <th>Total Pembelian</th>
                                            <td>{{ $results['total_beli'] }}</td>
                                        </tr>
                                        <tr>
                                            <th>Total Penjualan</th>
                                            <td>{{ $results['total_jual'] }}</td>
                                        </tr>
                                        <tr>
                                            <th>Stock Akhir</th>
                                            <td>
                                                {{ (array_sum(array_column($results['stock_awal'], 'qty')) + $results['total_beli']) - $results['total_jual'] }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Total Modal</th>
                                            <td>{{ number_format($results['total_modal'], 2) }}</td>
                                        </tr>
                                        <tr>
                                            <th>Total Penjualan (Rp)</th>
                                            <td>{{ number_format($results['total_penjualan'], 2) }}</td>
                                        </tr>
                                        <tr>
                                            <th>Total Profit</th>
                                            <td>{{ number_format($results['total_profit'], 2) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <h4>Stock Awal Bulan</h4>
                            <div class="mb-3">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Source</th>
                                            <th>Qty</th>
                                            <th>Harga per Unit</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($results['stock_awal'] as $stock)
                                            <tr>
                                                <td>{{ $stock['tanggal'] }}</td>
                                                <td>{{ strtoupper($stock['source']) }}</td>
                                                <td>{{ $stock['qty'] }}</td>
                                                <td>{{ number_format($stock['harga'], 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center">Tidak ada data stock awal</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <h4>Total Pembelian</h4>
                            <div class="mb-3">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Source</th>
                                            <th>Tanggal Pembelian</th>
                                            <th>Qty</th>
                                            <th>Harga per Unit</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($results['histori_pembelian'] as $pembelian)
                                            <tr>
                                                <td>{{ $pembelian['source_id'] }}</td>
                                                <td>{{ $pembelian['tanggal'] }}</td>
                                                <td>{{ $pembelian['qty'] }}</td>
                                                <td>{{ number_format($pembelian['harga'], 2) }}</td>
                                                <td>{{ number_format($pembelian['harga'] * $pembelian['qty'], 0, ',', '.') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <h4>Total Penjualan</h4>
                            <div class="mb-3">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Tanggal Penjualan</th>
                                            <th>Invoice</th>
                                            <th>Qty Terpakai</th>
                                            <th>Harga Modal</th>
                                            <th>Harga Jual</th>
                                            <th>Subtotal Modal</th>
                                            <th>Subtotal Jual</th>
                                            <th>Sumber Modal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($results['histori_penjualan'] as $penjualan)
                                            <tr>
                                                <td>{{ $penjualan['tanggal'] }}</td>
                                                <td class="text-nowrap">{{ $penjualan['noinv'] }}</td>
                                                <td>{{ $penjualan['qty'] }}</td>
                                                <td>{{ number_format($penjualan['harga_modal'], 2) }}</td>
                                                <td>{{ number_format($penjualan['harga_jual'], 2) }}</td>
                                                <td>{{ number_format($penjualan['subtotal_modal'], 2) }}</td>
                                                <td>{{ number_format($penjualan['subtotal_jual'], 2) }}</td>
                                                <td>
                                                    <strong>Source:</strong>
                                                    {{ $penjualan['sumber_modal']['source'] }}<br>
                                                    <strong>Source ID:</strong>
                                                    {{ $penjualan['sumber_modal']['source_id'] }}<br>
                                                    <strong>Tanggal Layer:</strong>
                                                    {{ $penjualan['sumber_modal']['tanggal_layer'] }}<br>
                                                    <strong>Harga per Unit Layer:</strong>
                                                    {{ number_format($penjualan['sumber_modal']['harga_per_unit_layer'], 2) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <hr>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
