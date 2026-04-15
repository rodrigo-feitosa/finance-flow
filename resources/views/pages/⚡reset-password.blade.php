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

<div class="fixed inset-0 flex items-center justify-center bg-cover bg-center">
    <div class="bg-white/90 backdrop-blur-md p-8 rounded-xl shadow-xl w-96">
        <h2 class="text-2xl font-bold text-center mb-6">Recuperar Senha</h2>
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
            <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded hover:bg-indigo-700">
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