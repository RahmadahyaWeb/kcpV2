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
            margin-top: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Helvetica, sans-serif;
            font-size: 10px;
        }

        h4 {
            margin: 0;
        }

        .w-full {
            width: 50%;
        }

        .w-half {
            width: 50%;
        }

        table .gg {
            border-collapse: collapse;
            border: 2px solid black;
        }

        td {
            padding: 0.5rem;
        }

        small {
            font-size: 8px;
            font-weight: bold;
        }

        .page-break {
            page-break-before: always;
            margin-top: 10px !important;
        }
    </style>
</head>

<body>

    <div style="margin-top: 10px">
        <table width="100%">
            @for ($i = 0; $i < count($labels); $i += 2)
                @if ($i % 8 == 0 && $i != 0)
                    <div class="page-break"></div>
                @endif
                <tr>
                    @for ($j = 0; $j < 2; $j++)
                        @if (isset($labels[$i + $j]))
                            <td width="50%" valign="top" style="padding: 5px">
                                <table width="100%" class="gg">
                                    <tr>
                                        <td valign="top">
                                            <h4 style="font-size: 11px">PT. Kumala Central Partindo</h4>
                                            <small>Jl. Sutoyo S. No. 144 Banjarmasin</small> <br>
                                            <small>Hp. 0811 517 1595, 0812 5156 2768</small> <br>
                                            <small>Telp. 0511-4416579, 4417127</small><br>
                                            <small>Fax. 3364674</small>
                                        </td>
                                        <td valign="top" style="text-align: right;">
                                            <h4 style="font-size: 11px">
                                                KCP/{{ $labels[$i + $j]['kd_outlet'] }}/{{ $labels[$i + $j]['nops'] }}
                                            </h4>
                                            <small>Tgl. Packingsheet : {{ $labels[$i + $j]['tanggal_ps'] }}</small>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td colspan="2">
                                            <div
                                                style="text-align: center; font-size: 20px; font-weight: bold; padding-top: 15px; padding-bottom: 30px">
                                                {{ $labels[$i + $j]['nama_outlet'] }}
                                                ({{ $labels[$i + $j]['kd_outlet'] }})
                                                <br>
                                                KCP/{{ $labels[$i + $j]['kd_outlet'] }}/{{ $labels[$i + $j]['no_dus'] }}
                                            </div>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td colspan="2" style="font-weight: bold">
                                            {{ $labels[$i + $j]['alamat'] }}

                                            <br>

                                            {{ $labels[$i + $j]['kabupaten'] }}, {{ $labels[$i + $j]['provinsi'] }}
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        @else
                            <td width="50%"></td>
                        @endif
                    @endfor
                </tr>
            @endfor
        </table>
    </div>

</body>

</html>
