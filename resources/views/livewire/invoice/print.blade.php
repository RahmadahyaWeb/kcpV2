<!doctype html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Invoice</title>

    <style>
        h4 {
            margin: 0;
        }

        .w-full {
            width: 100%;
        }

        .w-half {
            width: 50%;
        }

        .w-hehe {
            width: 50%;
        }

        .w-haha {
            width: 50%;
        }

        .margin-top {
            margin-top: 1.50rem;
        }

        .signature {
            margin-top: 50mm;
        }

        table {
            width: 100%;
            border-spacing: 0;
        }

        table.products {
            font-size: 0.875rem;
        }

        table.products th {
            color: black;
            padding: 0.5rem;
            border-top: 1px solid black;
            border-bottom: 1px solid black;
            border-spacing: 0;
        }

        table tr.items td {
            padding: 0.5rem;
        }

        .total {
            text-align: right;
            margin-top: 1rem;
            font-size: 0.875rem;
        }

        .page-break {
            page-break-before: always;
        }
    </style>
</head>

<body>

    @php
        use App\Http\Controllers\HelperController;
    @endphp

    <hr>

    {{-- HEADER --}}
    <table class="w-full">
        <tr>
            <td class="w-half" valign="top">
                <h3>PT. Kumala Central Partindo</h3>
                <small>Jl. Sutoyo S. No. 144 Banjarmasin</small> <br>
                <small>Hp. 0811 517 1595, 0812 5156 2768</small> <br>
                <small>Telp. 0511-4416579, 4417127</small><br>
                <small>Fax. 3364674</small>

                <table style="margin-top: 20px">
                    <tr>
                        <td style="width: 50px">No. Nota</td>
                        <td style="width: 5px">:</td>
                        <td style="width: 150px">
                            KCP/{{ $header->area_inv }}/{{ str_replace('-', '/', $header->noinv) }}
                        </td>
                    </tr>
                    <tr>
                        <td>Tanggal</td>
                        <td>:</td>
                        <td>{{ date('d-m-Y', strtotime($header->crea_date)) }}</td>
                    </tr>
                </table>
            </td>
            <td class="w-half" valign="bottom">
                <h3 style="font-size: 20px">INVOICE</h3>
                <table style="margin-top: 30px">
                    <tr>
                        <td valign="top" style="width: 50px" rowspan="2">Kepada</td>
                        <td valign="top" style="width: 5px" rowspan="2">:</td>
                        <td style="width: 200px">{{ $header->nm_outlet }} ({{ $header->kd_outlet }})</td>
                    </tr>
                    <tr>
                        <td>{{ $alamat_toko }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- CONTENT --}}
    <div class="margin-top">
        <table class="products">
            <tr>
                <td style="border-top: 1px solid black;border-bottom: 1px solid black;text-align:center;width:20px">
                    No.
                </td>
                <td style="border-top: 1px solid black;border-bottom: 1px solid black;width:120px">
                    Part No.
                </td>
                <td style="border-top: 1px solid black;border-bottom: 1px solid black;width:220px">
                    Nama Barang
                </td>
                <td style="border-top: 1px solid black;border-bottom: 1px solid black;text-align:center;width:30px">
                    Qty
                </td>
                <td style="border-top: 1px solid black;border-bottom: 1px solid black;text-align:center;width:50px">
                    Hrg/Pcs
                </td>
                <td style="border-top: 1px solid black;border-bottom: 1px solid black;text-align:center;width:60px">
                    Disc.
                </td>
                <td style="border-top: 1px solid black;border-bottom: 1px solid black;text-align:center;width:35px">
                    %
                </td>
                <td style="border-top: 1px solid black;border-bottom: 1px solid black;text-align:center;width:65px">
                    Jumlah
                </td>
            </tr>
            @php
                $no = 1;
                $totalQty = 0;
                $totalHarga = 0;
                $totalDisc = 0;
                $total = 0;
            @endphp
            @foreach ($invoices as $item)
                @php
                    $totalQty += $item->qty;
                    $totalHarga += $item->hrg_pcs;
                    $totalDisc += $item->nominal_disc;
                    $total += $item->nominal_total;
                @endphp
                <tr>
                    <td style="text-align:center;">
                        {{ $no++ }}
                    </td>
                    <td>
                        {{ $item->part_no }}
                    </td>
                    <td>
                        {{ str_replace($item->part_no, '', $item->nm_part) }}
                    </td>
                    <td style="text-align:center;">
                        {{ $item->qty }}
                    </td>
                    <td style="text-align:right;">
                        {{ number_format($item->hrg_pcs, 0, ',', '.') }}
                    </td>
                    <td style="text-align:right;">
                        {{ number_format($item->nominal_disc, 0, ',', '.') }}
                    </td>
                    <td style="text-align:center;">
                        {{ $item->disc }}%
                    </td>
                    <td style="text-align:right;">
                        {{ number_format($item->nominal_total, 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach
            <tr>
                <td style="border-top: 1px solid black" colspan="3">

                </td>
                <td style="border-top: 1px solid black; text-align:center;">
                    {{ $totalQty }}
                </td>
                <td style="border-top: 1px solid black; text-align:right">
                    {{ number_format($totalHarga, 0, ',', '.') }}
                </td>
                <td style="border-top: 1px solid black; text-align:right">
                    {{ number_format($totalDisc, 0, ',', '.') }}
                </td>
                <td style="border-top: 1px solid black;"></td>
                <td style="border-top: 1px solid black; text-align:right">
                    {{ number_format($total, 0, ',', '.') }}
                </td>
            </tr>
        </table>
    </div>

    {{-- FOOTER --}}
    <div class="footer">
        {{-- SUPPORT PROGRAM --}}
        <table class="w-full" style="margin-top: 10px; font-size: 0.875rem;">
            <tr>
                <td class="w-hehe" valign="top">
                    <div>
                        <b>
                            Tanggal Jatuh Tempo :
                            {{ date('d-m-Y', strtotime($header->tgl_jth_tempo)) }}
                        </b>
                    </div>
                    <br>
                    <div>
                        <b>Terbilang</b>
                        <br>
                        @php
                            $terbilang = HelperController::convert($total);
                        @endphp
                        {{ $terbilang }}
                    </div>
                </td>
                <td class="w-haha" valign="top">
                    <table>
                        @php
                            $totalProgram = 0;
                        @endphp
                        @foreach ($suppProgram as $program)
                            @php
                                $totalProgram += $program->nominal_program;
                            @endphp
                            <tr>
                                <td style="text-align: left; width: 15rem">{{ $program->nm_program }}</td>
                                <td align="right" style="text-align: right;" valign="top">
                                    {{ number_format($program->nominal_program, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                        <tr>
                            <td align="right" style="text-align: left; font-weight: bold">Total Nilai Faktur</td>
                            <td align="right" style="text-align: right;">
                                <b>{{ number_format($total - $totalProgram, 0, ',', '.') }}</b>
                            </td>
                        </tr>
                        <tr>
                            <td align="right" style="text-align: left;">DPP</td>
                            <td align="right" style="text-align: right;">
                                {{ number_format(($total - $totalProgram) / config('tax.ppn_factor'), 0, ',', '.') }}
                            </td>
                        </tr>
                        <tr>
                            <td align="right" style="text-align: left;">PPN</td>
                            <td align="right" style="text-align: right;">
                                {{ number_format((($total - $totalProgram) / config('tax.ppn_factor')) * config('tax.ppn_percentage'), 0, ',', '.') }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        @php
            $maxItem = 15;
            $countItem = count($invoices);
            $calculate = $maxItem - $countItem;

        @endphp

        @for ($i = 0; $i < $calculate; $i++)
            </br>
        @endfor

        {{-- SIGNATURE --}}
        <table class="w-full">
            <tr>
                <td style="text-align:center;width:130px">Penjualan</td>
                <td style="text-align:center;width:20px"></td>
                <td style="text-align:center;width:130px"></td>
                <td style="text-align:center;width:20px"></td>
                <td style="text-align:center;width:130px"></td>
                <td style="text-align:center;width:20px"></td>
                <td style="text-align:center;width:130px">AR</td>
            </tr>
            <tr>
                <td style="text-align:center;"></td>
                <td colspan="5"><br><br></td>
                <td style="text-align:center;">
                    <img src="{{ public_path('ttd_ar.png') }}" width="40" height="65">
                </td>
            </tr>
            <tr>
                <td style="border-bottom: 1px solid black;text-align:center;">{{ Auth::user()->name }}</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td style="border-bottom: 1px solid black;text-align:center;">
                    <b>Approve By System<br>Detty Herawati</b>
                </td>
            </tr>
        </table>

        <div style="margin-top: 10px;">
            - Pembayaran dianggap sah bila dicap LUNAS
            <br>
            - Pembayaran dengan giro/cheque dianggap sah bila telah diclearingkan
        </div>
        <hr style="margin-top: 30px">
    </div>
</body>

</html>
