<?php

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts.app'), Title('Login')] class extends Component
{
    public $email;
    public $password;

    public function login()
    {
        $credentials = [
            'email' => $this->email,
            'password' => $this->password
        ];

        if (Auth::attempt($credentials)) {
            session()->flash('toast', [
                'message' => 'Login realizado com sucesso!',
                'type' => 'success'
            ]);
            return redirect()->route('index');
        }

        session()->flash('error', 'Email ou senha inválidos.');
    }
};
?>

<div class="fixed inset-0 flex items-center justify-center bg-cover bg-center">
    <div class="bg-white backdrop-blur-md p-8 rounded-xl shadow-xl w-96">

        <h2 class="text-2xl font-bold text-center mb-6">Login</h2>

        @if (session()->has('error'))
        <div class="bg-red-100 text-red-600 p-2 rounded mb-4 text-sm">
            {{ session('error') }}
        </div>
        @endif

        <form wire:submit.prevent="login" class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1">E-mail</label>
                <input
                    type="email"
                    wire:model="email"
                    class="w-full border rounded px-3 py-2"
                    placeholder="Digite seu email">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Senha</label>
                <input
                    type="password"
                    wire:model="password"
                    class="w-full border rounded px-3 py-2"
                    placeholder="Digite sua senha">
            </div>

            <button
                type="submit"
                class="w-full bg-indigo-600 text-white py-2 rounded hover:bg-indigo-700 cursor-pointer">
                Entrar
            </button>
        </form>

        <p class="text-sm text-center mt-4">
            Não possui conta?
            <a href="/register" class="text-indigo-600 hover:underline cursor-pointer">
                Registrar
            </a>
        </p>
        <p class="text-sm text-center mt-4">
            Esqueceu a senha?
            <a href="/reset-password" class="text-indigo-600 hover:underline cursor-pointer">
                Clique aqui
            </a>
        </p>
    </div>
</div>