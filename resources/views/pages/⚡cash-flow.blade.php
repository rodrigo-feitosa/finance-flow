<?php

use Livewire\Component;
use App\Models\Revenue;
use App\Models\Expense;
use App\Models\Investment;
use Livewire\WithPagination;

new class extends Component
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
            ->sum('value');

        $expenses = Expense::where('user', $userId)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->sum('value');

        $expenses = Expense::where('user', $userId)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->sum('value');

        $investments = Investment::where('user', $userId)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->sum('value');

        return [
            'revenues' => $revenues,
            'expenses' => $expenses,
            'investments' => $investments,
            'balance' => $revenues - $expenses - $investments,
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

    public function getLatestMovements()
    {
        $revenues = Revenue::where('user', auth()->id())
            ->select('date', 'description', 'value', \DB::raw("'receita' as tipo"))
            ->whereBetween('date', [$this->startDate, $this->endDate]);

        $expenses = Expense::where('user', auth()->id())
            ->select('date', 'description', 'value', \DB::raw("'despesa' as tipo"))
            ->whereBetween('date', [$this->startDate, $this->endDate]);

        return $revenues
            ->unionAll($expenses)
            ->orderBy('date', 'desc')
            ->limit(10)
            ->get();
    }

    public function getStatusColor($status)
    {
        return match ($status) {
            'paga' => 'bg-green-300 text-green-900',
            'a pagar' => 'bg-red-300 text-red-900',
        };
    }

    public function getMonthlyProjection()
    {
        $userId = auth()->id();

        $revenues = Revenue::where('user', $userId)
            ->get()
            ->groupBy(fn($item) => \Carbon\Carbon::parse($item->date)->format('Y-m'));

        $expenses = Expense::where('user', $userId)
            ->get()
            ->groupBy(fn($item) => \Carbon\Carbon::parse($item->date)->format('Y-m'));

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

        return $months->sortKeys();
    }
};
?>

<div class="p-6 bg-gray-100 min-h-screen">
    <h1 class="text-3xl font-bold text-center mb-6">📊 Balanço Financeiro</h1>

    <div class="flex gap-2 justify-center mb-6">
        <input type="date" wire:model.live="startDate" class="border p-2 rounded">
        <input type="date" wire:model.live="endDate" class="border p-2 rounded">
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-green-500 text-white p-4 rounded shadow-lg text-center">
            <p>Receitas</p>
            <h2 class="text-2xl font-bold">
                R$ {{ number_format($this->getSummary()['revenues'], 2, ',', '.') }}
            </h2>
        </div>

        <div class="bg-yellow-500 text-white p-4 rounded shadow-lg text-center">
            <p>Investimentos</p>
            <h2 class="text-2xl font-bold">
                R$ {{ number_format($this->getSummary()['investments'], 2, ',', '.') }}
            </h2>
        </div>

        <div class="bg-red-500 text-white p-4 rounded shadow-lg text-center">
            <p>Despesas</p>
            <h2 class="text-2xl font-bold">
                R$ {{ number_format($this->getSummary()['expenses'], 2, ',', '.') }}
            </h2>
        </div>

        <div class="{{ $this->getSummary()['balance'] >= 0 ? 'bg-blue-500' : 'bg-gray-800' }} text-white p-4 rounded shadow-lg text-center">
            <p>Saldo</p>
            <h2 class="text-2xl font-bold">
                R$ {{ number_format($this->getSummary()['balance'], 2, ',', '.') }}
            </h2>
        </div>
    </div>

    <div class="flex gap-1">
        <div class="w-1/2 bg-white p-2 rounded shadow">
            <h2 class="text-xl font-bold mb-4">Despesas variáveis</h2>

            <table class="w-full text-xs">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="p-2 w-1/6">Data</th>
                        <th class="p-2 w-2/6">Descrição</th>
                        <th class="p-2 w-1/6">Valor</th>
                        <th class="p-2 w-1/6">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->getExpenses('variavel') as $item)
                    <tr class="border-b">
                        <td class="p-2">
                            {{ \Carbon\Carbon::parse($item->date)->format('d/m/Y') }}
                        </td>
                        <td class="p-2">
                            {{ $item->description }}
                        </td>
                        <td class="p-2 font-bold text-red-600">
                            R$ {{ number_format($item->value, 2, ',', '.') }}
                        </td>
                        <td class="p-2">
                            <span class="px-2 py-1 rounded text-white {{ $this->getStatusColor($item->status) }}">
                                {{ $item->status }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-4">
                {{ $this->getExpenses('variavel')->links() }}
            </div>
        </div>

        <div class="w-1/2 bg-white p-2 rounded shadow">
            <h2 class="text-xl font-bold mb-4">Despesas fixas</h2>

            <table class="w-full text-xs">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="p-2 w-1/6">Data</th>
                        <th class="p-2 w-2/6">Descrição</th>
                        <th class="p-2 w-1/6">Valor</th>
                        <th class="p-2 w-1/6">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->getExpenses('fixa') as $item)
                    <tr class="border-b">
                        <td class="p-2">
                            {{ \Carbon\Carbon::parse($item->date)->format('d/m/Y') }}
                        </td>
                        <td class="p-2">
                            {{ $item->description }}
                        </td>
                        <td class="p-2 font-bold text-red-600">
                            R$ {{ number_format($item->value, 2, ',', '.') }}
                        </td>
                        <td class="p-2">
                            <span class="px-2 py-1 rounded text-white {{ $this->getStatusColor($item->status) }}">
                                {{ $item->status }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-4">
                {{ $this->getExpenses('fixa')->links() }}
            </div>
        </div>

        <div class="w-1/2 bg-white p-2 rounded shadow">
            <h2 class="text-xl font-bold mb-4">Despesas parceladas</h2>

            <table class="w-full text-xs">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="p-2 w-1/6">Data</th>
                        <th class="p-2 w-2/6">Descrição</th>
                        <th class="p-2 w-1/6">Valor</th>
                        <th class="p-2 w-1/6">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->getExpenses('parcelada') as $item)
                    <tr class="border-b">
                        <td class="p-2">
                            {{ \Carbon\Carbon::parse($item->date)->format('d/m/Y') }}
                        </td>
                        <td class="p-2">
                            {{ $item->description }}
                        </td>
                        <td class="p-2 font-bold text-red-600">
                            R$ {{ number_format($item->value, 2, ',', '.') }}
                        </td>
                        <td class="p-2">
                            <span class="px-2 py-1 rounded text-white {{ $this->getStatusColor($item->status) }}">
                                {{ $item->status }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-4">
                {{ $this->getExpenses('variavel')->links() }}
            </div>
        </div>
    </div>

    <div class="bg-white p-4 rounded shadow mb-8">
        <h2 class="text-xl font-bold mb-4">📅 Fluxo mensal</h2>

        <table class="w-full text-sm text-center">
            <thead>
                <tr class="bg-gray-200">
                    <th class="p-2">Mês</th>
                    <th class="p-2">Recebidas</th>
                    <th class="p-2">A receber</th>
                    <th class="p-2">Pagas</th>
                    <th class="p-2">A pagar</th>
                    <th class="p-2">Saldo real</th>
                    <th class="p-2">Saldo projetado</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($this->getMonthlyProjection() as $month => $data)
                @php
                $received = $data['received_revenues'] ?? 0;
                $pendingR = $data['pending_revenues'] ?? 0;
                $paid = $data['paid_expenses'] ?? 0;
                $pendingE = $data['pending_expenses'] ?? 0;

                $real = $received - $paid;
                $projected = ($received + $pendingR) - ($paid + $pendingE);
                @endphp

                <tr class="border-b">
                    <td class="p-2 font-bold">
                        {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('m/Y') }}
                    </td>

                    <td class="p-2 text-green-600">
                        R$ {{ number_format($received, 2, ',', '.') }}
                    </td>

                    <td class="p-2 text-green-400">
                        R$ {{ number_format($pendingR, 2, ',', '.') }}
                    </td>

                    <td class="p-2 text-red-600">
                        R$ {{ number_format($paid, 2, ',', '.') }}
                    </td>

                    <td class="p-2 text-red-400">
                        R$ {{ number_format($pendingE, 2, ',', '.') }}
                    </td>

                    <td class="p-2 font-bold {{ $real >= 0 ? 'text-blue-600' : 'text-red-600' }}">
                        R$ {{ number_format($real, 2, ',', '.') }}
                    </td>

                    <td class="p-2 font-bold {{ $projected >= 0 ? 'text-blue-800' : 'text-red-800' }}">
                        R$ {{ number_format($projected, 2, ',', '.') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>