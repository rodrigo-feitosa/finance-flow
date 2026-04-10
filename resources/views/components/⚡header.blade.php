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
        return redirect()->route('index');
    }
};
?>

<header class="bg-gray-900 text-white">
    <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
        <a href="/">
            <h1 class="text-2xl font-bold">FinanceFlow</h1>
        </a>

        <nav class="space-x-3 relative">
            <a href="/" class="rounded bg-blue-500 hover:bg-blue-600 p-2">Home</a>
            <a href="/expenses" class="rounded bg-purple-500 hover:bg-purple-700 p-2">Despesas</a>
            <a href="/revenues" class="rounded bg-green-500 hover:bg-green-700 p-2">Receitas</a>
            <a href="/investments" class="rounded bg-yellow-500 hover:bg-yellow-900 p-2">Investimentos</a>
            <a href="/cash-flow" class="rounded bg-cyan-500 hover:bg-cyan-700 p-2">Fluxo Financeiro</a>
            <div class="relative inline-block">
                <button wire:click="toggleMenu" class="hover:text-gray-300 cursor-pointer">
                    <i class="fa-solid fa-user"></i>
                </button>

                @if($showMenu)
                <div class="absolute right-0 mt-2 w-40 bg-white border rounded-lg shadow-lg">
                    @auth
                    <a href="" class="block w-full text-left px-4 py-2 text-black hover:bg-gray-100">
                        Perfil
                    </a>
                    <a href="" class="block w-full text-left px-4 py-2 text-black hover:bg-gray-100">
                        Configurações
                    </a>

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
    </div>
</header>