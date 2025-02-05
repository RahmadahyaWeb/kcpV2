<?php

namespace App\Imports;

use App\Models\FrekuensiTokoTemp;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithSkipDuplicates;

class FrekuensiTokoImport implements ToCollection, WithSkipDuplicates
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $existingData = DB::table('frekuensi_toko_temp')
                ->where('kd_outlet', $row[0])
                ->where('periode_bulan', $row[2])
                ->where('periode_tahun', $row[3])
                ->first();

            if ($existingData) {
                // Jika data sudah ada, lakukan update
                DB::table('frekuensi_toko_temp')
                    ->where('kd_outlet', $row[0])
                    ->where('periode_bulan', $row[2])
                    ->where('periode_tahun', $row[3])
                    ->update([
                        'frekuensi' => $row[1],
                        'updated_at' => now()
                    ]);
            } else {
                // Jika data belum ada, lakukan insert
                DB::table('frekuensi_toko_temp')->insert([
                    'kd_outlet' => $row[0],
                    'frekuensi' => $row[1],
                    'periode_bulan' => $row[2],
                    'periode_tahun' => $row[3],
                    'created_at' => now()
                ]);
            }
        }
    }
}
