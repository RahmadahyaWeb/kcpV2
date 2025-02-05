<?php

namespace App\Http\Controllers;

use App\Imports\FrekuensiTokoImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class UploadController extends Controller
{
    public function import_frekuensi_toko(Request $request)
    {
        $request->validate([
            'file_frekuensi' => ['required']
        ]);

        Excel::import(new FrekuensiTokoImport, $request->file('file_frekuensi'));

        return back()->with('success', 'All good!');
    }
}
