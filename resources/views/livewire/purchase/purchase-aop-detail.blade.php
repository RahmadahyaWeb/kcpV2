<div>
    <x-alert />
    <x-loading :target="$target" />

    @if (!$isAvailable)
        <div class="alert alert-danger">
            Ada Part Number yang tidak terdaftar.
        </div>
    @endif

    <div class="row">
        {{-- CARD DETAIL --}}
        <div class="col-12 mb-3">
            <div class="card">
                <div class="card-header">
                    Detail Invoice Astra Otoparts (AOP): <b>{{ $header->invoiceAop }}</b>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Field</th>
                                    <th>Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Invoice AOP</td>
                                    <td>{{ $header->invoiceAop }}</td>
                                </tr>
                                <tr>
                                    <td>Customer To</td>
                                    <td>{{ $header->customerTo }}</td>
                                </tr>
                                <tr>
                                    <td>No. SPB</td>
                                    <td>{{ $header->SPB }}</td>
                                </tr>
                                <tr>
                                    <td>Billing Document Date</td>
                                    <td>
                                        @if ($header->billingDocumentDate != null)
                                            {{ date('d-m-Y', strtotime($header->billingDocumentDate)) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td>Tgl. Cetak Faktur</td>
                                    <td>
                                        @if ($header->tanggalCetakFaktur != null)
                                            {{ date('d-m-Y', strtotime($header->tanggalCetakFaktur)) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td>Tgl. Jatuh Tempo</td>
                                    <td>
                                        @if ($header->tanggalJatuhTempo != null)
                                            {{ date('d-m-Y', strtotime($header->tanggalJatuhTempo)) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td>Faktur Pajak</td>
                                    <td>
                                        <div class="d-inline text-primary" style="cursor: pointer"
                                            wire:click="openModalFakturPajak">
                                            {{ empty($header->fakturPajak) ? 'Belum ada' : $header->fakturPajak }}
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Field</th>
                                    <th>Upload</th>
                                    <th>Input</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Harga</td>
                                    <td>Rp {{ number_format($totalAmount, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($totalAmount, 0, ',', '.') }}</td>
                                    <td>-</td>
                                </tr>
                                <tr>
                                    <td>Additional Discount</td>
                                    <td>Rp {{ number_format($addDiscount, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($addDiscount, 0, ',', '.') }}</td>
                                    <td>-</td>
                                </tr>
                                <tr
                                    class="{{ $extraPlafonDiscount_input != $extraPlafonDiscount_upload ? 'table-danger' : '' }}">
                                    <td>Extra Plafon Discount</td>
                                    <td>Rp {{ number_format($extraPlafonDiscount_upload, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($extraPlafonDiscount_input, 0, ',', '.') }}</td>
                                    <td>{{ $extraPlafonDiscount_input != $extraPlafonDiscount_upload ? 'Nominal tidak sama' : '-' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>Cash Discount</td>
                                    <td>Rp {{ number_format($cashDiscount, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($cashDiscount, 0, ',', '.') }}</td>
                                    <td>-</td>
                                </tr>
                                <tr class="{{ $netSales_input != $netSales_upload ? 'table-danger' : '' }}">
                                    <td>Net Sales</td>
                                    <td>Rp {{ number_format($netSales_upload, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($netSales_input, 0, ',', '.') }}</td>
                                    <td>{{ $netSales_input != $netSales_upload ? 'Nominal tidak sama' : '-' }}</td>
                                </tr>
                                <tr class="{{ $tax_input != $tax_upload ? 'table-danger' : '' }}">
                                    <td>Tax</td>
                                    <td>Rp {{ number_format($tax_upload, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($tax_input, 0, ',', '.') }}</td>
                                    <td>{{ $tax_input != $tax_upload ? 'Nominal tidak sama' : '-' }}</td>
                                </tr>
                                <tr class="{{ $grandTotal_input != $grandTotal_upload ? 'table-danger' : '' }}">
                                    <td>Grand Total</td>
                                    <td>Rp {{ number_format($grandTotal_upload, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($grandTotal_input, 0, ',', '.') }}</td>
                                    <td>{{ $grandTotal_input != $grandTotal_upload ? 'Nominal tidak sama' : '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    @if ($header->flag_final == 'N' && $isAvailable)
                        <div class="row">
                            <form wire:submit="updateFlag({{ $header->invoiceAop }})"
                                wire:confirm="Yakin ingin update flag?">
                                <div class="col d-grid">
                                    <hr>
                                    <button type="submit" class="btn btn-success">Selesai</button>
                                </div>
                            </form>
                        </div>
                    @elseif($header->flag_final == 'Y' && $header->flag_po == 'N')
                        <div class="row gap-2">
                            <div class="col">
                                <hr>
                            </div>
                            <form wire:submit="sendToBosnet" wire:confirm="Yakin ingin kirim data ke Bosnet?">
                                <div class="col d-grid">
                                    <button type="submit" class="btn btn-warning" wire:offline="disabled">
                                        Kirim ke Bosnet
                                    </button>
                                </div>
                            </form>

                            <form wire:submit="updateFlag({{ $header->invoiceAop }})"
                                wire:confirm="Yakin ingin update flag?">
                                <div class="col d-grid">
                                    <button type="submit" class="btn btn-danger">
                                        Batal
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- CARD EXTRA PLAFON DISCOUNT  --}}
        <div class="col-12 mb-3">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            Extra Plafon Discount (Disc Program)
                        </div>
                        <div class="col d-flex justify-content-end">
                            <button class="btn btn-primary" wire:click="openModalProgram">
                                Tambah Program
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Keterangan</th>
                                    <th>Discount (Rp)</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($programAop as $item)
                                    <tr>
                                        <td>{{ $item->keteranganProgram }}</td>
                                        <td>{{ number_format($item->potonganProgram, 0, ',', '.') }}</td>
                                        <td>
                                            <button class="btn btn-danger btn-sm"
                                                wire:click="destroyProgram({{ $item->id }})">
                                                Hapus
                                            </button>
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
                </div>
            </div>
        </div>

        {{-- CARD DETAIL PART --}}
        <div class="col-12 mb-3">
            <div class="card">
                <div class="card-header">
                    Detail Material Astra Otoparts (AOP): <b>{{ $header->invoiceAop }}</b>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table mb-3">
                            <thead>
                                <tr>
                                    <th>Total Qty</th>
                                    <th>Total Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><b>{{ $totalQty }}</b></td>
                                    <td><b>Rp {{ number_format($totalAmount, 0, ',', '.') }}</b></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Material Number</th>
                                    <th>Material Name</th>
                                    <th>Qty</th>
                                    <th>Amount</th>
                                    <th>Edit Amount</th>
                                    <th>Edit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $no = 1;
                                @endphp

                                @foreach ($details as $item)
                                    <tr>
                                        <td>{{ $no++ }}</td>
                                        <td class="text-nowrap">{{ $item['materialNumber'] }}</td>
                                        <td>{{ $item['nm_part'] }}</td>
                                        <td>{{ $item['qty'] }}</td>
                                        <td class="text-nowrap">Rp {{ number_format($item['amount'], 0, ',', '.') }}</td>
                                        <td>
                                            <input type="number" wire:model="details.{{ $item['materialNumber'] }}.amount"
                                                   class="form-control" min="0">
                                        </td>
                                        <td>
                                            <!-- Action button to save changes or other functionality -->
                                            <button wire:click="saveChanges('{{ $item['materialNumber'] }}')"
                                                class="btn btn-primary btn-sm">
                                                Save
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- MODAL EDIT FAKTUR PAJAK --}}
        <div wire:ignore.self class="modal fade" id="editFakturPajakModal" tabindex="-1"
            aria-labelledby="editFakturPajakModalLabel" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="editFakturPajakModalLabel">
                            Edit Faktur Pajak
                        </h1>
                    </div>
                    <div class="modal-body">
                        <form wire:submit="saveFakturPajak">
                            <label for="fakturPajak" class="form-label">Faktur Pajak</label>
                            <input type="text" class="form-control" wire:model="fakturPajak">
                            <div class="d-flex justify-content-end mt-2 gap-2">
                                <button type="button" class="btn btn-danger"
                                    wire:click="closeModalFakturPajak">Batal</button>
                                <button type="submit" class="btn btn-primary">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- MODAL Tambah Program --}}
        <div wire:ignore.self class="modal fade" id="createProgramModal" tabindex="-1"
            aria-labelledby="createProgramModalLabel" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="createProgramModalLabel">Tambah Extra Plafon Discount</h1>
                    </div>
                    <div class="modal-body">
                        <form wire:submit="saveProgram">
                            <div class="row gap-3">
                                <div class="col-12">
                                    <label for="potonganProgram" class="form-label">Potongan Harga</label>
                                    <input type="number"
                                        class="form-control @error('potonganProgram') is-invalid @enderror"
                                        wire:model="potonganProgram">
                                    @error('potonganProgram')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label for="keteranganProgram" class="form-label">Keterangan Program</label>
                                    <input type="text"
                                        class="form-control @error('keteranganProgram') is-invalid @enderror"
                                        wire:model="keteranganProgram">
                                    @error('keteranganProgram')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                                <div class="col-12 d-flex justify-content-end mt-2 gap-2">
                                    <button type="button" class="btn btn-danger"
                                        wire:click="closeModalProgram">Batal</button>
                                    <button type="submit" class="btn btn-primary">Simpan</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
