<div>
    @php
        use App\Livewire\DeliveryOrder\DetailDeliveryOrder;
    @endphp

    <x-alert />
    <x-loading :target="$target" />

    <div class="row g-2 mb-3">
        @if ($terima_lkh_status && $status == true && $header->terima_ar == 'N')
            <div class="col-md-3 d-grid">
                <button wire:click="terima_lkh('{{ $header->no_lkh }}')" class="btn btn-success"
                    wire:confirm="Yakin ingin terima LKH?">Terima LKH</button>
            </div>
        @endif
    </div>

    <div class="row gap-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    Detail LKH: <b>{{ $no_lkh }}</b>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="row mb-3">
                                <div class="col col-4 col-md-4">
                                    <div>No LKH</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>{{ $header->no_lkh }}</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col col-4 col-md-4">
                                    <div>Driver</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>{{ $header->driver }}</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col col-4 col-md-4">
                                    <div>Helper</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>{{ $header->helper }}</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col col-4 col-md-4">
                                    <div>Plat Mobil</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>{{ $header->plat_mobil }}</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col col-4 col-md-4">
                                    <div>Jam Berangkat</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>{{ $header->jam_berangkat }}</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col col-4 col-md-4">
                                    <div>Jam Balik</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>{{ $header->jam_balik }}</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col col-4 col-md-4">
                                    <div>KM Saat Berangkat</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>{{ $header->km_berangkat }}</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col col-4 col-md-4">
                                    <div>KM Saat Balik</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>{{ $header->km_balik }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if ($ready_to_sent && $status == false)
                        <div class="row">
                            <form wire:submit="sendToBosnet" wire:confirm="Yakin ingin kirim data ke Bosnet?">
                                <div class="col d-grid">
                                    <hr>
                                    <button type="submit" class="btn btn-warning" wire:offline.attr="disabled">
                                        <span wire:loading.remove wire:target="sendToBosnet">Kirim ke Bosnet</span>
                                        <span wire:loading wire:target="sendToBosnet">Loading...</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    Detail LKH: <b>{{ $no_lkh }}</b>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Kode / Nama Toko</th>
                                    <th>No. Invoice</th>
                                    <th>No. PackingSheet</th>
                                    <th>Koli</th>
                                    <th>No. Urut</th>
                                    <th>Ekspedisi</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if ($items->isEmpty())
                                    <tr>
                                        <td class="text-center" colspan="5">No Data</td>
                                    </tr>
                                @else
                                    @foreach ($items as $item)
                                        <tr>
                                            <td>{{ $item->kd_outlet }} / {{ $item->nm_outlet }}</td>
                                            <td>
                                                {{ $item->noinv }}
                                            </td>
                                            <td>
                                                <span class="badge text-bg-dark">{{ $item->no_packingsheet }}</span>
                                            </td>
                                            <td>
                                                {{ $item->koli }}
                                            </td>
                                            <td>
                                                {{ $item->no_urut }}
                                            </td>
                                            <td>
                                                {{ $item->expedisi }}
                                            </td>
                                            <td>
                                                @isset(DetailDeliveryOrder::cek_status($item->noinv)->status_bosnet)
                                                    @if (DetailDeliveryOrder::cek_status($item->noinv)->status_bosnet == 'KCP')
                                                        <span class="badge text-bg-danger">
                                                            SO masih di KCP.
                                                        </span>
                                                    @else
                                                        <span class="badge text-bg-success">
                                                            Siap dikirim.
                                                        </span>
                                                    @endif
                                                @else
                                                    SO / INV belum terintegrasi dengan Bosnet.
                                                @endisset
                                            </td>
                                            <td class="text-center">
                                                @if ($item->terima_ar == 'N')
                                                    <span style="cursor: pointer;" class="badge text-bg-primary"
                                                        wire:click="terimaSJ('{{ $item->id }}')"
                                                        wire:confirm="Yakin ingin terima SJ?">
                                                        Terima SJ
                                                    </span>
                                                @else
                                                    <span class="badge text-bg-success">Berhasil terima SJ</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
