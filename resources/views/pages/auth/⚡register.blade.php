<?php

use Livewire\Component;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts.app'), Title('Cadastro')] class extends Component
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
        $this->dispatch('toast', message: 'Cadastro realizado com sucesso!');
    }
};
?>
<div class="page-container flex min-h-[calc(100vh-10rem)] items-center justify-center dark:text-white">
    <div class="panel w-full max-w-md p-7 sm:p-8">
        <span class="badge bg-indigo-50 text-indigo-700 ring-indigo-600/20 dark:bg-indigo-500/10 dark:text-indigo-300">FinanceFlow</span><h2 class="mt-4 text-2xl font-semibold tracking-tight">Crie sua conta</h2><p class="mt-1 mb-6 text-sm text-slate-500 dark:text-slate-400">Comece a organizar sua vida financeira.</p>

        <form wire:submit.prevent="register" class="space-y-4">
            <label class="block text-sm font-medium mb-1">Nome</label>
            <input class="w-full" type="text" wire:model="name" placeholder="Seu nome">

            <label class="block text-sm font-medium mb-1">E-mail</label>
            <input class="w-full" type="email" wire:model="email" placeholder="voce@exemplo.com">

            <label class="block text-sm font-medium mb-1">Senha</label>
            <input class="w-full" type="password" wire:model="password" placeholder="••••••••">

            <button class="btn btn-primary w-full" type="submit">Criar conta</button>
        </form>

        <p class="text-sm text-center mt-4">
            Já possui conta?
            <a href="/login" class="text-indigo-600 hover:underline cursor-pointer">
                Entrar
            </a>
        </p>

    </div>
</div>
