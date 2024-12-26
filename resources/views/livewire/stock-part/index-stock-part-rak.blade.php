<div>
    <x-loading :target="$target" />

    <div class="card">
        <div class="card-header">
            Rak
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4 mb-3">
                    <label for="search" class="form-label">Part Number / Nama Part</label>
                    <input type="text" class="form-control" placeholder="Cari berdasarkan part number / nama part"
                        wire:model.live.debounce.150ms="search">
                </div>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>part number</th>
                            <th>nama part</th>
                            <th>no rak</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td style="white-space: nowrap">{{ $item->part_no }}</td>
                                <td style="white-space: nowrap">{{ $item->nm_part}}</td>
                                <td style="white-space: nowrap">{{ $item->kd_rak }}</td>
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
        <div class="card-footer">
            {{ $items->links() }}
        </div>
    </div>
</div>
