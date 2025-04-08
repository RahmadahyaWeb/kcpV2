<div>
    <x-loading :target="$target" />

    <div class="card">
        <div class="card-header">
            Monitoring DKS
        </div>
        <div class="card-body">
            <div class="d-flex gap-2 mb-3 py-4" style="overflow-x: auto; white-space: nowrap;">
                <a href="{{ route('report-warehouse.dkd.rekap-daftar-kehadiran-driver') }}" class="btn btn-primary" wire:navigate>Rekap DKD</a>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <label for="fromDate" class="form-label">Dari tanggal</label>
                    <input id="fromDate" type="date" class="form-control @error('fromDate') is-invalid @enderror"
                        wire:model.change="fromDate" name="fromDate">
                    @error('fromDate')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label for="toDate" class="form-label">Sampai tanggal</label>
                    <input id="toDate" type="date" class="form-control @error('toDate') is-invalid @enderror"
                        wire:model.change="toDate" name="toDate">
                    @error('toDate')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label for="user_driver" class="form-label">Driver</label>
                    <select name="user_driver" id="user_driver" class="form-select" wire:model.change="user_driver">
                        <option value="" selected>Pilih Driver</option>
                        @foreach ($driver as $user)
                            <option value="{{ $user->username }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="kd_toko" class="form-label">Nama Toko</label>
                    <select name="kd_toko" id="kd_toko" class="form-select" wire:model.change="kd_toko">
                        <option value="" selected>Pilih Toko</option>
                        @foreach ($master_toko_kcpinformation as $toko)
                            <option value="{{ $toko->kd_outlet }}">{{ $toko->nm_outlet }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="table-responsive mb-6">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Driver</th>
                            <th>Tgl. Kunjungan</th>
                            <th>Kode Toko</th>
                            <th>Nama Toko</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Lama Kunjungan</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($items->isEmpty())
                            <tr>
                                <td colspan="9" class="text-center">No data</td>
                            </tr>
                        @else
                            @foreach ($items as $item)
                                @php
                                    $carbonDate = \Carbon\Carbon::parse($item->tgl_kunjungan);

                                    $dayInIndonesian = config('days.days.' . $carbonDate->format('l'));

                                    $formattedDate = $dayInIndonesian . ', ' . $carbonDate->format('d-m-Y');
                                @endphp
                                <tr>
                                    <td>{{ $item->user_sales }}</td>
                                    <td style="white-space: nowrap">{{ $formattedDate }}</td>
                                    <td>{{ $item->kd_toko }}</td>
                                    <td>{{ $item->nama_toko }}</td>
                                    <td>{{ date('H:i:s', strtotime($item->waktu_cek_in)) }}</td>
                                    @if (in_array($item->kd_toko, $absen_toko))
                                        <td>
                                            @if ($item->waktu_cek_out)
                                                {{ date('H:i:s', strtotime($item->waktu_cek_out)) }}
                                            @else
                                                Belum check out
                                            @endif
                                        </td>
                                        <td>-</td>
                                        <td>Absen Toko</td>
                                    @else
                                        <td>
                                            @if ($item->waktu_cek_out)
                                                {{ date('H:i:s', strtotime($item->waktu_cek_out)) }}
                                            @else
                                                Belum check out
                                            @endif
                                        </td>
                                        <td>
                                            @if ($item->lama_kunjungan != null)
                                                {{ $item->lama_kunjungan }} menit
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $item->keterangan }}</td>
                                    @endif
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer">
            {{ $items->links() }}
        </div>
    </div>
</div>
