<div>
    <x-alert />
    <x-loading :target="$target" />

    <div class="card">
        <div class="card-header">
            Details Intransit AOP dengan Surat Pengantar {{ $delivery_note }}
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Part Number</th>
                            <th>QTY</th>
                            <th>QTY Terima</th>
                            <th>Sisa</th>
                            <th>Rak</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $no = 1;
                        @endphp

                        @forelse ($items as $item)
                            <tr>
                                <td>{{ $no++ }}</td>
                                <td>{{ $item->part_no }}</td>
                                <td>{{ $item->qty }}</td>
                                <td>{{ $item->qty_terima }}</td>
                                <td>{{ $item->qty - $item->qty_terima }}</td>
                                <td>{{ $item->kd_rak }}</td>
                                <td>
                                    <a href="{{ route('intransit.update', $item->id) }}"
                                        class="btn btn-sm btn-warning">Edit</a>
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
