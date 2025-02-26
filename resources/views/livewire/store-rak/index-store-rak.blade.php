<div>
    <x-alert-alpine />

    <x-loading :target="$target" />

    <div class="row gap-3">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    Buat Label
                </div>

                <div class="card-body">
                    <form wire:submit="create_label">
                        <div class="row gap-3">
                            <div class="col-12">
                                <label for="label" class="form-label">Nama Label</label>
                                <input type="text" class="form-control @error('label') is-invalid @enderror"
                                    wire:model="label">

                                @error('label')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-12 d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">Simpan</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    List Label
                </div>

                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select" wire:model.change="status">
                                <option value="Y">Selesai</option>
                                <option value="N">Belum Selesai</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nama Label</th>
                                    <th>Dibuat Oleh</th>
                                    <th>Tanggal Buat Label</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($items as $item)
                                    <tr>
                                        <td class="text-nowrap">{{ $item->label }}</td>
                                        <td>{{ $item->user_id }}</td>
                                        <td>{{ $item->created_at }}</td>
                                        <td>{{ $item->status == 'N' ? 'Belum Selesai' : 'Selesai' }}</td>
                                        <td>
                                            <button class="btn btn-sm btn-primary"
                                                wire:click="lihat_detail('{{ $item->id }}')">Lihat</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No Data</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
