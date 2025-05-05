<div>
    <x-alert />
    <x-loading :target="$target" />

    <div class="row">
        <x-lss-navigation />

        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header">
                    Upload COGS
                </div>

                <div class="card-body">
                    <form action="{{ route('upload-cogs') }}" enctype="multipart/form-data" method="POST">
                        @csrf
                        <label for="file_cogs" class="form-label">File COGS</label>
                        <input type="file" class="form-control @error('file_cogs') is-invalid @enderror"
                            name="file_cogs" id="file_cogs">

                        @error('file_cogs')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                        <div class="d-flex justify-content-end gap-3">
                            {{-- <button type="button" wire:click="sync_frekuensi"
                                class="mt-3 btn btn-primary">Sync</button> --}}
                            <button type="submit" class="mt-3 btn btn-success">Upload</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col">
                            Data Upload COGS
                        </div>

                        <div class="col d-flex justify-content-end">
                            <button class="btn btn-danger" wire:click="inject"
                                wire:confirm="Yakin ingin inject data cogs?">Inject</button>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="part_no">Part Number</label>
                                <input type="search" class="form-control" wire:model.live="part_no">
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Part No</th>
                                        <th>QTY</th>
                                        <th>COGS</th>
                                        <th>Bulan</th>
                                        <th>tahun</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($items as $item)
                                        <tr>
                                            <td>{{ $item->part_no }}</td>
                                            <td>{{ $item->qty }}</td>
                                            <td>{{ number_format($item->cogs, 2, ',', '.') }}</td>
                                            <td>{{ $item->periode_bulan }}</td>
                                            <td>{{ $item->periode_tahun }}</td>
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
        </div>
    </div>
</div>
