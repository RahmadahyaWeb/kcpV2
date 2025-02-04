<?php

namespace App\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Component;

class KelompokPart extends Component
{
    public $target = 'periode';
    public $kelompok_2w;
    public $kelompok_4w;
    public $non_aop;
    public $periode;

    public function mount()
    {
        $this->periode = date('Y-m');
    }

    public function fetch_data_by_kategori_part($kategoriPart, $supplier)
    {
        $kcpinformation = DB::connection('kcpinformation');

        $periode = $this->periode;

        // Subquery untuk mendapatkan kelompok_part berdasarkan kategori dan supplier
        $kelompokParts = $kcpinformation->table('kcpinformation.mst_part')
            ->select('kelompok_part')
            ->where('kategori_part', $kategoriPart)
            ->where('kelompok_part', '<>', '')
            ->whereIn('supplier', $supplier)
            ->groupBy('kelompok_part');

        // Subquery untuk menghitung total nominal berdasarkan kelompok_part
        $totalNominal = $kcpinformation->table('kcpinformation.trns_inv_header as header')
            ->join('kcpinformation.trns_inv_details as detail', 'header.noinv', '=', 'detail.noinv')
            ->join('kcpinformation.mst_part as part', 'detail.part_no', '=', 'part.part_no')
            ->select('part.kelompok_part', DB::raw('SUM(detail.nominal_total) as amount_total'))
            ->whereRaw("SUBSTR(header.crea_date, 1, 7) = ?", [$periode])
            ->where('header.flag_batal', 'N')
            ->groupBy('part.kelompok_part');

        // Query utama dengan LEFT JOIN menggunakan leftJoinSub
        $query = $kcpinformation->table('kcpinformation.mst_part')
            ->select('kelompok.kelompok_part', 'nominal.amount_total as amount')
            ->fromSub($kelompokParts, 'kelompok')
            ->leftJoinSub($totalNominal, 'nominal', 'kelompok.kelompok_part', '=', 'nominal.kelompok_part')
            ->get();

        return $query;
    }

    public function fetch_data_for_graph()
    {
        $data_2w = $this->fetch_data_by_kategori_part('2W', ['ASTRA OTOPART']);

        $this->kelompok_2w = [
            'labels' => $data_2w->pluck('kelompok_part'),
            'amount' => $data_2w->pluck('amount'),
        ];

        $data_4w = $this->fetch_data_by_kategori_part('4W', ['ASTRA OTOPART']);

        $this->kelompok_4w = [
            'labels' => $data_4w->pluck('kelompok_part'),
            'amount' => $data_4w->pluck('amount'),
        ];

        $data_non_aop = $this->fetch_data_by_kategori_part('2W', ['ABM', 'SSI', 'KMC']);

        $this->non_aop = [
            'labels' => $data_non_aop->pluck('kelompok_part'),
            'amount' => $data_non_aop->pluck('amount'),
        ];
    }

    public function render()
    {
        $this->fetch_data_for_graph();

        return view('livewire.kelompok-part');
    }
}
