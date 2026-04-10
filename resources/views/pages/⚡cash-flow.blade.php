<?php

use Livewire\Component;
use App\Models\Revenue;
use App\Models\Expense;
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

        return [
            'revenues' => $revenues,
            'expenses' => $expenses,
            'balance' => $revenues - $expenses,
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
};
?>

<div class="p-6 bg-gray-100 min-h-screen">
    <h1 class="text-3xl font-bold text-center mb-6">📊 Balanço Financeiro</h1>

    <div class="flex gap-2 justify-center mb-6">
        <input type="date" wire:model.live="startDate" class="border p-2 rounded">
        <input type="date" wire:model.live="endDate" class="border p-2 rounded">
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-green-500 text-white p-4 rounded shadow text-center">
            <p>Receitas</p>
            <h2 class="text-2xl font-bold">
                R$ {{ number_format($this->getSummary()['revenues'], 2, ',', '.') }}
            </h2>
        </div>

        <div class="bg-red-500 text-white p-4 rounded shadow text-center">
            <p>Despesas</p>
            <h2 class="text-2xl font-bold">
                R$ {{ number_format($this->getSummary()['expenses'], 2, ',', '.') }}
            </h2>
        </div>

        <div class="{{ $this->getSummary()['balance'] >= 0 ? 'bg-blue-500' : 'bg-gray-800' }} text-white p-4 rounded shadow text-center">
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

    <div class="bg-white p-4 rounded shadow">
        <h2 class="text-xl font-bold mb-4">Últimas movimentações</h2>

        <table class="w-full text-xs">
            <thead>
                <tr class="bg-gray-200">
                    <th class="p-2">Data</th>
                    <th class="p-2">Descrição</th>
                    <th class="p-2">Tipo</th>
                    <th class="p-2">Valor</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($this->getLatestMovements() as $mov)
                <tr class="border-b">
                    <td class="p-2">{{ \Carbon\Carbon::parse($mov->date)->format('d/m/Y') }}</td>
                    <td class="p-2">{{ $mov->description }}</td>
                    <td class="p-2">
                        <span class="{{ $mov->tipo === 'receita' ? 'text-green-600' : 'text-red-600' }}">
                            {{ ucfirst($mov->tipo) }}
                        </span>
                    </td>
                    <td class="p-2 font-bold">
                        R$ {{ number_format($mov->value, 2, ',', '.') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>