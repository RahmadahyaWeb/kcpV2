<?php

namespace App\Livewire\Pajak;

use Livewire\Component;

class IndexPajakMasukan extends Component
{
    public $target = '';
    public $from_date, $to_date;

    public function render()
    {
        return view('livewire.pajak.index-pajak-masukan');
    }
}
