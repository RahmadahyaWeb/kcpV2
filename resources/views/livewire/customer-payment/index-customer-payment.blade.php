<div>
    <x-alert />
    <x-loading :target="$target" />

    <div class="card">
        <div class="card-header">
            Data Customer Payment From Bosnet
        </div>
        <div class="card-body">
            <div class="row mb-3 g-2">
                <div class="col-md-4">
                    <label class="form-label">No Piutang</label>
                    <input type="text" class="form-control" wire:model.live.debounce.1000ms="no_piutang"
                        placeholder="Cari berdasarkan no piutang">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status Customer Payment</label>
                    <select class="form-select" wire:model.change="status_customer_payment">
                        <option value="O">OPEN</option>
                        <option value="C">CLOSE</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>No Piutang</th>
                            <th>Kode Toko</th>
                            <th>Nama Toko</th>
                            <th>Nominal Potong (RP)</th>
                            <th>Pembayaran Via</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($customer_payment_header as $item)
                            <tr>
                                <td style="white-space: nowrap">
                                    <a href="{{ route('customer-payment.detail', $item->no_piutang) }}" wire:navigate>{{ $item->no_piutang }}
                                    </a>
                                </td>
                                <td>{{ $item->kd_outlet }}</td>
                                <td>{{ $item->nm_outlet }}</td>
                                <td>{{ number_format($item->nominal_potong, 0, ',', '.') }}</td>
                                <td>{{ $item->pembayaran_via }}</td>
                                <td style="white-space: nowrap">{{ $item->crea_date }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No Data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $customer_payment_header->links() }}
        </div>
    </div>
</div>
