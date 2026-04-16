<?php

use Livewire\Component;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts.app'), Title('Perfil')] class extends Component
{
    public $name;
    public $email;
    public $password;

    public function mount()
    {
        $user = auth()->user();

        $this->name = $user->name;
        $this->email = $user->email;
    }

    public function updateProfile()
    {
        $user = auth()->user();

        $user->update([
            'name' => $this->name,
            'email' => $this->email,
        ]);

        $this->dispatch('toast', 
            message: 'Perfil atualizado com sucesso!',
            type: 'success'
        );
    }
};
?>
<div class="fixed inset-0 flex items-center justify-center bg-cover bg-center">
    <div class="bg-white bg-opacity-90 p-6 rounded-lg shadow-lg w-96">
        <h2 class="text-2xl font-bold text-center mb-6">Perfil</h2>

        <form wire:submit.prevent="updateProfile">
            <label class="block text-sm font-medium mb-1">Nome</label>
            <input class="w-full border rounded px-3 py-2 mb-3" type="text" wire:model="name" placeholder="Name">

            <label class="block text-sm font-medium mb-1">E-mail</label>
            <input class="w-full border rounded px-3 py-2 mb-3" type="email" wire:model="email" placeholder="Email">

            <label class="block text-sm font-medium mb-1">Senha</label>
            <input class="w-full border rounded px-3 py-2 mb-3" type="password" wire:model="password" placeholder="Password">

            <button type="submit" class="btn text-white p-1 rounded bg-purple-900 hover:bg-purple-600 cursor-pointer">Atualizar perfil</button>
        </form>
    </div>
</div>