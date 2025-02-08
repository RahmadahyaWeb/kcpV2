<div>
    <x-alert />
    <x-loading :target="$target" />

    <div class="card">
        <div class="card-header">
            Data Bonus Toko
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>NO INVOICE</th>
                            <th>KODE TOKO</th>
                            <th>NAMA TOKO</th>
                            <th>NOMINAL</th>
                            <th>NOMINAL DISC</th>
                            <th>NOMINAL TOTAL</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td class="text-nowrap">{{ $item->noinv }}</td>
                                <td class="text-nowrap">{{ $item->kd_outlet }}</td>
                                <td class="text-nowrap">{{ $item->nm_outlet }}</td>
                                <td class="text-nowrap">{{ number_format($item->amount, 0, ',', '.') }}</td>
                                <td class="text-nowrap">{{ number_format($item->amount_disc, 0, ',', '.') }}</td>
                                <td class="text-nowrap">{{ number_format($item->amount_total, 0, ',', '.') }}</td>
                                <td>
                                    @hasanyrole(['super-user', 'ar'])
                                        <button class="btn btn-sm btn-danger"
                                            wire:click="potong_piutang('{{ $item->noinv }}')"
                                            wire:confirm="Yakin ingin potong piutang?">
                                            Potong Piutang
                                        </button>
                                    @endhasanyrole
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
</div>
