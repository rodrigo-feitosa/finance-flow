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

    // 🔹 RESUMO
    public function getSummaryProperty()
    {
        $userId = auth()->id();

        $revenues = Revenue::where('user', $userId)
            ->whereBetween('data', [$this->startDate, $this->endDate])
            ->sum('value');

        $expenses = Expense::where('user', $userId)
            ->whereBetween('data', [$this->startDate, $this->endDate])
            ->sum('value');

        return [
            'revenues' => $revenues,
            'expenses' => $expenses,
            'balance' => $revenues - $expenses,
        ];
    }

    // 🔹 GASTOS POR TIPO
    public function getExpensesByTypeProperty()
    {
        return Expense::selectRaw('type, SUM(value) as total')
            ->where('user', auth()->id())
            ->whereBetween('data', [$this->startDate, $this->endDate])
            ->groupBy('type')
            ->get();
    }

    // 🔹 ÚLTIMAS MOVIMENTAÇÕES
    public function getLatestMovementsProperty()
    {
        $revenues = Revenue::where('user', auth()->id())
            ->select('data', 'description', 'value', \DB::raw("'receita' as tipo"))
            ->whereBetween('data', [$this->startDate, $this->endDate]);

        $expenses = Expense::where('user', auth()->id())
            ->select('data', 'description', 'value', \DB::raw("'despesa' as tipo"))
            ->whereBetween('data', [$this->startDate, $this->endDate]);

        return $revenues
            ->unionAll($expenses)
            ->orderBy('data', 'desc')
            ->limit(10)
            ->get();
    }
};
?>

<div class="p-6 bg-gray-100 min-h-screen">
    <h1 class="text-3xl font-bold text-center mb-6">📊 Balanço Financeiro</h1>

    {{-- 🔹 FILTRO --}}
    <div class="flex gap-2 justify-center mb-6">
        <input type="date" wire:model.live="startDate" class="border p-2 rounded">
        <input type="date" wire:model.live="endDate" class="border p-2 rounded">
    </div>

    {{-- 🔹 CARDS --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">

        <div class="bg-green-500 text-white p-4 rounded shadow text-center">
            <p>Receitas</p>
            <h2 class="text-2xl font-bold">
                R$ {{ number_format($this->summary['revenues'], 2, ',', '.') }}
            </h2>
        </div>

        <div class="bg-red-500 text-white p-4 rounded shadow text-center">
            <p>Despesas</p>
            <h2 class="text-2xl font-bold">
                R$ {{ number_format($this->summary['expenses'], 2, ',', '.') }}
            </h2>
        </div>

        <div class="{{ $this->summary['balance'] >= 0 ? 'bg-blue-500' : 'bg-gray-800' }} text-white p-4 rounded shadow text-center">
            <p>Saldo</p>
            <h2 class="text-2xl font-bold">
                R$ {{ number_format($this->summary['balance'], 2, ',', '.') }}
            </h2>
        </div>
    </div>

    {{-- 🔹 GASTOS POR TIPO --}}
    <div class="bg-white p-4 rounded shadow mb-8">
        <h2 class="text-xl font-bold mb-4">Gastos por tipo</h2>

        @foreach ($this->expensesByType as $item)
        <div class="flex justify-between border-b py-2">
            <span>{{ ucfirst($item->type) }}</span>
            <span>R$ {{ number_format($item->total, 2, ',', '.') }}</span>
        </div>
        @endforeach
    </div>

    {{-- 🔹 ÚLTIMAS MOVIMENTAÇÕES --}}
    <div class="bg-white p-4 rounded shadow">
        <h2 class="text-xl font-bold mb-4">Últimas movimentações</h2>

        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-200">
                    <th class="p-2">Data</th>
                    <th class="p-2">Descrição</th>
                    <th class="p-2">Tipo</th>
                    <th class="p-2">Valor</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($this->latestMovements as $mov)
                <tr class="border-b">
                    <td class="p-2">{{ \Carbon\Carbon::parse($mov->data)->format('d/m/Y') }}</td>
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