<div>
    <x-alert />
    <x-loading :target="$target" />

    <div class="card">
        <div class="card-header">
            Data Intransit
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Delivery Note</th>
                            <th>Kode Gudang</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td class="text-nowrap">{{ $item->delivery_note }}</td>
                                <td>{{ $item->kd_gudang_aop }}</td>
                                <td>
                                    <a href="" class="btn btn-primary">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center">No Data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
