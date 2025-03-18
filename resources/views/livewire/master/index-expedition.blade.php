<div>
    <x-alert />
    <x-loading :target="$target" />

    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col">
                    Master Expedition
                </div>
                <div class="col d-flex justify-content-end">
                    <a href="{{ route('expedition.create') }}" class="btn btn-success" wire:navigate>Tambah</a>
                </div>
            </div>
        </div>

        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kode Expedition</th>
                        <th>Nama Expedition</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td>{{ $item->kd_expedition }}</td>
                            <td>{{ $item->nama_expedition }}</td>
                            <td>
                                <a href="{{ route('expedition.edit', $item->kd_expedition) }}" class="btn btn-sm btn-warning">Edit</a>
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

        <div class="card-footer">
            {{ $items->links() }}
        </div>
    </div>
</div>
