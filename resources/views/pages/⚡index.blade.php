<?php

use Livewire\Component;

new class extends Component
{
    public function goToExpenses()
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        return redirect()->route('expenses');
    }

    public function goToRevenues()
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        return redirect()->route('revenues');
    }

    public function goToFlow()
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        return redirect()->route('cash-flow');
    }
};
?>

<div class="flex flex-col items-center justify-center min-h-screen bg-gray-100">
    <div class="bg-white p-10 rounded-2xl shadow-lg text-center w-[400px]">

        <h1 class="text-4xl font-bold text-purple-800 mb-4">
            FinanceFlow 💸
        </h1>

        <p class="text-gray-600 mb-6">
            Controle suas finanças de forma simples, rápida e inteligente.
        </p>

        <button
            wire:click="goToExpenses"
            class="w-full bg-purple-800 text-white py-2 mb-2 rounded-lg hover:bg-purple-600 transition cursor-pointer">
            Acessar minhas despesas
        </button>

        <button
            wire:click="goToRevenues"
            class="w-full bg-pink-800 text-white py-2 mb-2 rounded-lg hover:bg-pink-600 transition cursor-pointer">
            Acessar minhas receitas
        </button>

        <button
            wire:click="goToFlow"
            class="w-full bg-blue-800 text-white py-2 rounded-lg hover:bg-blue-600 transition cursor-pointer">
            Fluxo financeiro
        </button>
    </div>
</div>