<?php

namespace App\Livewire\Master;

use App\Models\User;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class EditUser extends Component
{
    public $target = 'save';

    public User $user;
    public $name;
    public $username;
    public $status;

    public $roles = [];
    public $availableRoles;

    public function mount(User $user)
    {
        $this->user = $user;
        $this->name = $user->name;
        $this->username = $user->username;
        $this->status = $user->status;

        $this->availableRoles = Role::all();

        $this->roles = $user->roles->pluck('name')->toArray();
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $this->user->id,
            'roles' => 'array',
        ]);

        $this->user->name = $this->name;
        $this->user->username = $this->username;
        $this->user->status = $this->status;
        $this->user->save();

        $this->user->syncRoles($this->roles);

        session()->flash('success', 'Changes have been saved successfully!');

        $this->redirect(IndexUser::class, true);
    }

    public function render()
    {
        return view('livewire.master.edit-user');
    }
}
