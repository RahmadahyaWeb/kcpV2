<div>
    <x-loading :target="$target" />
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <b>Rekap Daftar Kehadiran Driver</b>
                </div>
                <div class="card-body">
                    <form wire:submit.prevent="export">
                        @csrf
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3">
                                <label for="fromDate" class="form-label">Dari tanggal</label>
                                <input id="fromDate" type="date"
                                    class="form-control @error('fromDate') is-invalid @enderror"
                                    wire:model.live="fromDate" name="fromDate">
                                @error('fromDate')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="toDate" class="form-label">Sampai tanggal</label>
                                <input id="toDate" type="date"
                                    class="form-control @error('toDate') is-invalid @enderror" wire:model.live="toDate"
                                    name="toDate">
                                @error('toDate')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="user_driver" class="form-label">Pilih Driver</label>
                                <select wire:model.change="user_driver" class="form-select">
                                    <option value="all">Semua driver</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->username }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="laporan" class="form-label">Pilih Laporan</label>
                                <select wire:model.change="laporan"
                                    class="form-select @error('laporan') is-invalid @enderror">
                                    <option value="">Pilih laporan</option>
                                    <option value="rekap_punishment">Rekap DKD</option>
                                </select>
                                @error('laporan')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-12 d-flex justify-content-end gap-2">
                                <button type="submit" class="btn btn-success" wire:loading.attr="disabled"
                                    wire:target="export">
                                    <span wire:loading.remove wire:target="export">Download</span>
                                    <span wire:loading wire:target="export">Downloading...</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
