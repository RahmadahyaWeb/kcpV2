<div>
    <x-loading :target="$target" />
    <x-alert />

    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header">
                    Aging
                </div>
                <div class="card-body">
                    <form wire:submit="show_data">
                        <div class="row gap-3">
                            <div class="col-12">
                                <label for="jenis_laporan" class="form-label">Jenis Laporan</label>
                                <select name="jenis_laporan" id="jenis_laporan"
                                    class="form-select @error('jenis_laporan') is-invalid @enderror"
                                    wire:model="jenis_laporan">
                                    <option value="">Pilih Jenis Laporan</option>
                                    <option value="aging">Aging</option>
                                </select>

                                @error('jenis_laporan')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label for="from_date" class="form-label">Dari Tanggal</label>
                                <input type="date" class="form-control @error('from_date') is-invalid @enderror"
                                    name="from_date" id="from_date" wire:model="from_date">

                                @error('from_date')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label for="to_date" class="form-label">Sampai Tanggal</label>
                                <input type="date" class="form-control @error('to_date') is-invalid @enderror"
                                    name="to_date" id="to_date" wire:model="to_date">

                                @error('to_date')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="col-12 d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">Tampilkan</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @if ($show)
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">
                                Preview Aging
                            </div>

                            <div class="col d-flex justify-content-end">
                                <button class="btn btn-sm btn-success">Export</button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th class="text-nowrap">KODE TOKO</th>
                                        <th class="text-nowrap">NAMA TOKO</th>
                                        <th class="text-nowrap">LIMIT KREDIT</th>
                                        <th class="text-nowrap">SISA LIMIT KREDIT</th>
                                        <th class="text-nowrap">BELUM OVERDUE</th>
                                        <th class="text-nowrap">INVOICE BELUM OVERDUE</th>
                                        <th class="text-nowrap">OVERDUE 1-7</th>
                                        <th class="text-nowrap">INVOICE OVERDUE 1-7</th>
                                        <th class="text-nowrap">OVERDUE 8-20</th>
                                        <th class="text-nowrap">INVOICE OVERDUE 8-20</th>
                                        <th class="text-nowrap">OVERDUE 21-50</th>
                                        <th class="text-nowrap">INVOICE OVERDUE 21-50</th>
                                        <th class="text-nowrap">OVERDUE > 50</th>
                                        <th class="text-nowrap">INVOICE OVERDUE > 50</th>
                                        <th class="text-nowrap">TOTAL PIUTANG</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($items as $kd_outlet => $data)
                                        <tr>
                                            <td>{{ $kd_outlet }}</td>
                                            <td>{{ $data['nm_outlet'] }}</td>
                                            <td>{{ number_format($data['limit_kredit'], 0, ',', '.') }}</td>
                                            <td>{{ number_format($data['sisa_limit_kredit'], 0, ',', '.') }}</td>
                                            <td>{{ number_format($data['not_overdue']['total_amount'], 0, ',', '.') }}
                                            </td>
                                            <td>
                                                {{ implode(', ', $data['not_overdue']['invoice_numbers']) }}
                                            </td>
                                            <td>{{ number_format($data['overdue_1_7']['total_amount'], 0, ',', '.') }}
                                            </td>
                                            <td>
                                                {{ implode(', ', $data['overdue_1_7']['invoice_numbers']) }}
                                            </td>
                                            <td>{{ number_format($data['overdue_8_20']['total_amount'], 0, ',', '.') }}
                                            </td>
                                            <td>
                                                {{ implode(', ', $data['overdue_8_20']['invoice_numbers']) }}
                                            </td>
                                            <td>{{ number_format($data['overdue_21_50']['total_amount'], 0, ',', '.') }}
                                            </td>
                                            <td>
                                                {{ implode(', ', $data['overdue_21_50']['invoice_numbers']) }}
                                            </td>
                                            <td>{{ number_format($data['overdue_over_50']['total_amount'], 0, ',', '.') }}
                                            </td>
                                            <td>
                                                {{ implode(', ', $data['overdue_over_50']['invoice_numbers']) }}
                                            </td>
                                            <td>{{ number_format($data['total_piutang'], 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                        {{ $items->links() }}
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
