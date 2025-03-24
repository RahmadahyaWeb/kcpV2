<!doctype html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Cetak SO</title>

    <style>
        * {
            /* margin: 5px; */
        }

        body {
            font-family: Helvetica, sans-serif;
            font-size: 10px;
        }

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
            border-collapse: collapse;
            border-spacing: 0;
        }

        table.products th {
            color: black;
            border: 1px solid black;
            padding: 0.5rem;
        }

        table tr.items td {
            padding: 0.5rem;
            border: 1px solid black;
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
    <hr>

    <table class="w-full">
        <tr>
            <td class="w-half" valign="top">
                <h3>PT. Kumala Central Partindo</h3>

                <b><small>Jl. Sutoyo S. No. 144 Banjarmasin</small> <br>
                    <small>Hp. 0811 517 1595, 0812 5156 2768</small> <br>
                    <small>Telp. 0511-4416579, 4417127</small><br>
                    <small>Fax. 3364674</small></b>
            </td>

            <td class="w-hal" valign="top">
                <h3>
                    SALES ORDER / SO

                    <br>

                    {{ $nama_gudang }} ({{ $kode_gudang }})
                </h3>

                <small>
                    <b>{{ $data_outlet->nm_outlet }} ({{ $data_outlet->kd_outlet }})</b>

                    <br>

                    <b>{{ $data_outlet->almt_outlet }}</b>

                    <br>

                    <b>{{ $data_kabupaten }}, {{ $data_provinsi }}</b>
                </small>
            </td>
        </tr>
    </table>

    <table class="w-full">
        <tr>
            <td class="w-half">
                <table>
                    <tr>
                        <td style="width: 50px">No. SO</td>
                        <td style="width: 5px">:</td>
                        <td style="width: 150px">
                            KCP/{{ $header->area_so }}/{{ str_replace('-', '/', $header->noso) }}
                        </td>
                    </tr>
                    <tr>
                        <td>Tanggal</td>
                        <td>:</td>
                        <td>{{ date('d-m-Y h:i:s', strtotime($header->flag_selesai_date)) }}</td>
                    </tr>
                </table>
            </td>

            <td class="w-half" valign="bottom">

            </td>
        </tr>
    </table>

    <div class="margin-top">
        <table class="products">
            <tr>
                <th style="text-align:center;">No.</th>
                <th style="white-space: nowrap">Part No.</th>
                <th>Nama Barang</th>
                <th style="text-align:center;">Qty</th>
                <th style="text-align:center;">Qty Stock</th>
                <th style="text-align:center; width: 25px;">Check</th>
                <th style="text-align:center; white-space: nowrap;">Lokasi</th>
                <th style="text-align:center;">Ket.</th>
            </tr>

            @foreach ($details as $index => $detail)
                @php
                    $isFederal = in_array($detail->produk_part, $list_federal);

                    $data_stock = $kcpinformation
                        ->table('stock_part')
                        ->where('kd_gudang', $kode_gudang)
                        ->where('part_no', $detail->part_no)
                        ->first();

                    $keterangan_mutasi = $kcpinformation
                        ->table('trns_mutasi_header as header')
                        ->join('trns_mutasi_details as detail', 'detail.no_mutasi', '=', 'header.no_mutasi')
                        ->where('header.noso', $noso)
                        ->where('detail.part_no', $detail->part_no)
                        ->where('header.flag_batal', 'N')
                        ->get();

                    $text_mutasi = '';
                    if (count($keterangan_mutasi) == 1) {
                        $text_mutasi = 'Mutasi : ' . $keterangan_mutasi[0]->qty_spv;
                    }

                    $rak = '';
                    if ($header->kd_outlet == 'V2') {
                        $rak = 'Kons.Assa';
                    } elseif ($header->kd_outlet == 'NW') {
                        $rak = 'Kanvasan';
                    } else {
                        $data_rak = $kcpinformation
                            ->table('stock_part_rak')
                            ->where('id_stock_part', $data_stock->id)
                            ->where('qty', '>', 0)
                            ->get();

                        $tempQty = 0;
                        $tempQty = $detail->qty - $tempQty;
                        foreach ($data_rak as $value_rak) {
                            if ($value_rak->qty >= $tempQty) {
                                $rak = $rak . $value_rak->kd_rak;
                            } elseif ($value_rak->qty < $tempQty) {
                                $tempQty = $tempQty - $detail->qty;
                                if ($tempQty == 0) {
                                    $rak = $rak . $value_rak->kd_rak;
                                } else {
                                    $rak = $rak . $value_rak->kd_rak . '<br>';
                                }
                            }
                        }
                    }

                @endphp

                <tr class="items">
                    <td style="text-align:center;">{{ $index + 1 }}.</td>
                    <td
                        style="{{ $isFederal ? 'background-color: black; color: white;' : 'background-color: red; color: white;' }}">
                        {{ $detail->part_no }}
                    </td>
                    <td>{{ $detail->nm_part }}</td>
                    <td style="text-align:center;">{{ $detail->qty }}</td>
                    <td style="text-align:center;">{{ $data_stock->stock }}</td>
                    <td>
                        <table border="1">
                            <tr>
                                <td style="width:20px"></td>
                            </tr>
                        </table>
                    </td>
                    <td style="text-align:center;">
                        {{ $rak }}

                        <br>
                    </td>
                    <td>{{ $text_mutasi }}</td>
                </tr>
            @endforeach
        </table>
    </div>

    <div class="margin-top">
        <table class="w-full">
            <tr>
                <td class="w-half">
                    <table>
                        <tr>
                            <td style="width: 50px">Cetak Oleh</td>
                            <td style="width: 5px">:</td>
                            <td style="width: 150px">
                                {{ Auth::user()->name }}
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2"></td>
                            <td style="width: 150px">
                                {{ date('d-m-Y h:i:s') }}
                            </td>
                        </tr>
                    </table>
                </td>

                <td class="w-half" valign="bottom">

                </td>
            </tr>
        </table>
    </div>

    <div class="margin-top">
        <table class="w-full">
            <tr>
                <td colspan="7">
                    <table>
                        <tr>
                            <td style="text-align:center;width:130px">Picker</td>
                            <td style="text-align:center;width:20px"></td>
                            <td style="text-align:center;width:130px">Checker</td>
                            <td style="text-align:center;width:20px"></td>
                            <td style="text-align:center;width:130px"></td>
                            <td style="text-align:center;width:20px"></td>
                            <td style="text-align:center;width:130px">Yang Membuat,</td>
                        </tr>
                        <tr>
                            <td colspan="4"><br><br><br></td>
                        </tr>
                        <tr>
                            <td style="border-bottom: 1px solid black;"></td>
                            <td></td>
                            <td style="border-bottom: 1px solid black;"></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td style="border-bottom: 1px solid black;"></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    @if ($header->keterangan != '')
        <div class="margin-top">
            <p align="center">
                <font size="35">CATATAN : {{ $header->keterangan }}</font>
            </p>
        </div>
    @endif
</body>

</html>
