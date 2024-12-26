<div>
    <x-loading :target="$target" />

    <div class="card">
        <div class="card-header">
            Create invoice
        </div>
        <div class="card-body">
            <form wire:submit="save">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nota</label>
                        <input type="text" class="form-control" disabled placeholder="Pilih supplier terlebih dahulu."
                            value="{{ $invoiceGenerated ? $invoiceGenerated : 'Pilih supplier terlebih dahulu' }}">
                        <input type="hidden" value="{{ $invoiceGenerated }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Supplier</label>
                        <select class="form-select @error('supplier') is-invalid @enderror"
                            wire:model.change="supplier">
                            <option value="" selected>Pilih Supplier</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->supplierCode }}">{{ $supplier->supplierName }}</option>
                            @endforeach
                        </select>

                        @error('supplier')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tanggal Nota</label>
                        <input type="date" class="form-control @error('billingDocumentDate') is-invalid @enderror"
                            wire:model.change="billingDocumentDate">

                        @error('billingDocumentDate')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Customer To</label>
                        <select class="form-select @error('customerTo') is-invalid @enderror"
                            wire:model.change="customerTo">
                            <option value="" selected>Pilih Customer</option>
                            <option value="KCP01001">KCP01001</option>
                            <option value="KCP02001">KCP02001</option>
                        </select>

                        @error('customerTo')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">TOP (Hari)</label>
                        <input type="number" class="form-control @error('top') is-invalid @enderror"
                            wire:model.live="top">

                        @error('top')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Faktur Pajak</label>
                        <input type="text" class="form-control @error('fakturPajak') is-invalid @enderror"
                            wire:model.live="fakturPajak">

                        @error('fakturPajak')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nota Fisik</label>
                        <input type="text" class="form-control @error('notaFisik') is-invalid @enderror"
                            wire:model.live="notaFisik">

                        @error('notaFisik')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    <div class="col-12 mb-3 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            Simpan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
