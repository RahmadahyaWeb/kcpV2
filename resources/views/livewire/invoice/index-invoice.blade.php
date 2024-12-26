<div class="card">
    <!-- Card Header -->
    <div class="card-header">
        List Invoice
    </div>

    <div class="card-body">
        <div class="d-flex gap-2 mb-3 py-4" style="overflow-x: auto; white-space: nowrap;">
            <a href="{{ route('invoice.bosnet') }}" class="btn btn-primary" wire:navigate>Invoice Bosnet</a>
        </div>

        <!-- Data Table -->
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>No Invoice</th>
                        <th>No SO</th>
                        <th>Kode Toko</th>
                        <th>Nama Toko</th>
                        <th>Nominal Invoice</th>
                        <th>Nominal Invoice + PPn (Rp)</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $invoice)
                        <tr>
                            <td style="white-space: nowrap">
                                KCP/{{ $invoice->area_inv }}/{{ $invoice->noinv }}
                            </td>
                            <td style="white-space: nowrap">
                                KCP/{{ $invoice->area_inv }}/{{ $invoice->noso }}
                            </td>
                            <td>
                                {{ $invoice->kd_outlet }}
                            </td>
                            <td>
                                {{ $invoice->nm_outlet }}
                            </td>
                            <td class="table-warning">
                                {{ number_format($invoice->nominal_total_noppn, 0, ',', '.') }}
                            </td>
                            <td class="table-warning">
                                {{ number_format($invoice->nominal_total_ppn, 0, ',', '.') }}
                            </td>
                            <td style="white-space: nowrap">
                                <button wire:click="print('{{ $invoice->noinv }}')" type="button"
                                    class="btn btn-sm btn-primary">Print</button>
                                <button type="button" class="btn btn-sm btn-danger">Batal</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">No Data</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
