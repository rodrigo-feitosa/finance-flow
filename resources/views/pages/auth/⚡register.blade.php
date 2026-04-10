<?php

use Livewire\Component;
use App\Models\User;

new class extends Component
{
    public $name;
    public $email;
    public $password;

    public function register()
    {
        User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => bcrypt($this->password),
        ]);

        $this->reset();
    }
};
?>
<div class="min-h-screen bg-gray-100">
    <div class="fixed inset-0 flex items-center justify-center bg-cover bg-center">
        <div class="bg-white bg-opacity-90 p-6 rounded-lg shadow-lg w-96">
            <h2 class="text-2xl font-bold text-center mb-6">Cadastro</h2>

            <form wire:submit.prevent="register">
                <label class="block text-sm font-medium mb-1">Nome</label>
                <input class="w-full border rounded px-3 py-2 mb-3" type="text" wire:model="name" placeholder="Name">

                <label class="block text-sm font-medium mb-1">E-mail</label>
                <input class="w-full border rounded px-3 py-2 mb-3" type="email" wire:model="email" placeholder="Email">

                <label class="block text-sm font-medium mb-1">Senha</label>
                <input class="w-full border rounded px-3 py-2 mb-3" type="password" wire:model="password" placeholder="Password">

                <button class="mt-3 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded w-full" type="submit">Criar conta</button>
            </form>

            <p class="text-sm text-center mt-4">
                Já possui conta?
                <a href="/login" class="text-indigo-600 hover:underline cursor-pointer">
                    Entrar
                </a>
            </p>

        </div>
    </div>
</div>