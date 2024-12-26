<div>
    <x-alert />
    <x-loading :target="$target" />

    <div class="card">
        <div class="card-header">
            <b>Data Non AOP</b>
        </div>

        <div class="card-body">
            <div class="d-flex gap-2 mb-3 py-4" style="overflow-x: auto; white-space: nowrap;">
                <a href="{{ route('purchase.non.create') }}" class="btn btn-primary" wire:navigate>Create Invoice</a>
            </div>

            <div class="row mb-3">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Invoice Non</label>
                    <input type="text" class="form-control" wire:model.live.debounce.1000ms="invoiceNon"
                        placeholder="Invoice Non">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Status</label>
                    <select wire:model.change="status" class="form-select">
                        <option item="" selected>Pilih Status</option>
                        <option item="KCP">KCP</option>
                        <option item="BOSNET">BOSNET</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Invoice Non AOP</th>
                            <th>Customer To</th>
                            <th>Supplier</th>
                            <th>Total Harga</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td style="white-space: nowrap">{{ $item->invoiceNon }}</td>
                                <td>{{ $item->customerTo }}</td>
                                <td>{{ $item->supplierCode }}</td>
                                <td>{{ number_format($item->price, 0, ',', '.') }}</td>
                                <td>{{ number_format($item->amount, 0, ',', '.') }}</td>
                                <td>
                                    @if ($item->flag_selesai == 'Y' && $item->status == 'KCP')
                                        <span class="badge text-bg-success">Siap dikirim</span>
                                    @elseif ($item->flag_selesai == 'Y' && $item->status == 'BOSNET')
                                        <span class="badge text-bg-success">Berhasil dikirim pada
                                            {{ date('d-m-Y H:i:s', strtotime($item->sendToBosnet)) }}</span>
                                    @else
                                        <span class="badge text-bg-danger">Belum siap dikirim</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{route('purchase.non.detail', $item->invoiceNon)}}" class="btn btn-sm btn-primary" wire:navigate>Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-center" colspan="7">No Data</td>
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
