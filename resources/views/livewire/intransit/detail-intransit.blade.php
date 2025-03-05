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
                            <th></th>
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
                                <td>
                                    <input type="checkbox" wire:model="selectedItems" value="{{ $item->id }}">
                                </td>
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
                                <td colspan="7
                                8" class="text-center">No Data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Floating Button -->
    <div style="position: fixed; bottom: 40px; right: 40px; z-index: 1000;">
        <button class="btn btn-warning" wire:loading.attr="disabled" wire:target="save, selectedItems"
            wire:click="save" @disabled(count($selectedItems) < 1) wire:confirm="Yakin ingin kirim data ke Bosnet?">
            <span wire:loading.remove wire:target="save, selectedItems">Simpan</span>
            <span wire:loading wire:target="save, selectedItems">Loading...</span>
        </button>
    </div>
</div>
