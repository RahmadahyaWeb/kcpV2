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

    public $target = "search";
    public $search;

    public function render()
    {
        $search = $this->search;

        $users = User::with('roles')
            ->when($search, function ($query) use ($search) {
                return $query->where('username', 'like', '%' . $search . '%')
                    ->orWhere('name', 'like', '%' . $search . '%');
            })
            ->orderBy('name', 'asc')
            ->paginate();


        return view('livewire.master.index-user', compact(
            'users'
        ));
    }
}
