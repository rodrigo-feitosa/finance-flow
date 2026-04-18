<?php

use Livewire\Component;
use Illuminate\Support\Facades\Http;

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

<header class="mt-2 mx-2 bg-[#0B0618] border-[#2E235A] text-white rounded-xl z-999">
    <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
        <a href="{{ route('index') }}">
            <img src="/images/logo.png" alt="Logo" class="w-50 h-auto">
        </a>

        <nav class="hidden md:flex space-x-3 relative items-center">
            <a href="{{ route('index') }}" class="rounded bg-violet-500 hover:bg-violet-600 p-2">Home</a>
            <a href="{{ route('expenses') }}" class="rounded bg-violet-500 hover:bg-violet-600 p-2">Despesas</a>
            <a href="{{ route('revenues') }}" class="rounded bg-violet-500 hover:bg-violet-600 p-2">Receitas</a>
            <a href="{{ route('investments') }}" class="rounded bg-violet-500 hover:bg-violet-600 p-2">Investimentos</a>
            <a href="{{ route('cash-flow') }}" class="rounded bg-violet-500 hover:bg-violet-600 p-2">Fluxo Financeiro</a>
            <button
                onclick="toggleTheme()"
                class="p-2 rounded-lg bg-violet-500 hover:bg-violet-600 transition flex items-center justify-center"
                title="Alternar tema">
                <span id="theme-icon">🌙</span>
            </button>
            <div class="relative inline-block">
                <button wire:click="toggleMenu" class="scale-120 pl-5 hover:text-gray-300 transition hover:scale-150 cursor-pointer">
                    <i class="fa-solid fa-user"></i>
                </button>

                @if($showMenu)
                <div class="absolute right-0 mt-2 w-40 bg-white border rounded-lg shadow-lg">
                    @auth
                    <a href="{{ route('profile') }}" class="block w-full text-left px-4 py-2 text-black hover:bg-gray-100">
                        Perfil
                    </a>
                    <!-- <a wire:click="goToPreferences" class="block w-full text-left px-4 py-2 text-black hover:bg-gray-100">
                        Preferências
                    </a> -->

                    <button wire:click="logout" class="block w-full text-left px-4 py-2 text-red-500 hover:bg-gray-100">
                        Logout
                    </button>
                    @endauth

                    @guest
                    <a href="/login" class="block w-full text-left px-4 py-2 text-black hover:bg-gray-100">
                        Login
                    </a>
                    <a href="/register" class="block w-full text-left px-4 py-2 text-black hover:bg-gray-100">
                        Registrar
                    </a>
                    @endguest
                </div>
                @endif
            </div>
        </nav>

        <nav class="flex md:hidden justify-end relative">
            <button
                onclick="toggleTheme()"
                class="mr-3 p-2 rounded-lg bg-violet-500 hover:bg-violet-600 transition">
                <span id="theme-icon-mobile">🌙</span>
            </button>
            <div class="relative inline-block">
                <button wire:click="toggleMenu" class="hover:text-gray-300 cursor-pointer">
                    <i class="fa-solid fa-user"></i>
                </button>

                @if($showMenu)
                <div class="absolute right-0 mt-2 w-40 bg-white border rounded-lg shadow-lg">
                    @auth
                    <a wire:click="goToProfile" class="block px-4 py-2 text-black hover:bg-gray-100">Perfil</a>
                    <!-- <a wire:click="goToPreferences" class="block px-4 py-2 text-black hover:bg-gray-100">Preferências</a> -->
                    <button wire:click="logout" class="block w-full text-left px-4 py-2 text-red-500 hover:bg-gray-100">
                        Logout
                    </button>
                    @endauth

                    @guest
                    <a href="/login" class="block px-4 py-2 text-black hover:bg-gray-100">Login</a>
                    <a href="/register" class="block px-4 py-2 text-black hover:bg-gray-100">Registrar</a>
                    @endguest
                </div>
                @endif
            </div>
        </nav>

        <div class="fixed bottom-0 left-0 w-full bg-gray-900 text-white flex justify-around py-3 md:hidden shadow-lg z-50">
            <a href="{{ route('index') }}" class="flex flex-col items-center text-xs">
                <i class="fa-solid fa-hand-holding-dollar text-lg "></i>
                Home
            </a>

            <a href="{{ route('expenses') }}" class="flex flex-col items-center text-xs">
                <i class="fa-solid fa-money-bill-wave text-lg"></i>
                Despesas
            </a>

            <a href="{{ route('revenues') }}" class="flex flex-col items-center text-xs">
                <i class="fa-solid fa-coins text-lg"></i>
                Receitas
            </a>

            <a href="{{ route('investments') }}" class="flex flex-col items-center text-xs">
                <i class="fa-solid fa-chart-line text-lg"></i>
                Invest.
            </a>

            <a href="{{ route('cash-flow') }}" class="flex flex-col items-center text-xs">
                <i class="fa-solid fa-exchange-alt text-lg"></i>
                Fluxo
            </a>
        </div>
    </div>
</header>