<?php

namespace App\Livewire\Master;

use App\Models\User;
use Livewire\Attributes\Lazy;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class IndexUser extends Component
{
    use WithPagination, WithoutUrlPagination;

    public $target="";

    public function render()
    {
        $users = User::with('roles')->orderBy('name', 'asc')->paginate();

        return view('livewire.master.index-user', compact(
            'users'
        ));
    }
}
