<div>
    <x-alert />
    <x-loading :target="$target" />

    <div class="card">
        <div class="card-header">
            Data Retur Invoice
        </div>

        <div class="card-body">
            <div class="row mb-3 g-2">
                <div class="col-md-4">
                    <label class="form-label">No Retur</label>
                    <input type="text" class="form-control" wire:model.live.debounce.1000ms="no_retur"
                        placeholder="Cari berdasarkan no retur">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select wire:model.change="status" class="form-select">
                        <option value="" selected>Pilih Status</option>
                        <option value="N">KCP</option>
                        <option value="Y">BOSNET</option>
                        <option value="F">FAILED</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>No Retur</th>
                            <th>No Invoice</th>
                            <th>Kode / Nama Toko</th>
                            <th>Tanggal Cetak Pengajuan</th>
                            <th>Tanggal Approve</th>
                            <th>Tanggal Nota</th>
                            <th>Status</th>
                            <th>Sent Retur To Bosnet</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td style="white-space: nowrap">
                                    <a href="{{ route('retur.invoice.detail', ['no_retur' => $item->noretur]) }}">
                                        {{ $item->noretur }}
                                    </a>
                                </td>
                                <td style="white-space: nowrap">{{ $item->noinv }}</td>
                                <td>{{ $item->kd_outlet }} / {{ $item->nm_outlet }}</td>
                                <td>{{ $item->flag_cetak_date }}</td>
                                <td>{{ $item->flag_approve1_date }}</td>
                                <td>{{ $item->flag_nota_date }}</td>
                                <td>
                                    @if ($item->flag_bosnet == 'N')
                                        <span class="badge text-bg-success">KCP</span>
                                    @elseif($item->flag_bosnet == 'Y')
                                        <span class="badge text-bg-warning">BOSNET</span>
                                    @else
                                        <span class="badge text-bg-danger">FAILED</span>
                                    @endif
                                </td>
                                <td>{{ $item->retur_send_to_bosnet }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-center" colspan="8">No Data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
