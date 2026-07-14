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
<div class="page-container flex min-h-[calc(100vh-12rem)] items-center justify-center">
    <div class="panel w-full max-w-lg p-6 sm:p-8">
        <div class="mb-7"><span class="badge bg-indigo-50 text-indigo-700 ring-indigo-600/20 dark:bg-indigo-500/10 dark:text-indigo-300">Conta</span><h2 class="mt-3 text-2xl font-semibold tracking-tight">Seu perfil</h2><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Mantenha seus dados atualizados.</p></div>
        <form wire:submit.prevent="updateProfile" class="space-y-5">
            <div><label class="mb-1.5 block text-sm font-medium">Nome</label><input class="w-full" type="text" wire:model="name" placeholder="Seu nome"></div>
            <div><label class="mb-1.5 block text-sm font-medium">E-mail</label><input class="w-full" type="email" wire:model="email" placeholder="voce@exemplo.com"></div>
            <div><label class="mb-1.5 block text-sm font-medium">Senha</label><input class="w-full" type="password" wire:model="password" placeholder="••••••••"></div>
            <button type="submit" class="btn btn-primary w-full">Atualizar perfil</button>
        </form>
    </div>
</div>
