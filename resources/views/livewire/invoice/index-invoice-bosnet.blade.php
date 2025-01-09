<div>
    <x-alert />
    <x-loading :target="$target" />

    <div class="card">
        <!-- Card Header -->
        <div class="card-header">
            List Invoice Bosnet
        </div>

        <div class="card-body">
            @hasanyrole('super-user')
                <div class="d-flex gap-2 mb-3 py-4" style="overflow-x: auto; white-space: nowrap;">
                    <button type="button" class="btn btn btn-danger" wire:click="send_inv_to_bosnet"
                        wire:confirm="Yakin ingin kirim semua invoice ke BOSNET?">
                        Kirim Invoice
                    </button>
                    <button type="button" class="btn btn btn-danger" wire:click="export">
                        Export
                    </button>
                </div>
            @endhasanyrole

            <!-- Filter Section -->
            <div class="row mb-3 g-2">
                <div class="col-md-4">
                    <label class="form-label">Sales Order</label>
                    <input type="text" class="form-control" placeholder="Cari berdasarkan no sales order"
                        wire:model.live.debounce.1000ms="noso">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Invoice</label>
                    <input type="text" class="form-control" placeholder="Cari berdasarkan no invoice"
                        wire:model.live.debounce.1000ms="noinv">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status SO</label>
                    <select wire:model.change="status" class="form-select">
                        <option value="">Pilih Status</option>
                        <option value="KCP">KCP</option>
                        <option value="BOSNET">BOSNET</option>
                    </select>
                </div>
            </div>

            <!-- Data Table -->
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>No Invoice</th>
                            <th>No SO</th>
                            <th>Nominal Invoice + PPn (Rp)</th>
                            <th>Status SO</th>
                            <th>Sent to Bosnet</th>
                            <th>Status INV</th>
                            <th>Sent invoice to bosnet</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($invoices as $invoice)
                            <tr>
                                <td style="white-space: nowrap">
                                    <a href="{{ route('invoice.detail', $invoice->noinv) }}"
                                        wire:navigate>{{ $invoice->noinv }}</a>
                                </td>
                                <td style="white-space: nowrap">
                                    {{ $invoice->noso }}
                                </td>
                                <td>{{ number_format($invoice->amount_total, 0, ',', '.') }}</td>
                                <td>
                                    <span
                                        class="badge text-bg-{{ $invoice->status_bosnet == 'KCP' ? 'success' : 'warning' }}">
                                        {{ $invoice->status_bosnet }}
                                    </span>
                                </td>
                                <td>
                                    {{ $invoice->send_to_bosnet }}
                                </td>
                                <td>
                                    <span
                                        class="badge text-bg-{{ $invoice->status_invoice == 'KCP' ? 'success' : 'warning' }}">
                                        {{ $invoice->status_invoice }}
                                    </span>
                                </td>
                                <td>
                                    {{ $invoice->invoice_send_to_bosnet }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">No Data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer">
            {{ $invoices->links() }}
        </div>
    </div>

</div>
