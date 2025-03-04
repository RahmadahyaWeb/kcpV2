<div>
    <x-alert />
    <x-loading :target="$target" />

    <div class="row gap-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    Upload File AOP
                </div>
                <div class="card-body">
                    <form wire:submit="save">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label for="surat_tagihan" class="form-label">
                                    Surat Tagihan
                                </label>
                                <input type="file" id="surat_tagihan"
                                    class="form-control @error('surat_tagihan') is-invalid @enderror"
                                    wire:model="surat_tagihan" wire:loading.class="is-invalid">
                                @error('surat_tagihan')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                                <div class="invalid-feedback" wire:loading wire:target="surat_tagihan">
                                    Uploading...
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="rekap_tagihan" class="form-label">
                                    Rekap Tagihan
                                </label>
                                <input type="file" id="rekap_tagihan"
                                    class="form-control @error('rekap_tagihan') is-invalid @enderror"
                                    wire:model="rekap_tagihan" wire:loading.class="is-invalid">
                                @error('rekap_tagihan')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                                <div class="invalid-feedback" wire:loading wire:target="rekap_tagihan">
                                    Uploading...
                                </div>
                            </div>
                            <div class="col-md-12 d-flex justify-content-end">
                                <button type="submit" class="btn btn-success"
                                    wire:target="rekap_tagihan, surat_tagihan">
                                    Upload
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @if (session()->has('sync_result'))
            <div class="col-12">
                @php
                    $result = session('sync_result');
                @endphp

                <div class="card mt-3">
                    <div class="card-header">
                        Hasil Sync Intransit
                    </div>
                    <div class="card-body">
                        <p><strong>Berhasil:</strong> {{ $result['success_count'] }}</p>
                        <p><strong>Gagal:</strong> {{ $result['failed_count'] }}</p>
                        <p><strong>Diskip:</strong> {{ $result['skipped_count'] }}</p>

                        @if (!empty($result['success_invoices']))
                            <h5>Invoice Berhasil</h5>
                            <ul>
                                @foreach ($result['success_invoices'] as $invoice)
                                    <li>
                                        {{ $invoice['invoice'] }} -
                                        {{ count($invoice['details']) }} item(s)
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if (!empty($result['failed_invoices']))
                            <h5>Invoice Gagal</h5>
                            <ul>
                                @foreach ($result['failed_invoices'] as $invoice)
                                    <li>
                                        {{ $invoice['invoice'] }} - Error: {{ $invoice['error'] }}
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if (!empty($result['skipped_invoices']))
                            <h5>Invoice Diskip</h5>
                            <ul>
                                @foreach ($result['skipped_invoices'] as $invoice)
                                    <li>
                                        {{ $invoice['invoice'] }}
                                        @if (!empty($invoice['invalid_items']))
                                            - Tidak valid: {{ implode(', ', $invoice['invalid_items']) }}
                                        @else
                                            - Data sudah ada atau tidak perlu di-sync.
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col">
                            Data AOP
                        </div>
                        <div class="col d-flex justify-content-end">
                            <button type="button" class="btn btn-danger" wire:click="sync_intransit">Sync
                                Intransit</button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Invoice AOP</label>
                            <input type="text" class="form-control" wire:model.live.debounce.1000ms="invoiceAop"
                                placeholder="Invoice AOP">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">SPB</label>
                            <input type="text" class="form-control" wire:model.live.debounce.1000ms="dn"
                                placeholder="SPB">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Tanggal Jatuh Tempo</label>
                            <input type="date" class="form-control" wire:model.change="tanggalJatuhTempo">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" wire:model.change="flag_po">
                                <option value="Y">BOSNET</option>
                                <option value="N">KCP</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Tanggal Invoice</label>
                            <input type="date" class="form-control" wire:model.change="billing_doc_date">
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Invoice AOP</th>
                                    <th>SPB</th>
                                    <th>Customer To</th>
                                    <th>Billing Document Date</th>
                                    <th>Tgl. Jatuh Tempo</th>
                                    <th>Harga (Rp)</th>
                                    <th>Add Discount (Rp)</th>
                                    <th>Amount (Rp)</th>
                                    <th>Cash Discount (Rp)</th>
                                    <th>Extra Plafon Discount (Rp)</th>
                                    <th>Net Sales (Rp)</th>
                                    <th>Tax (Rp)</th>
                                    <th>Grand Total (Rp)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($items as $item)
                                    <tr>
                                        <td style="white-space: nowrap">
                                            <a href="{{ route('purchase.aop.detail', $item->invoiceAop) }}"
                                                wire:navigate>
                                                {{ $item->invoiceAop }}
                                            </a>
                                        </td>
                                        <td>{{ $item->SPB }}</td>
                                        <td>{{ $item->customerTo }}</td>
                                        <td style="white-space: nowrap">
                                            {{ date('d-m-Y', strtotime($item->billingDocumentDate)) }}
                                        </td>
                                        <td style="white-space: nowrap">
                                            {{ date('d-m-Y', strtotime($item->tanggalJatuhTempo)) }}</td>
                                        <td>{{ number_format($item->price, 0, ',', '.') }}</td>
                                        <td>{{ number_format($item->addDiscount, 0, ',', '.') }}</td>
                                        <td>{{ number_format($item->amount, 0, ',', '.') }}</td>
                                        <td>{{ number_format($item->cashDiscount, 0, ',', '.') }}</td>
                                        <td>{{ number_format($item->extraPlafonDiscount, 0, ',', '.') }}
                                        </td>
                                        <td>{{ number_format($item->netSales, 0, ',', '.') }}</td>
                                        <td>{{ number_format($item->tax, 0, ',', '.') }}</td>
                                        <td>{{ number_format($item->grandTotal, 0, ',', '.') }}</td>
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
                    {{ $items->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
