<?php

use Livewire\Component;

new class extends Component
{
    public $showMenu = false;

    public function toggleMenu()
    {
        $this->showMenu = !$this->showMenu;
    }

    public function logout()
    {
        auth()->logout();
        session()->flash('toast', [
            'message' => 'Logout realizado com sucesso!',
            'type' => 'success'
        ]);
        return redirect()->route('login');
    }
};
?>

@php
    $items = [
        ['route' => 'index', 'label' => 'Visão geral', 'icon' => 'fa-chart-pie'],
        ['route' => 'expenses', 'label' => 'Despesas', 'icon' => 'fa-arrow-trend-down'],
        ['route' => 'revenues', 'label' => 'Receitas', 'icon' => 'fa-arrow-trend-up'],
        ['route' => 'investments', 'label' => 'Investimentos', 'icon' => 'fa-chart-line'],
        ['route' => 'cash-flow', 'label' => 'Fluxo de caixa', 'icon' => 'fa-arrows-left-right'],
    ];
@endphp

<header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/85 backdrop-blur-xl dark:border-slate-800 dark:bg-[#0b1020]/85">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        <a href="{{ route('index') }}" class="flex shrink-0 items-center" aria-label="FinanceFlow — início">
            <img src="/images/logo.png" alt="FinanceFlow" class="h-8 w-auto sm:h-9">
        </a>

        <nav class="hidden items-center gap-1 lg:flex" aria-label="Navegação principal">
            @foreach ($items as $item)
                <a href="{{ route($item['route']) }}" @class([
                    'inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition',
                    'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300' => request()->routeIs($item['route']),
                    'text-slate-600 hover:bg-slate-100 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white' => !request()->routeIs($item['route']),
                ])>
                    <i class="fa-solid {{ $item['icon'] }} text-xs" aria-hidden="true"></i>{{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="flex items-center gap-2">
            <label class="relative inline-flex cursor-pointer items-center" title="Alternar tema">
                <input type="checkbox" class="peer sr-only" onchange="toggleTheme()">
                <span class="flex h-8 w-14 items-center rounded-full bg-slate-200 p-1 transition peer-checked:bg-indigo-600 dark:bg-slate-700">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-white text-xs shadow-sm transition peer-checked:translate-x-6"><span id="theme-icon" aria-hidden="true">🌙</span></span>
                </span>
                <span class="sr-only">Alternar tema</span>
            </label>

            <div class="relative">
                <button wire:click="toggleMenu" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 hover:text-slate-950 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white" aria-label="Abrir menu do perfil" aria-expanded="{{ $showMenu ? 'true' : 'false' }}">
                    <i class="fa-regular fa-user" aria-hidden="true"></i>
                </button>
                @if($showMenu)
                    <div class="absolute right-0 mt-2 w-48 overflow-hidden rounded-xl border border-slate-200 bg-white p-1 shadow-xl shadow-slate-950/10 dark:border-slate-700 dark:bg-slate-900">
                        @auth
                            <a href="{{ route('profile') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800"><i class="fa-regular fa-user w-4"></i>Perfil</a>
                            <button wire:click="logout" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10"><i class="fa-solid fa-arrow-right-from-bracket w-4"></i>Sair</button>
                        @endauth
                        @guest
                            <a href="{{ route('login') }}" class="block rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800">Entrar</a>
                            <a href="{{ route('register') }}" class="block rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800">Criar conta</a>
                        @endguest
                    </div>
                @endif
            </div>
        </div>
    </div>

    <nav class="fixed inset-x-0 bottom-0 z-40 flex justify-around border-t border-slate-200 bg-white/95 px-1 py-2 backdrop-blur-xl dark:border-slate-800 dark:bg-slate-950/95 lg:hidden" aria-label="Navegação móvel">
        @foreach ($items as $item)
            <a href="{{ route($item['route']) }}" @class([
                'flex min-w-0 flex-col items-center gap-1 rounded-lg px-2 py-1 text-[10px] font-medium',
                'text-indigo-600 dark:text-indigo-400' => request()->routeIs($item['route']),
                'text-slate-500 dark:text-slate-400' => !request()->routeIs($item['route']),
            ])><i class="fa-solid {{ $item['icon'] }} text-sm" aria-hidden="true"></i><span class="truncate">{{ str_replace('Visão geral', 'Início', $item['label']) }}</span></a>
        @endforeach
    </nav>
</header>
