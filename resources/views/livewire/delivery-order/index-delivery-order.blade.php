<div>
    <x-alert />
    <x-loading :target="$target" />

    <div class="card">
        <!-- Card Header with Synchronization Button -->
        <div class="card-header">
            <b>Data Delivery Order</b>
        </div>

        <!-- Card Body with Filters -->
        <div class="card-body">
            <!-- Filters Section -->
            <div class="row mb-3 g-2">
                <!-- No LKH Filter -->
                <div class="col-md-4">
                    <label class="form-label">No LKH</label>
                    <input type="text" class="form-control" wire:model.live.debounce.1000ms="no_lkh"
                        placeholder="Cari berdasarkan no lkh" wire:loading.attr="disabled">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select class="form-select" wire:model.change="status">
                        <option value="KCP">KCP</option>
                        <option value="BOSNET">BOSNET</option>
                    </select>
                </div>
            </div>

            <!-- Table with Delivery Orders -->
            <div class="table-responsive">
                @if ($status == 'KCP')
                    <table class="table">
                        <thead>
                            <tr>
                                <th>No LKH</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Check if there are no items -->
                            @if ($items->isEmpty())
                                <tr>
                                    <td colspan="2" class="text-center">No Data</td>
                                </tr>
                            @else
                                <!-- Loop through each item and display it -->
                                @foreach ($items as $item)
                                    <tr>
                                        <td style="white-space: nowrap">
                                            KCP/{{ $item->area_lkh }}/{{ $item->no_lkh }}
                                        </td>
                                        <td>
                                            <a href="{{ route('delivery-order.detail', $item->no_lkh) }}"
                                                class="btn btn-sm btn-primary" wire:navigate>Detail</a>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                @else
                    <table class="table">
                        <thead>
                            <tr>
                                <th>No LKH</th>
                                <th>No Invoice</th>
                                <th>Status</th>
                                <th>Sent to Bosnet</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $item)
                                <tr>
                                    <td style="white-space: nowrap">{{ $item->no_lkh }}</td>
                                    <td>{{ $item->noinv }}</td>
                                    <td>{{ $item->status_bosnet }}</td>
                                    <td>{{ $item->send_to_bosnet }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">No Data</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        <!-- Card Footer with Pagination -->
        <div class="card-footer">
            {{ $items->links() }}
        </div>
    </div>
</div>
