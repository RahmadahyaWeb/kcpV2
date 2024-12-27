<?php

namespace App\Exports;

use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class RekapSheet implements WithTitle, WithEvents, WithColumnFormatting
{
    protected $sales;
    protected $fromDate;
    protected $toDate;
    protected $items;

    protected $tokoAbsen = [
        '6B',
        '6C',
        '6D',
        '6F',
        '6H',
        'TX'
    ];

    public function __construct($sales, $fromDate, $toDate, $items)
    {
        $this->sales = $sales;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->items = $items;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $this->setHeader($sheet);
                $dates = $this->getDateRange();
                $this->populateData($sheet, $dates);
                $this->autoSizeColumns($sheet);

                // FREEZE PANE
                $event->sheet->getDelegate()->freezePane('D1');
            }
        ];
    }

    private function setHeader($sheet)
    {
        $sheet->mergeCells('A1:A2');
        $sheet->mergeCells('B1:B2');
        $sheet->mergeCells('C1:C2');
        $sheet->setCellValue('A1', 'Alur Kerja Penggunaan DKS');
        $sheet->setCellValue('B1', 'Pelanggaran');
        $sheet->setCellValue('C1', 'Punishment');

        // ALUR PENGGUNAAN DKS
        $sheet->mergeCells('A8:A9');

        $sheet->setCellValue('A3', 'Setiap masuk toko harus check in');
        $sheet->setCellValue('A4', 'Setiap keluar/pulang dari toko harus check in');
        $sheet->setCellValue('A5', 'Check in untuk toko yang pertama di kunjungi setiap hari maksimal jam 09.30');
        $sheet->setCellValue('A6', 'Durasi perjalanan dari toko ke toko berikutnya maksimal 40 menit');
        $sheet->setCellValue('A7', 'Durasi lama berkunjung di toko minimal 30 menit');
        $sheet->setCellValue('A8', 'Lama istirahat 1 jam 30 menit (selain hari jumat) harus memberikan "IST" di system');
        $sheet->setCellValue('A10', 'Lama istirahat 2 jam (khusus hari jumat) harus memberikan "IST" di system');
        $sheet->setCellValue('A11', 'Frekuensi Toko');

        // PELANGGARAN
        $sheet->mergeCells('B3:B4');

        $sheet->setCellValue('B3', 'Lupa check in atau check out');
        $sheet->setCellValue('B5', 'Check in pertama melebihi 09.30');
        $sheet->setCellValue('B6', 'Durasi perjalanan dari toko ke toko berikutnya');
        $sheet->setCellValue('B7', 'Lama berkunjung di toko tidak sampai 30 menit');
        $sheet->setCellValue('B8', 'Istirahat melebihi 1 jam 15 menit (selain hari jumat)');
        $sheet->setCellValue('B9', 'Istirahat melebihi 1 jam 45 menit (khusus hari jumat)');
        $sheet->setCellValue('B10', 'Tidak memberikan keterangan saat mau istirahat');
        $sheet->setCellValue('B11', 'Tidak mencapai minimal kunjungan toko');

        // PUNISHMENT
        $sheet->mergeCells('C3:C4');
        $sheet->mergeCells('C8:C9');

        $sheet->setCellValue('C3', 'Rp. 10.000 / kejadian');
        $sheet->setCellValue('C5', 'Rp. 25.000 / kejadian');
        $sheet->setCellValue('C6', 'Rp. 25.000 / kejadian');
        $sheet->setCellValue('C7', 'Rp. 15.000 / kejadian');
        $sheet->setCellValue('C8', 'Rp. 10.000 / kejadian');
        $sheet->setCellValue('C10', 'Rp. 5.000 / kejadian');
        $sheet->setCellValue('C11', 'Rp. 10.000 / kejadian');

        $styleArray = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'font' => ['bold' => true],
        ];

        $styleArrayAlurPenggunaanDks = [
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];

        $sheet->getDelegate()->getStyle('A1:C2')->applyFromArray($styleArray);
        $sheet->getDelegate()->getStyle('A3:C10')->applyFromArray($styleArrayAlurPenggunaanDks);
    }

    private function populateData($sheet, $dates)
    {
        $startColumn = 4;
        foreach ($this->sales as $user_sales) {
            $user_sales_upper = strtoupper($user_sales);

            $this->setSalesHeaders($sheet, $startColumn, $user_sales_upper);
            $this->fillData($sheet, $dates, $startColumn, $user_sales);
            $startColumn += 3;
        }
    }

    private function setSalesHeaders($sheet, $startColumn, $user_sales)
    {
        $endColumn = $startColumn + 2;
        $sheet->mergeCellsByColumnAndRow($startColumn, 1, $endColumn, 1);
        $sheet->setCellValueByColumnAndRow($startColumn, 1, $user_sales);
        $this->styleSalesHeader($sheet, $startColumn);

        $sheet->setCellValueByColumnAndRow($startColumn, 2, 'BANYAK');
        $sheet->setCellValueByColumnAndRow($startColumn + 1, 2, 'REV');
        $sheet->setCellValueByColumnAndRow($startColumn + 2, 2, 'BAYAR');
    }

    private function styleSalesHeader($sheet, $startColumn)
    {
        $sheet->getStyleByColumnAndRow($startColumn, 1)
            ->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        $sheet->getStyleByColumnAndRow($startColumn, 1)
            ->getFont()
            ->setBold(true);
    }

    private function fillData($sheet, $dates, $startColumn, $user_sales)
    {
        // Filter untuk mengambil elemen dengan kunci 'user_sales'
        $filtered = array_filter($this->items, function ($value, $key) use ($user_sales) {
            return $key === $user_sales;
        }, ARRAY_FILTER_USE_BOTH);

        $punishmentCekInPertama = count($this->punishmentCekInPertama($filtered, $user_sales));

        $punishmentCekInCekOut = count($this->punishmentCekInCekOut($filtered, $user_sales));

        $dataIst = $this->punishmentIstirahat($filtered, $user_sales);

        $punishment_istirahat = count($dataIst['punishment_istirahat']);
        $punishment_lupa_ist = count($dataIst['punishment_lupa_ist']);

        $punishmentFrekuensiToko = $this->punishmentFrekuensiToko($user_sales);

        $rowNumber = 3;

        $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($startColumn);
        $sheet->mergeCells($columnLetter . $rowNumber . ':' . $columnLetter . $rowNumber + 1);
        $nextColumnLetter2 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($startColumn + 1);
        $sheet->mergeCells($nextColumnLetter2 . $rowNumber . ':' . $nextColumnLetter2 . $rowNumber + 1);
        $nextColumnLetter3 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($startColumn + 2);
        $sheet->mergeCells($nextColumnLetter3 . $rowNumber . ':' . $nextColumnLetter3 . $rowNumber + 1);

        // BANYAK PUNISHMENT CEK IN CEK OUT
        $sheet->setCellValue($columnLetter . $rowNumber, str_replace('{row}', $rowNumber, $punishmentCekInCekOut));
        // BAYAR PUNISHMENT CEK IN CEK OUT
        $sheet->setCellValue($nextColumnLetter3 . $rowNumber, str_replace('{row}', $rowNumber, 10000 * $punishmentCekInCekOut));

        // BANYAK PUNISHMENT CEK IN PERTAMA
        $sheet->setCellValue($columnLetter . ($rowNumber + 2), str_replace('{row}', ($rowNumber + 2), $punishmentCekInPertama));
        // BAYAR PUNISHMENT CEK IN PERTAMA
        $sheet->setCellValue($nextColumnLetter3 . ($rowNumber + 2), str_replace('{row}', ($rowNumber) + 2, 25000 * $punishmentCekInPertama));

        // BANYAK PUNISHMENT DURASI LAMA PERJALANAN TOKO
        $sheet->setCellValue($columnLetter . ($rowNumber + 3), str_replace('{row}', ($rowNumber + 2), "=SUM({$user_sales}!K3:K6898)"));
        // BAYAR PUNISHMENT DURASI LAMA PERJALANAN TOKO
        $sheet->setCellValue($nextColumnLetter3 . ($rowNumber + 3), str_replace('{row}', ($rowNumber + 2), "=SUM({$user_sales}!K3:K6898) * 25000"));

        // BANYAK PUNISHMENT DURASI KUNJUNGAN TOKO
        $sheet->setCellValue($columnLetter . ($rowNumber + 4), str_replace('{row}', ($rowNumber + 2), "=SUM({$user_sales}!I3:I6898)"));
        // BAYAR PUNISHMENT DURASI KUNJUNGAN TOKO
        $sheet->setCellValue($nextColumnLetter3 . ($rowNumber + 4), str_replace('{row}', ($rowNumber + 2), "=SUM({$user_sales}!I3:I6898) * 25000"));

        // BANYAK PUNISHMENT ISTIRAHAT
        $sheet->setCellValue($columnLetter . ($rowNumber + 5), str_replace('{row}', ($rowNumber + 2), $punishment_istirahat));
        // BAYAR PUNISHMENT ISTIRAHAT
        $sheet->setCellValue($nextColumnLetter3 . ($rowNumber + 5), str_replace('{row}', ($rowNumber) + 2, 10000 * $punishment_istirahat));

        $sheet->mergeCells($columnLetter . ($rowNumber + 5) . ':' . $columnLetter . $rowNumber + 6);
        $sheet->mergeCells($nextColumnLetter2 . ($rowNumber + 5) . ':' . $nextColumnLetter2 . $rowNumber + 6);
        $sheet->mergeCells($nextColumnLetter3 . ($rowNumber + 5) . ':' . $nextColumnLetter3 . $rowNumber + 6);

        // BANYAK PUNISHMENT LUPA IST
        $sheet->setCellValue($columnLetter . ($rowNumber + 7), str_replace('{row}', ($rowNumber + 2), $punishment_lupa_ist));
        // BAYAR PUNISHMENT LUPA IST
        $sheet->setCellValue($nextColumnLetter3 . ($rowNumber + 7), str_replace('{row}', ($rowNumber) + 2, 5000 * $punishment_lupa_ist));

        // BANYAK PUNISHMENT FREKUENSI TOKO
        $sheet->setCellValue($columnLetter . ($rowNumber + 8), str_replace('{row}', ($rowNumber + 2), $punishmentFrekuensiToko));
        // BAYAR PUNISHMENT FREKUENSI TOKO
        $sheet->setCellValue($nextColumnLetter3 . ($rowNumber + 8), str_replace('{row}', ($rowNumber) + 2, 10000 * $punishmentFrekuensiToko));
    }

    public function punishmentFrekuensiToko($user_sales)
    {
        $master_toko = DB::connection('kcpinformation')
            ->table('mst_outlet')
            ->leftJoin('mst_areatoko', 'mst_areatoko.area_group', '=', 'mst_outlet.area_group_2w')
            ->leftJoin('mst_area', 'mst_area.kode_kab', '=', 'mst_outlet.kode_kab')
            ->leftJoin('mst_provinsi', 'mst_provinsi.kode_prp', '=', 'mst_outlet.kode_prp')
            ->where('mst_outlet.status', 'Y')
            ->where('mst_areatoko.user_sales', $user_sales)
            ->get();

        $punishment = 0;

        foreach ($master_toko as $key => $value) {
            $minimal_kunjungan = $value->frekuensi ?? 0;
            $kd_toko = $value->kd_outlet;

            if ($value->kd_outlet == 'TQ') {
                $realisasi_kunjungan_tq = DB::table('trans_dks')
                    ->where('user_sales', $user_sales)
                    ->whereBetween('tgl_kunjungan', [$this->fromDate, $this->toDate])
                    ->where('type', 'in')
                    ->whereIn('kd_toko', ['TQ', 'TQ2'])
                    ->count();

                $realisasi_kunjungan = (string) $realisasi_kunjungan_tq;
            } else {
                $realisasi_kunjungan_data = DB::table('trans_dks')
                    ->where('user_sales', $user_sales)
                    ->whereBetween('tgl_kunjungan', [$this->fromDate, $this->toDate])
                    ->where('type', 'in')
                    ->where('kd_toko', $kd_toko)
                    ->count();

                $realisasi_kunjungan = (string) $realisasi_kunjungan_data;
            }

            if ($minimal_kunjungan > $realisasi_kunjungan) {
                $punishment++;
            }
        }

        return $punishment;
    }


    public function punishmentIstirahat($filtered, $user_sales)
    {
        $punishment_istirahat = [];
        $punishment_lupa_ist_array = [];

        // Mengonversi Collection menjadi array
        $itemsArray = $filtered[$user_sales]->toArray();

        foreach ($itemsArray as $data) {
            // Durasi perjalanan dalam format HH:MM:SS
            $durasi_perjalanan = $data->durasi_perjalanan;

            $punishment_durasi_lama_perjalanan = 0;
            $punishment_lupa_ist = 0;

            if ($durasi_perjalanan != '00:00:00' && !empty($durasi_perjalanan)) {
                // Pisahkan durasi perjalanan menjadi jam, menit, dan detik
                $timeParts = explode(':', $durasi_perjalanan);

                // Pastikan ada 3 bagian (jam, menit, detik)
                if (count($timeParts) === 3) {
                    list($hours, $minutes, $seconds) = $timeParts;
                } else {
                    // Jika format tidak sesuai, set jam, menit, detik menjadi 0
                    $hours = $minutes = $seconds = 0;
                }

                // Menghitung durasi perjalanan dalam menit
                $lama_perjalanan_dalam_menit = ($hours * 60) + $minutes;

                // Durasi maksimal perjalanan (40 menit) dan waktu istirahat
                $max_durasi_lama_perjalanan = 40;

                // Cek apakah hari ini adalah hari Jumat
                $isFriday = Carbon::parse($data->tgl_kunjungan)->isFriday();
                $waktu_istirahat = $isFriday ? 120 : 90; // Istirahat 2 jam pada Jumat, selain Jumat 1 jam 30 menit

                // Durasi maksimal perjalanan ditambah waktu istirahat
                $max_durasi_lama_perjalanan_plus_waktu_istirahat = $waktu_istirahat + $max_durasi_lama_perjalanan;

                // Cek apakah ada keterangan 'ist' (untuk istirahat)
                if (strpos($data->keterangan, 'ist') !== false) {
                    // Jika durasi perjalanan lebih dari maksimal + waktu istirahat, beri punishment
                    $punishment_durasi_lama_perjalanan = ($lama_perjalanan_dalam_menit > $max_durasi_lama_perjalanan_plus_waktu_istirahat) ? 1 : 0;
                } else {
                    $punishment_lupa_ist = ($lama_perjalanan_dalam_menit > $max_durasi_lama_perjalanan_plus_waktu_istirahat) ? 1 : 0;
                }
            }

            // Jika ada punishment, pisahkan berdasarkan hari Jumat atau selain Jumat
            if ($punishment_durasi_lama_perjalanan == 1) {
                $punishment_istirahat[] = [
                    'user_sales' => $data->user_sales,
                    'kd_toko' => $data->kd_toko,
                    'tgl_kunjungan' => $data->tgl_kunjungan,
                    'durasi_perjalanan' => $data->durasi_perjalanan,
                    'punishment' => 'Durasi perjalanan melebihi batas waktu yang ditentukan',
                ];
            }

            if ($punishment_lupa_ist == 1) {
                $punishment_lupa_ist_array[] = [
                    'user_sales' => $data->user_sales,
                    'kd_toko' => $data->kd_toko,
                    'tgl_kunjungan' => $data->tgl_kunjungan,
                    'durasi_perjalanan' => $data->durasi_perjalanan,
                    'punishment' => 'Lupa tulis ist',
                ];
            }
        }

        // Kembalikan data punishment untuk hari Jumat dan selain Jumat
        return [
            'punishment_istirahat' => $punishment_istirahat,
            'punishment_lupa_ist'  => $punishment_lupa_ist_array,
        ];
    }

    public function punishmentCekInPertama($filtered, $user_sales)
    {
        $punishmentData = [];

        // Mengonversi Collection menjadi array
        $itemsArray = $filtered[$user_sales]->toArray();

        // Mengurutkan data berdasarkan tgl_kunjungan dan waktu_cek_in
        usort($itemsArray, function ($a, $b) {
            // Mengakses properti objek dengan notasi objek ($a->key)
            return strtotime($a->tgl_kunjungan . ' ' . $a->waktu_cek_in) - strtotime($b->tgl_kunjungan . ' ' . $b->waktu_cek_in);
        });

        $firstVisitPerDay = []; // Menyimpan cek-in pertama tiap harinya

        // Loop melalui data untuk menentukan cek-in pertama per tanggal
        foreach ($itemsArray as $data) {
            // Cek apakah sudah ada cek-in untuk tanggal ini
            if (!isset($firstVisitPerDay[$data->tgl_kunjungan])) {
                // Menyimpan cek-in pertama pada tanggal ini
                $firstVisitPerDay[$data->tgl_kunjungan] = $data;
            }
        }

        // Periksa apakah cek-in pertama melebihi pukul 09:30
        foreach ($firstVisitPerDay as $data) {
            // Cek apakah hari adalah Minggu
            $isSunday = Carbon::parse($data->tgl_kunjungan)->isSunday();

            // Skip jika hari Minggu
            if ($isSunday) {
                continue;
            }

            if (in_array($data->kd_toko, $this->tokoAbsen)) {
                // Skip jika toko ada dalam array tokoAbsen
                continue;
            }

            if ($data->waktu_cek_in) {
                // Menggunakan Carbon untuk mem-parsing waktu cek-in
                $waktuCekIn = Carbon::parse($data->waktu_cek_in);

                // Tentukan batas waktu 09:30
                $batasWaktu = Carbon::parse($data->tgl_kunjungan . ' 09:30');

                if ($waktuCekIn->greaterThan($batasWaktu)) {
                    // Tambahkan data punishment
                    $punishmentData[] = [
                        'user_sales' => $data->user_sales,
                        'kd_toko'  => $data->kd_toko,
                        'tgl_kunjungan' => $data->tgl_kunjungan,
                        'waktu_cek_in' => $data->waktu_cek_in,
                        'punishment' => 'Melewati batas waktu cek-in (09:30)',
                    ];
                }
            }
        }

        return $punishmentData;
    }

    public function punishmentCekInCekOut($filtered, $user_sales)
    {
        $punishmentData = []; // Menyimpan data punishment

        foreach ($filtered[$user_sales] as $data) {
            // Cek apakah hari adalah Minggu
            $isSunday = Carbon::parse($data->tgl_kunjungan)->isSunday();

            // Skip jika hari Minggu
            if ($isSunday) {
                continue;
            }

            if (in_array($data->kd_toko, $this->tokoAbsen)) {
                // Skip jika toko ada dalam array tokoAbsen
                continue;
            }

            // Periksa jika waktu cek in dan cek out sama
            if ($data->waktu_cek_in == $data->waktu_cek_out && $data->waktu_cek_in) {
                // Jika sama, maka dianggap lupa cek in atau cek out
                $punishmentData[] = [
                    'user_sales' => $data->user_sales,
                    'kd_toko'  => $data->kd_toko,
                    'tgl_kunjungan' => $data->tgl_kunjungan,
                    'waktu_cek_in' => $data->waktu_cek_in,
                    'waktu_cek_out' => $data->waktu_cek_out,
                    'punishment' => 'Lupa cek in atau cek out (waktu cek in dan cek out sama)',
                ];
            }
        }

        return $punishmentData;
    }


    private function autoSizeColumns($sheet)
    {
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        foreach (range(1, $highestColumnIndex) as $columnIndex) {
            $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
            $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
        }
    }

    private function getDateRange()
    {
        $dateBeginLoop = new DateTime($this->fromDate);
        $dateEndLoop = new DateTime($this->toDate);
        $dates = [];

        while ($dateBeginLoop <= $dateEndLoop) {
            $dates[] = $dateBeginLoop->format('Y-m-d');
            $dateBeginLoop->modify('+1 day');
        }

        return $dates;
    }

    public function title(): string
    {
        return 'Rekap';
    }

    public function columnFormats(): array
    {
        return [];
    }
}
