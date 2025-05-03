<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithSkipDuplicates;

class LssCogsImport implements ToCollection, WithSkipDuplicates, WithHeadingRow
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $existingData = DB::table('lss_cogs_temp')
                ->where('part_no', $row['part_no'])
                ->where('periode_bulan', 04)
                ->where('periode_tahun', 2025)
                ->first();

            $qty = ($row['ks_oh'] + $row['ks_int']) + ($row['kt_oh'] + $row['kt_int']);
            $cogs = $row['cogs'] != 'Check' ? $row['cogs'] : 0;

            if ($existingData) {
                // Jika data sudah ada, lakukan update
                DB::table('lss_cogs_temp')
                    ->where('part_no', $row['part_no'])
                    ->where('periode_bulan', 04)
                    ->where('periode_tahun', 2025)
                    ->update([
                        'qty'           => $qty,
                        'cogs'          => $cogs,
                        'updated_at'    => now()
                    ]);
            } else {
                // Jika data belum ada, lakukan insert
                DB::table('lss_cogs_temp')->insert([
                    'part_no'           => $row['part_no'],
                    'qty'               => $qty,
                    'cogs'              => $cogs,
                    'periode_bulan'     => 04,
                    'periode_tahun'     => 2025,
                    'created_at'        => now()
                ]);
            }
        }
    }
}
