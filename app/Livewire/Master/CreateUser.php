<?php

namespace App\Livewire\Master;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class CreateUser extends Component
{
    public $target = 'save';

    public $name;
    public $username;
    public $password;

    public $availableRoles;
    public $roles = [];

    public function mount()
    {
        $this->availableRoles = Role::all();
    }

    public function save()
    {
        $this->validate([
            'name'      => 'required|string|max:255',
            'username'  => 'required|string|max:255|unique:users,username',
            'roles'     => 'array',
            'password'  => 'required'
        ]);

        $user = User::create([
            'name'      => $this->name,
            'username'  => $this->username,
            'password'  => Hash::make($this->password),
        ]);

        $user->syncRoles($this->roles);

        session()->flash('success', 'Data has been created successfully!');

        $this->redirect(IndexUser::class, true);
    }

    public function render()
    {
        return view('livewire.master.create-user');
    }
}
