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
                            <th>No</th>
                            <th>Delivery Note</th>
                            <th>Kode Gudang</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $no = 0;
                        @endphp

                        @forelse ($items as $item)
                            <tr>
                                <td>{{ $no++ }}</td>
                                <td class="text-nowrap">{{ $item->delivery_note }}</td>
                                <td>{{ $item->kd_gudang_aop }}</td>
                                <td>
                                    <a href="" class="btn btn-primary">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">No Data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
