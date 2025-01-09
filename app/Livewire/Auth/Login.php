<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.auth')]
class Login extends Component
{

    public $username;
    public $password;

    public function login()
    {
        $validated = $this->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        if (Auth::attempt($validated)) {
            if (Auth::user()->status != 'active') {
                $this->addError('username', 'Your account is inactive. Please contact the administrator to activate your account.');

                Auth::logout();
            } else {
                $this->redirectIntended('dashboard');
            }
        }

        $this->addError('username', 'Incorrect username or password.');
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
