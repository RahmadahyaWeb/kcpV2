<div>
    <x-alert />
    <x-loading :target="$target" />

    <div class="row">
        <div class="col-12 mb-3">
            <div class="card">
                <div class="card-header">
                    Detail Invoice Non AOP: <b>{{ $invoiceNon }}</b>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="row mb-3">
                                <div class="col col-4 col-md-4">
                                    <div>Invoice Non</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>{{ $invoiceNon }}</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col col-4 col-md-4">
                                    <div>Customer To</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>{{ $header->customerTo }}</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col col-4 col-md-4">
                                    <div>Supplier</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>{{ $header->supplierName }}</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col col-4 col-md-4">
                                    <div>Tanggal Nota</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>{{ date('d-m-Y', strtotime($header->billingDocumentDate)) }}</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col col-4 col-md-4">
                                    <div>Tanggal Jatuh Tempo</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>{{ date('d-m-Y', strtotime($header->tanggalJatuhTempo)) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if (!$details->isEmpty() && $header->flag_selesai == 'N')
                        <div class="row">
                            <form wire:submit="updateFlag" wire:confirm="Yakin ingin update flag?">
                                <div class="col d-grid">
                                    <hr>
                                    <button type="submit" class="btn btn-success">
                                        <span wire:loading.remove wire:target="updateFlag">Selesai</span>
                                        <span wire:loading wire:target="updateFlag">Loading...</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    @elseif ($header->flag_selesai == 'Y' && $header->status == 'KCP')
                        <div class="row">
                            <form wire:submit="sendToBosnet" wire:confirm="Yakin ingin kirim data ke Bosnet?">
                                <div class="col d-grid">
                                    <hr>
                                    <button type="submit" class="btn btn-warning">
                                        <span wire:loading.remove wire:target="sendToBosnet">Kirim ke Bosnet</span>
                                        <span wire:loading wire:target="sendToBosnet">Loading...</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if ($header->flag_selesai == 'N')
            <div class="col-12 mb-3">
                <div class="card">
                    <div class="card-header">
                        Form Tambah Item
                    </div>
                    <div class="card-body">
                        <form wire:submit="addItem">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label class="form-label">No Part</label>
                                    <input type="text" class="form-control mb-2" wire:model.live="search"
                                        placeholder="Cari Part">
                                    <select class="form-select @error('materialNumber') is-invalid @enderror"
                                        wire:model.live ="materialNumber">
                                        <option value="" selected>Pilih Part</option>
                                        @foreach ($nonAopParts as $part)
                                            <option value="{{ $part->part_no }}">
                                                {{ $part->part_no }} | {{ $part->nm_part }}
                                            </option>
                                        @endforeach
                                    </select>

                                    @error('materialNumber')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Harga</label>
                                    <input type="number" class="form-control @error('price') is-invalid @enderror"
                                        wire:model.live="price">

                                    @error('price')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">QTY</label>
                                    <input type="number" class="form-control @error('qty') is-invalid @enderror"
                                        wire:model.live="qty">

                                    @error('qty')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Total (Harga * Qty)</label>
                                    <input type="number" class="form-control" wire:model.live="total" disabled>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Total Nota Fisik</label>
                                    <input type="number" class="form-control @error('totalFisik') is-invalid @enderror"
                                        wire:model.live="totalFisik">

                                    @error('totalFisik')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Discount</label>
                                    <input type="number" class="form-control" wire:model.live="extraPlafonDiscount"
                                        disabled>
                                </div>
                                <div class="col-12 mb-3 d-grid">
                                    <button type="submit" class="btn btn-success">
                                        <span wire:loading.remove wire:target="addItem">Tambah Item</span>
                                        <span wire:loading wire:target="addItem">Loading...</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <div class="col-12 mb-3">
            <div class="card">
                <div class="card-header">
                    Detail Item
                </div>
                <div class="card-body">
                    @if ($details->isEmpty())
                        <div class="table-responsive" wire:loading.class="d-none" wire:target="addItem, destroyItem">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>No Part | Nama Part</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-center">No Data</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="table-responsive" wire:loading.class="d-none" wire:target="addItem, destroyItem">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>No Part | Nama Part</th>
                                        <th>Qty</th>
                                        <th>Harga (Rp)</th>
                                        <th>Discount (Rp)</th>
                                        <th>Amount (Rp)</th>
                                        @if ($header->flag_selesai == 'N')
                                            <th></th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $grandTotal = 0;
                                    @endphp
                                    @foreach ($details as $item)
                                        @php
                                            $grandTotal += $item->amount;
                                        @endphp
                                        <tr>
                                            <td>{{ $item->materialNumber }}</td>
                                            <td>{{ $item->qty }}</td>
                                            <td>{{ number_format($item->price, 0, ',', '.') }}</td>
                                            <td>{{ number_format($item->extraPlafonDiscount, 0, ',', '.') }}</td>
                                            <td>{{ number_format($item->amount, 0, ',', '.') }}</td>

                                            @if ($header->flag_selesai == 'N')
                                                <td>
                                                    <button class="btn btn-danger btn-sm"
                                                        wire:click="destroyItem({{ $item->id }})"
                                                        wire:confirm="Yakin ingin hapus item?">
                                                        Hapus
                                                    </button>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <th colspan="4">Grand Total</th>
                                        <td>{{ number_format($grandTotal, 0, ',', '.') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
