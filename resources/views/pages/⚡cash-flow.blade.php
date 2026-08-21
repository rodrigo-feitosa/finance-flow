<?php

use Livewire\Component;
use App\Models\Revenue;
use App\Models\Expense;
use App\Models\Investment;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Carbon\Carbon;

new #[Layout('layouts.app'), Title('Fluxo de Caixa')] class extends Component
{
    use WithPagination;

    public $startDate;
    public $endDate;

    public function mount()
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->endOfMonth()->format('Y-m-d');
    }

    public function getSummary()
    {
        $userId = auth()->id();

        $revenues = Revenue::where('user', $userId)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->where('status', 'recebida')
            ->sum('value');

        $expenses = Expense::where('user', $userId)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->where('status', 'paga')
            ->sum('value');

        $investments = Investment::where('user', $userId)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->sum('value');

        $investmentsBalanced = Investment::where('user', $userId)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->where('is_initial', false)
            ->sum('value');

        return [
            'revenues' => $revenues,
            'expenses' => $expenses,
            'investments' => $investments,
            'balance' => $revenues - $expenses - $investmentsBalanced,
        ];
    }

    public function getExpenses($filterStatus)
    {
        return Expense::where('user', auth()->id())
            ->where('type', $filterStatus)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->orderBy('date', 'asc')
            ->paginate(20);
    }

    public function getStatusColor($status)
    {
        return match ($status) {
            'paga' => 'bg-green-600 text-white-900',
            'a pagar' => 'bg-red-600 text-white-900',
        };
    }

    public function getMonthlyProjection()
    {
        $userId = auth()->id();

        $revenues = Revenue::where('user', $userId)
            ->get()
            ->groupBy(fn($item) => Carbon::parse($item->date)->format('Y-m'));

        $expenses = Expense::where('user', $userId)
            ->get()
            ->groupBy(fn($item) => Carbon::parse($item->date)->format('Y-m'));

        $investments = Investment::where('user', $userId)
            ->where('is_initial', false)
            ->get()
            ->groupBy(fn($item) => Carbon::parse($item->date)->format('Y-m'));

        $months = collect();

        foreach ($revenues as $month => $items) {
            $months->put($month, [
                'received_revenues' => $items->where('status', 'recebida')->sum('value'),
                'pending_revenues' => $items->where('status', 'a receber')->sum('value'),
            ]);
        }

        foreach ($expenses as $month => $items) {
            $existing = $months->get($month, [
                'received_revenues' => 0,
                'pending_revenues' => 0,
            ]);

            $months->put($month, array_merge($existing, [
                'paid_expenses' => $items->where('status', 'paga')->sum('value'),
                'pending_expenses' => $items->where('status', 'a pagar')->sum('value'),
            ]));
        }

        foreach ($investments as $month => $items) {
            $existing = $months->get($month, [
                'received_revenues' => 0,
                'pending_revenues' => 0,
                'paid_expenses' => 0,
                'pending_expenses' => 0,
            ]);

            $months->put($month, array_merge($existing, [
                'investments' => $items->sum('value'),
            ]));
        }

        return $months->sortKeys();
    }
};
?>

<div class="page-container dark:text-white">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"><div><span class="badge bg-indigo-50 text-indigo-700 ring-indigo-600/20 dark:bg-indigo-500/10 dark:text-indigo-300">Visão consolidada</span><h1 class="page-heading mt-3">Fluxo de caixa</h1><p class="page-subtitle">Monitore a saúde financeira do período selecionado.</p></div>

        <div class="flex flex-col gap-2 sm:flex-row">
            <input type="date" wire:model.live="startDate" aria-label="Data inicial">
            <input type="date" wire:model.live="endDate" aria-label="Data final">
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <div class="panel overflow-hidden p-4">
            <h2 class="text-lg md:text-xl font-bold mb-4">Despesas variáveis</h2>

            <div class="overflow-x-auto">
                <table class="data-table text-xs md:text-sm">
                    <thead>
                        <tr>
                            <th class="p-2 text-start w-1/12">Data</th>
                            <th class="p-2 text-start w-1/4">Descrição</th>
                            <th class="p-2 text-start w-1/7">Valor</th>
                            <th class="p-2 text-start w-1/9">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->getExpenses('variavel') as $item)
                        <tr class="border-b">
                            <td class="p-2">{{ Carbon::parse($item->date)->format('d/m/Y') }}</td>
                            <td class="p-2">{{ $item->description }}</td>
                            <td class="p-2 font-bold text-red-600">
                                R$ {{ number_format($item->value, 2, ',', '.') }}
                            </td>
                            <td class="p-2">
                                <span class="px-2 py-1 rounded {{ $this->getStatusColor($item->status) }}">
                                    {{ $item->status }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $this->getExpenses('variavel')->links() }}
            </div>
        </div>

        <div class="panel overflow-hidden p-4">
            <h2 class="text-lg md:text-xl font-bold mb-4">Despesas fixas</h2>

            <div class="overflow-x-auto">
                <table class="data-table text-xs md:text-sm">
                    <thead>
                        <tr>
                            <th class="p-2 text-start w-1/12">Data</th>
                            <th class="p-2 text-start w-1/4">Descrição</th>
                            <th class="p-2 text-start w-1/7">Valor</th>
                            <th class="p-2 text-start w-1/9">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->getExpenses('fixa') as $item)
                        <tr class="border-b">
                            <td class="p-2">{{ Carbon::parse($item->date)->format('d/m/Y') }}</td>
                            <td class="p-2">{{ $item->description }}</td>
                            <td class="p-2 font-bold text-red-600">
                                R$ {{ number_format($item->value, 2, ',', '.') }}
                            </td>
                            <td class="p-2">
                                <span class="px-2 py-1 rounded {{ $this->getStatusColor($item->status) }}">
                                    {{ $item->status }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $this->getExpenses('fixa')->links() }}
            </div>
        </div>

        <div class="panel overflow-hidden p-4">
            <h2 class="text-lg md:text-xl font-bold mb-4">Despesas parceladas</h2>

            <div class="overflow-x-auto">
                <table class="data-table text-xs md:text-sm">
                    <thead>
                        <tr>
                            <th class="p-2 text-start w-1/12">Data</th>
                            <th class="p-2 text-start w-1/4">Descrição</th>
                            <th class="p-2 text-start w-1/7">Valor</th>
                            <th class="p-2 text-start w-1/9">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->getExpenses('parcelada') as $item)
                        <tr class="border-b">
                            <td class="p-2">{{ Carbon::parse($item->date)->format('d/m/Y') }}</td>
                            <td class="p-2">{{ $item->description }}</td>
                            <td class="p-2 font-bold text-red-600">
                                R$ {{ number_format($item->value, 2, ',', '.') }}
                            </td>
                            <td class="p-2">
                                <span class="px-2 py-1 rounded {{ $this->getStatusColor($item->status) }}">
                                    {{ $item->status }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $this->getExpenses('parcelada')->links() }}
            </div>
        </div>

    </div>

    <div class="panel mt-6 overflow-hidden p-4">
        <h2 class="text-lg md:text-xl font-bold mb-4">&#128197; Fluxo mensal</h2>

        <div class="overflow-x-auto">
            <table class="data-table min-w-[800px] text-sm text-center">
                <thead>
                    <tr>
                        <th class="p-2">Mês</th>
                        <th class="p-2">Recebidas</th>
                        <th class="p-2">A receber</th>
                        <th class="p-2">Pagas</th>
                        <th class="p-2">A pagar</th>
                        <th class="p-2">Investimento</th>
                        <th class="p-2">Saldo real</th>
                        <th class="p-2">Saldo projetado</th>
                        <th class="p-2">Saldo acumulado</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                    $cumulative = 0;
                    @endphp
                    @foreach ($this->getMonthlyProjection() as $month => $data)
                    @php
                    $received = $data['received_revenues'] ?? 0;
                    $pendingR = $data['pending_revenues'] ?? 0;
                    $paid = $data['paid_expenses'] ?? 0;
                    $pendingE = $data['pending_expenses'] ?? 0;
                    $investments = $data['investments'] ?? 0;

                    $real = $received - ($paid + $investments);
                    $projected = ($received + $pendingR) - ($paid + $pendingE + $investments);
                    $cumulative = $real + ($cumulative ?? 0);
                    @endphp

                    <tr class="odd:bg-white even:bg-gray-100 hover:bg-violet-200
                        dark:odd:bg-[#1A1233] dark:even:bg-[#21184A] hover:bg-[#2A1F5E] transition cursor-pointer">
                        <td class="p-2 font-bold">
                            {{ Carbon::createFromFormat('Y-m', $month)->format('m/Y') }}
                        </td>

                        <td class="p-2 text-green-600 font-bold">R$ {{ number_format($received, 2, ',', '.') }}</td>
                        <td class="p-2 text-green-400 font-bold">R$ {{ number_format($pendingR, 2, ',', '.') }}</td>
                        <td class="p-2 text-red-600 font-bold">R$ {{ number_format($paid, 2, ',', '.') }}</td>
                        <td class="p-2 text-red-400 font-bold">R$ {{ number_format($pendingE, 2, ',', '.') }}</td>
                        <td class="p-2 text-yellow-700 font-bold">R$ {{ number_format($investments, 2, ',', '.') }}</td>

                        <td class="p-2 font-bold {{ $real >= 0 ? 'text-blue-600' : 'text-red-600' }}">
                            R$ {{ number_format($real, 2, ',', '.') }}
                        </td>

                        <td class="p-2 font-bold {{ $projected >= 0 ? 'text-blue-800' : 'text-red-800' }}">
                            R$ {{ number_format($projected, 2, ',', '.') }}
                        </td>
                        <td class="p-2 font-bold {{ $cumulative >= 0 ? 'text-blue-600' : 'text-red-600' }}">
                            R$ {{ number_format($cumulative, 2, ',', '.') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
