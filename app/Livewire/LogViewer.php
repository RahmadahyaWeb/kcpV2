<?php

namespace App\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class LogViewer extends Component
{
    use WithPagination;

    public $target = 'status';
    public $status;

    public function render()
    {
        $items = DB::table('log_api')
            ->where('status', 'like', '%' . $this->status . '%')
            ->paginate();

        return view('livewire.log-viewer', compact(
            'items'
        ));
    }
}
