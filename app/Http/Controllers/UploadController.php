<?php

namespace App\Http\Controllers;

use App\Imports\FrekuensiTokoImport;
use App\Imports\LssCogsImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class UploadController extends Controller
{
    public function import_frekuensi_toko(Request $request)
    {
        $request->validate([
            'file_frekuensi' => ['required']
        ]);

        try {
            $kcpapplication = DB::connection('mysql');
            $kcpapplication->beginTransaction();

            // Import data ke dalam tabel frekuensi_toko_temp
            Excel::import(new FrekuensiTokoImport, $request->file('file_frekuensi'));

            $kcpapplication->commit();
        } catch (\Exception $e) {
            $kcpapplication->rollBack();
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Berhasil upload!');
    }

    public function import_cogs(Request $request)
    {
        $request->validate([
            'file_cogs' => ['required']
        ]);

        try {
            $kcpapplication = DB::connection('mysql');
            $kcpapplication->beginTransaction();

            Excel::import(new LssCogsImport, $request->file('file_cogs'));

            $kcpapplication->commit();
        } catch (\Exception $e) {
            $kcpapplication->rollBack();
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Berhasil upload!');
    }
}
