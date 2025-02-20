<div>
    <x-alert />
    <x-loading :target="$target" />

    @if ($bonus->count() > 0 && $header->cetak == 0)
        <div class="alert alert-danger" role="alert">
            Toko memiliki {{ $bonus->count() }} bonus!
        </div>
    @endif

    <!-- Invoice Details Section -->
    <div class="row gap-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            Detail Invoice: <b>{{ $header->noinv }}</b>
                        </div>
                        <div class="col d-flex justify-content-end">
                            <!-- Print Button -->
                            @if ($header->cetak == 0)
                                {{-- <a href="{{ route('inv.print', $invoice) }}" target="_blank" class="btn btn-success"
                                    onclick="if (confirm('Apakah Anda yakin ingin mencetak invoice ini?')) { window.open(this.href, '_blank'); location.reload(); return false; } else { return false; }">
                                    <i class='bx bxs-printer me-1'></i> Print
                                </a> --}}

                                <button wire:click="print('{{ $header->noinv }}')" type="button"
                                    class="btn btn-success">
                                    <i class='bx bxs-printer me-1'></i> Print
                                </button>
                            @elseif($header->cetak >= 1)
                                @hasanyrole(['super-user'])
                                    <button wire:click="batal_print('{{ $header->noinv }}')" type="button"
                                        class="btn btn-danger">
                                        Batal Print
                                    </button>
                                @endhasanyrole
                            @endif
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Invoice Information Display -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            @foreach ([
        'No Invoice' => $header->noinv,
        'No Sales Order' => $header->noso,
        'Toko' => $header->nm_outlet,
        'Amount' => 'Rp ' . number_format($header->amount, 0, ',', '.'),
        'Amount Discount' => 'Rp ' . number_format($header->amount_disc, 0, ',', '.'),
        'Support Program' => 'Rp ' . number_format($nominalSuppProgram, 0, ',', '.'),
        'Amount Total' => 'Rp ' . number_format($header->amount_total, 0, ',', '.'),
    ] as $label => $value)
                                <div class="row mb-3">
                                    <div class="col-4 col-md-4">{{ $label }}</div>
                                    <div class="col-auto">:</div>
                                    <div class="col-auto">{{ $value }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Send to Bosnet Button (Only for KCP Status) -->
                    @if (isset($invoice_status->status_bosnet) && $invoice_status->status_bosnet == 'KCP')
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

        <!-- Add Support Program (Only for KCP Status) -->
        {{-- @if ($header->cetak == 0)
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        Form Tambah Support Program
                    </div>
                    <div class="card-body">
                        <form wire:submit="saveProgram">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Nama Program</label>
                                    <input type="text"
                                        class="form-control @error('search_program') is-invalid @enderror"
                                        wire:model.live="search_program" placeholder="Cari nama program">
                                </div>
                                <div class="col-md-12 mb-3">
                                    <select wire:model.change="nama_program"
                                        class="form-select @error('nama_program') is-invalid @enderror"
                                        wire:loading.attr="disabled" wire:target="nama_program, search_program">
                                        <option value="">Pilih Program</option>
                                        @foreach ($bonus as $item)
                                            <option value="{{ $item->no_program }}">{{ $item->nm_program }}</option>
                                        @endforeach
                                    </select>
                                    @error('nama_program')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Max Nominal Program</label>
                                    <input type="text" class="form-control" wire:model="nominal_program_display"
                                        disabled>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nominal Program</label>
                                    <input type="number"
                                        class="form-control @error('nominal_program') is-invalid @enderror"
                                        wire:model.live="nominal_program">
                                    @error('nominal_program')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-12 d-flex justify-content-end mt-3">
                                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled"
                                        wire:target="saveProgram,nama_program,search_program,nominal_program">
                                        <span wire:loading.remove wire:target="saveProgram">Tambah Program</span>
                                        <span wire:loading wire:target="saveProgram">Loading...</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif --}}

        <!-- Displaying Support Programs -->
        {{-- <div class="col-12">
            <div class="card">
                <div class="card-header">
                    Support Program
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nama Program</th>
                                    <th>Nominal Program (Rp)</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $supportProgram = 0; @endphp
                                @forelse ($programs as $program)
                                    @php $supportProgram += $program->nominal_program; @endphp
                                    <tr>
                                        <td>{{ $program->nm_program }}</td>
                                        <td>{{ number_format($program->nominal_program, 0, ',', '.') }}</td>
                                        <td>
                                            @if ($header->cetak == 0)
                                                <button wire:click="deleteProgram({{ $program->id }})"
                                                    class="btn btn-sm btn-danger">Hapus</button>
                                            @endif
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
        </div> --}}

        <!-- Material Details Section -->
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    Detail Material: <b>{{ $header->noinv }}</b>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>No Part</th>
                                    <th>Nama Part</th>
                                    <th>Qty</th>
                                    <th>Harga / Pcs (Rp)</th>
                                    <th>Disc (%)</th>
                                    <th>Nominal (Rp)</th>
                                    <th>Nominal Discount (Rp)</th>
                                    <th>Nominal Total (Rp)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($invoices as $invoice)
                                    @php
                                        $nominal_total += $invoice->nominal_total;
                                    @endphp
                                    <tr>
                                        <td class="text-nowrap">
                                            {{ $invoice->part_no }}
                                        </td>
                                        <td>{{ $invoice->nm_part }}</td>
                                        <td>{{ $invoice->qty }}</td>
                                        <td>{{ number_format($invoice->hrg_pcs, 0, ',', '.') }}</td>
                                        <td>{{ $invoice->disc }}</td>
                                        <td>{{ number_format($invoice->nominal, 0, ',', '.') }}</td>
                                        <td>{{ number_format($invoice->nominal_disc, 0, ',', '.') }}</td>
                                        <td>{{ number_format($invoice->nominal_total, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">No Data</td>
                                    </tr>
                                @endforelse
                                <tr>
                                    <td colspan="7" class="fw-bold">Total</td>
                                    <td>{{ number_format($nominal_total, 0, ',', '.') }}</td>
                                </tr>
                                {{-- <tr>
                                    <td colspan="7" class="fw-bold">Support Program</td>
                                    <td>{{ number_format($supportProgram, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td colspan="7" class="fw-bold">Grand Total</td>
                                    <td>{{ number_format($nominal_total - $supportProgram, 0, ',', '.') }}</td>
                                </tr> --}}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
