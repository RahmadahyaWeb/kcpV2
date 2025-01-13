<div>

    <x-alert />
    <x-loading :target="$target" />

    <div class="card">
        <div class="card-header">
            Log Viewer
        </div>

        <div class="card-body">
            <div class="row mb-3 g-2">
                {{-- <div class="col-md-4">
                    <label class="form-label">No Retur</label>
                    <input type="text" class="form-control" wire:model.live.debounce.1000ms="no_retur"
                        placeholder="Cari berdasarkan no retur">
                </div> --}}
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select wire:model.change="status" class="form-select">
                        <option value="" selected>Pilih Status</option>
                        <option value="1">SUKSES</option>
                        <option value="0">GAGAL</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Request</th>
                            <th>Response</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td>{{ $item->date }}</td>
                                <td>{{ $item->request }}</td>
                                <td>{{ $item->response }}</td>
                                <td>{{ $item->status }}</td>
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
