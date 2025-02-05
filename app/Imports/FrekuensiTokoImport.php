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
            // Lewati baris pertama jika merupakan header
            // if ($index === 0) {
            //     continue;
            // }

            DB::table('frekuensi_toko_temp')->insert([
                'kd_outlet' => $row[0],
                'frekuensi' => $row[1]
            ]);
        }
    }
}
