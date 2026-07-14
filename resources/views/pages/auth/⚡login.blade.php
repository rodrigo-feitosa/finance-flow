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

<div class="page-container flex min-h-[calc(100vh-10rem)] items-center justify-center dark:text-white">
    <div class="panel w-full max-w-md p-7 sm:p-8">

        <span class="badge bg-indigo-50 text-indigo-700 ring-indigo-600/20 dark:bg-indigo-500/10 dark:text-indigo-300">FinanceFlow</span><h2 class="mt-4 text-2xl font-semibold tracking-tight">Boas-vindas de volta</h2><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Entre para acessar suas finanças.</p>

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
                class="btn btn-primary w-full">
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
