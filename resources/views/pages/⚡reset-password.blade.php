<?php

use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Models\PasswordResetToken;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Facades\Hash;

new class extends Component
{
    public $email = '';
    public $token = null;

    public function generateToken()
    {
        $this->token = Str::random(64);

        PasswordResetToken::create([
            'email' => $this->email,
            'token' => Hash::make($this->token),
            'created_at' => now(),
        ]);
        
        Mail::to($this->email)->send(new ResetPasswordMail($this->token, $this->email));

        //dd($this->token, $this->email);

        $this->email = '';

        $this->dispatch('toast', message: 'E-mail de recuperação enviado!', type: 'success');
    }
};
?>

<div class="page-container flex min-h-[calc(100vh-10rem)] items-center justify-center">
    <div class="panel w-full max-w-md p-7 sm:p-8">
        <span class="badge bg-indigo-50 text-indigo-700 ring-indigo-600/20 dark:bg-indigo-500/10 dark:text-indigo-300">Acesso</span><h2 class="mt-4 text-2xl font-semibold tracking-tight">Recuperar senha</h2><p class="mt-1 mb-6 text-sm text-slate-500 dark:text-slate-400">Enviaremos as instruções para seu e-mail.</p>
        @if (session()->has('error'))
        <div class="bg-red-100 text-red-600 p-2 rounded mb-4 text-sm">
            {{ session('error') }}
        </div>
        @endif
        <form wire:submit.prevent="generateToken" class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1">E-mail</label>
                <input type="email" wire:model="email" class="w-full border rounded px-3 py-2" placeholder="Digite seu email">
            </div>
            <button type="submit" class="btn btn-primary w-full">
                Recuperar senha
            </button>
        </form>
        <p class="text-sm text-center mt-4"> Não possui conta?
            <a href="/register" class="text-indigo-600 hover:underline cursor-pointer">
                Registrar
            </a>
        </p>
    </div>
</div>
