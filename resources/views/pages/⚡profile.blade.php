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
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . auth()->id()],
            'password' => ['nullable', 'string', 'min:8'],
        ], [
            'name.required' => 'O nome é obrigatório.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está em uso.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
        ]);

        $user = auth()->user();

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if (filled($validated['password'])) {
            // hash aplicado pelo cast 'hashed' do model User
            $data['password'] = $validated['password'];
        }

        $user->update($data);

        $this->password = '';

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
            <div><label class="mb-1.5 block text-sm font-medium">Nova senha <span class="font-normal text-slate-400">(opcional)</span></label><input class="w-full" type="password" wire:model="password" placeholder="Deixe vazio para manter a senha atual">
                @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <button type="submit" class="btn btn-primary w-full">Atualizar perfil</button>
        </form>
    </div>
</div>
