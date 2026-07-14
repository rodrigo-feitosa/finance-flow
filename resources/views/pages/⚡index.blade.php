<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts.app'), Title('Finance Flow')] class extends Component
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

    public function goToInvestments()
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        return redirect()->route('investments');
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

<div class="page-container flex min-h-[calc(100vh-8rem)] items-center justify-center">
    <section class="w-full max-w-3xl text-center">
        <span class="badge bg-indigo-50 text-indigo-700 ring-indigo-600/20 dark:bg-indigo-500/10 dark:text-indigo-300 dark:ring-indigo-400/20">Seu espaço financeiro</span>
        <h1 class="mt-5 text-4xl font-semibold tracking-tight text-slate-950 sm:text-5xl dark:text-white">Decisões melhores começam com clareza.</h1>
        <p class="mx-auto mt-5 max-w-xl text-base leading-7 text-slate-500 dark:text-slate-400">Organize receitas, despesas e investimentos em um único lugar, com uma experiência simples e focada.</p>

        <div class="panel mt-10 grid overflow-hidden text-left sm:grid-cols-2">
            <button wire:click="goToExpenses" class="group border-b border-slate-200 p-6 text-left hover:bg-slate-50 sm:border-r dark:border-slate-800 dark:hover:bg-slate-800/60">
                <i class="fa-solid fa-arrow-trend-down text-rose-500"></i><span class="mt-4 block font-semibold">Despesas</span><span class="mt-1 block text-sm text-slate-500 dark:text-slate-400">Acompanhe cada saída.</span>
            </button>
            <button wire:click="goToRevenues" class="group border-b border-slate-200 p-6 text-left hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800/60">
                <i class="fa-solid fa-arrow-trend-up text-emerald-500"></i><span class="mt-4 block font-semibold">Receitas</span><span class="mt-1 block text-sm text-slate-500 dark:text-slate-400">Registre o que entra.</span>
            </button>
            <button wire:click="goToInvestments" class="group border-r border-slate-200 p-6 text-left hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800/60">
                <i class="fa-solid fa-chart-line text-amber-500"></i><span class="mt-4 block font-semibold">Investimentos</span><span class="mt-1 block text-sm text-slate-500 dark:text-slate-400">Veja seu patrimônio crescer.</span>
            </button>
            <button wire:click="goToFlow" class="group p-6 text-left hover:bg-slate-50 dark:hover:bg-slate-800/60">
                <i class="fa-solid fa-arrows-left-right text-indigo-500"></i><span class="mt-4 block font-semibold">Fluxo de caixa</span><span class="mt-1 block text-sm text-slate-500 dark:text-slate-400">Entenda seu saldo mensal.</span>
            </button>
        </div>
    </section>
</div>
