<?php

use Livewire\Component;
use App\Models\Expense;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts.app'), Title('Despesas')] class extends Component
{
    use WithFileUploads, WithPagination;

    public $file;

    public $modalAdd = false;
    public $modalImport = false;
    public $modalEdit = false;

    public $date;
    public $description;
    public $value;
    public $type;
    public $payment_method;
    public $status;

    public $filterType;
    public $filterPaymentMethod;
    public $filterStatus;
    public $filterDateStart;
    public $filterDateEnd;

    public $editingExpenseId;

    public $installments = 1;

    public function getExpensesProperty()
    {
        if (!auth()->check()) {
            return collect();
        }

        $query = Expense::where('user', auth()->id());

        if ($this->filterType) {
            $query->where('type', $this->filterType);
        }

        if ($this->filterPaymentMethod) {
            $query->where('payment_method', $this->filterPaymentMethod);
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterDateStart) {
            $query->whereDate('date', '>=', $this->filterDateStart);
        }

        if ($this->filterDateEnd) {
            $query->whereDate('date', '<=', $this->filterDateEnd);
        }

        return $query->orderBy('date', 'asc')->paginate(20);
    }

    public function applyFilters()
    {
        $this->getExpensesProperty([
            'type' => $this->filterType,
            'payment_method' => $this->filterPaymentMethod,
            'status' => $this->filterStatus,
            'date_start' => $this->filterDateStart,
            'date_end' => $this->filterDateEnd,
        ]);
    }

    public function showAddExpense()
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $this->modalAdd = true;
    }

    public function closeAddExpense()
    {
        $this->reset(['date', 'description', 'value', 'type', 'payment_method', 'status']);
        $this->modalAdd = false;
    }

    public function showImport()
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $this->modalImport = true;
    }

    public function closeImport()
    {
        $this->modalImport = false;
    }

    public function showEditModal($idExpense)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $this->modalEdit = true;

        $expense = Expense::where('id', $idExpense)->first();

        $this->editingExpenseId = $expense->id;

        $this->date = $expense->date;
        $this->description = $expense->description;
        $this->value = $expense->value;
        $this->type = $expense->type;
        $this->payment_method = $expense->payment_method;
        $this->status = $expense->status;
    }

    public function closeEditModal()
    {
        $this->modalEdit = false;
    }

    public function addExpense()
    {
        $installments = (int) $this->installments;

        if ($installments <= 1) {
            Expense::create([
                'user' => auth()->id(),
                'date' => $this->date,
                'description' => $this->description,
                'value' => $this->value,
                'type' => $this->type,
                'payment_method' => $this->payment_method,
                'status' => $this->status,
            ]);
        } else {
            for ($i = 0; $i < $installments; $i++) {
                Expense::create([
                    'user' => auth()->id(),
                    'date' => \Carbon\Carbon::parse($this->date)->addMonths($i),
                    'description' => $this->description . ' (' . ($i + 1) . '/' . $installments . ')',
                    'value' => $this->value,
                    'type' => 'parcelada', // força consistência
                    'payment_method' => $this->payment_method,
                    'status' => $this->status,
                ]);
            }
        }

        $this->reset(['installments']);
        $this->closeAddExpense();
    }

    public function importExpenses()
    {
        if (!$this->file) {
            session()->flash('message', 'Selecione um arquivo.');
            return;
        }

        $file = fopen($this->file->getRealPath(), 'r');

        // pula cabeçalho
        fgetcsv($file, 0, ',');

        $count = 0;

        while (($row = fgetcsv($file, 0, ',')) !== false) {
            $row = array_map(fn($item) => mb_convert_encoding($item, 'UTF-8', 'auto'), $row);
            $date = \Carbon\Carbon::createFromFormat('d/m/Y', $row[0])->format('Y-m-d');

            if (count($row) < 6 || empty($row[4])) {
                continue;
            }

            Expense::create([
                'user' => auth()->id(),
                'date' => $date,
                'description' => $row[1],
                'value' => $row[2],
                'type' => $row[3],
                'payment_method' => $row[4],
                'status' => $row[5],
            ]);

            $count++;
        }

        fclose($file);

        $this->closeImport();

        session()->flash('message', $count . ' despesas importadas com sucesso.');
    }

    public function removeExpense($id)
    {
        Expense::where('id', $id)
            ->where('user', auth()->id())
            ->delete();
    }

    public function editExpense()
    {
        Expense::where('id', $this->editingExpenseId)->update([
            'date' => $this->date,
            'description' => $this->description,
            'value' => $this->value,
            'type' => $this->type,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
        ]);

        $this->closeEditModal();
    }

    public function getTypeColor($type)
    {
        return match ($type) {
            'fixa' => 'bg-blue-300 text-gray-800 shadow-sm outline-1 outline-blue-400',
            'variavel' => 'bg-yellow-300 text-yellow-900 shadow-sm outline-1 outline-yellow-400',
            'parcelada' => 'bg-fuchsia-300 text-fuchsia-900 shadow-sm outline-1 outline-fuchsia-400',
        };
    }

    public function getPaymentMethodColor($method)
    {
        return match ($method) {
            'credito' => 'bg-blue-300 text-blue-900 shadow-sm outline-1 outline-blue-400',
            'debito' => 'bg-lime-300 text-lime-900 shadow-sm outline-1 outline-lime-400',
            'pix' => 'bg-teal-300 text-teal-900 shadow-sm outline-1 outline-teal-400',
            'dinheiro' => 'bg-yellow-300 text-gray-800 shadow-sm outline-1 outline-yellow-400',
        };
    }

    public function getStatusColor($status)
    {
        return match ($status) {
            'paga' => 'bg-green-300 text-green-900 shadow-sm outline-1 outline-green-400',
            'a pagar' => 'bg-red-300 text-red-900 shadow-sm outline-1 outline-red-400',
        };
    }
};
?>

<div>
    @if (session()->has('message'))
    <div class="bg-green-500 text-white p-2 text-center mt-2">
        {{ session('message') }}
    </div>
    @endif
    <h1 class="text-3xl font-bold text-center pt-10">Bem-vindo ao FinanceFlow</h1>
    <p class="text-center mt-4 text-gray-600">Gerencie suas finanças pessoais de forma fácil e eficiente.</p>

    <div class="text-center gap-1">
        <button
            wire:click="showAddExpense"
            class="btn w-24 text-white bg-blue-800 rounded p-1 hover:bg-blue-600 cursor-pointer">
            Adicionar
        </button>
        <button
            wire:click="showImport"
            class="btn w-24 text-white bg-fuchsia-800 rounded p-1 hover:bg-fuchsia-600 cursor-pointer">
            Importar
        </button>
    </div>

    <div class="mx-auto max-w-5xl px-4">
        <div class="flex flex-wrap gap-2 mt-4 justify-center">
            <select wire:model="filterType" class="border p-1 rounded">
                <option value="">Tipo</option>
                <option value="fixa">Fixa</option>
                <option value="variavel">Variável</option>
                <option value="parcelada">Parcelada</option>
            </select>

            <select wire:model="filterPaymentMethod" class="border p-1 rounded">
                <option value="">Pagamento</option>
                <option value="credito">Crédito</option>
                <option value="debito">Débito</option>
                <option value="pix">Pix</option>
                <option value="dinheiro">Dinheiro</option>
            </select>

            <select wire:model="filterStatus" class="border p-1 rounded">
                <option value="">Status</option>
                <option value="paga">Paga</option>
                <option value="a pagar">A pagar</option>
            </select>

            <input type="date" wire:model="filterDateStart" class="border p-1 rounded">
            <input type="date" wire:model="filterDateEnd" class="border p-1 rounded">

            <button wire:click="applyFilters" class="btn w-24 text-white bg-yellow-600 rounded p-1 hover:bg-yellow-800 cursor-pointer">Filtrar</button>
        </div>
        @if ($this->expenses->isEmpty())
        <p class="mt-6 text-gray-600">Nenhuma despesa registrada. Adicione uma nova despesa para começar a gerenciar suas finanças.</p>
        @else
        <div>
            <table class="hidden md:table min-w-full text-sm mt-6 border border-gray-200 rounded-lg overflow-hidden shadow-xl shadow-purple-600">
                <thead class="bg-violet-800 text-white">
                    <tr>
                        <th class="border border-black p-2 w-1/9">Data</th>
                        <th class="border border-black p-2 w-1/3">Descrição</th>
                        <th class="border border-black p-2 w-1/9">Valor</th>
                        <th class="border border-black p-2 w-1/9">Tipo</th>
                        <th class="border border-black p-2 w-1/6">Forma de pagamento</th>
                        <th class="border border-black p-2 w-1/9">Status</th>
                        <th class="border border-black p-2 w-1/9"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($this->expenses as $expense)
                    <tr wire:click="showEditModal({{ $expense->id }})" class="odd:bg-white even:bg-gray-100 hover:bg-violet-200 transition cursor-pointer">
                        <td class="border p-2">{{ \Carbon\Carbon::parse($expense->date)->format('d/m/Y') }}</td>
                        <td class="border p-2">{{ $expense->description }}</td>
                        <td class="border p-2">R$ {{ number_format($expense->value, 2, ',', '.') }}</td>
                        <td class="border p-2">
                            <p class="rounded-xl p-1 {{ $this->getTypeColor($expense->type) }}">{{ $expense->type }}</p>
                        </td>
                        <td class="border p-2">
                            <p class="rounded-xl p-1 {{ $this->getPaymentMethodColor($expense->payment_method) }}">{{ $expense->payment_method }}</p>
                        </td>
                        <td class="border p-2">
                            <p class="rounded-xl p-1 {{ $this->getStatusColor($expense->status) }}">{{ $expense->status }}</p>
                        </td>
                        <td class="border p-2">
                            <button wire:click.stop="removeExpense({{ $expense->id }})" class="btn text-white bg-red-800 rounded p-1 hover:bg-red-600 cursor-pointer">Excluir</button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="md:hidden mt-4 space-y-3">
                @foreach ($this->expenses as $expense)
                <div wire:click="showEditModal({{ $expense->id }})" class="bg-white p-3 rounded shadow">
                    <div class="flex justify-between mb-2">
                        <span>{{ $expense->description }}</span>
                        <span>R$ {{ number_format($expense->value, 2, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm text-gray-500">
                        <div>
                            <span>{{ \Carbon\Carbon::parse($expense->date)->format('d/m/Y') }}</span>
                            <span class="ml-2 px-2 py-1 rounded {{ $this->getTypeColor($expense->type) }}">{{ $expense->type }}</span>
                            <span class="ml-2 px-2 py-1 rounded {{ $this->getPaymentMethodColor($expense->payment_method) }}">{{ $expense->payment_method }}</span>
                            <span class="ml-2 px-2 py-1 rounded {{ $this->getStatusColor($expense->status) }}">{{ $expense->status }}</span>
                        </div>
                        <div>
                            <button wire:click.stop="removeExpense({{ $expense->id }})" class="text-lg text-red-500 hover:text-red-700 cursor-pointer"><i class="fa-regular fa-trash-can"></i></button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="mt-6">
                {{ $this->expenses->links() }}
            </div>
            @endif
        </div>

        @if ($modalAdd)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
            <div class="bg-white p-6 rounded shadow-lg w-96">
                <h2 class="font-bold pb-5">Adicionar despesa</h2>
                <form wire:submit.prevent="addExpense" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Data</label>
                        <input type="date" wire:model="date" class="w-full border rounded px-2 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Descrição</label>
                        <input type="text" wire:model="description" class="w-full border rounded px-2 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Valor</label>
                        <input type="number" step="0.01" wire:model="value" class="w-full border rounded px-2 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Tipo</label>
                        <select name="type" id="type" wire:model="type" class="w-full border rounded px-2 py-2">
                            <option value="">Selecione o tipo</option>
                            <option value="fixa">Despesa Fixa</option>
                            <option value="variavel">Despesa Variável</option>
                            <option value="parcelada">Despesa Parcelada</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Qtd parcelas</label>
                        <input type="number" wire:model="installments" id="installments" class="w-full border rounded px-2 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Forma de pagamento</label>
                        <select name="payment_method" id="payment_method" wire:model="payment_method" class="w-full border rounded px-2 py-2">
                            <option value="">Selecione o método</option>
                            <option value="credito">Crédito</option>
                            <option value="debito">Débito</option>
                            <option value="pix">Pix</option>
                            <option value="dinheiro">Dinheiro</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Status</label>
                        <select name="status" id="status" wire:model="status" class="w-full border rounded px-2 py-2">
                            <option value="">Selecione um status</option>
                            <option value="paga">Paga</option>
                            <option value="a pagar">À pagar</option>
                        </select>
                    </div>

                    <div>
                        <button type="submit" class="btn text-white p-1 rounded bg-purple-900 hover:bg-purple-600 cursor-pointer">Salvar</button>
                        <button type="button" wire:click="closeAddExpense" class="btn text-white p-1 rounded bg-gray-600 hover:bg-gray-400 cursor-pointer">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        @if ($modalImport)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
            <div class="bg-white p-6 rounded shadow-lg w-96">
                <input type="file" wire:model="file" class="file:mr-4 file:rounded-full file:border-0 file:bg-violet-500 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-violet-800 mb-2">

                <button wire:click="importExpenses"
                    class="btn text-white bg-green-800 rounded p-1 hover:bg-green-600">
                    Confirmar Importação
                </button>
                <button type="button" wire:click="closeImport" class="btn text-white p-1 rounded bg-gray-600 hover:bg-gray-400 cursor-pointer">Cancelar</button>
            </div>
        </div>
        @endif

        @if ($modalEdit)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
            <div class="bg-white p-6 rounded shadow-lg w-96">
                <h2 class="font-bold pb-5">Editar despesa</h2>
                <form wire:submit.prevent="editExpense" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Data</label>
                        <input type="date" wire:model="date" class="w-full border rounded px-2 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Descrição</label>
                        <input type="text" wire:model="description" class="w-full border rounded px-2 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Valor</label>
                        <input type="number" step="0.01" wire:model="value" class="w-full border rounded px-2 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Tipo</label>
                        <select type="text" wire:model="type" class="w-full border rounded px-2 py-2">
                            <option value="">Selecione o tipo</option>
                            <option value="fixa">Despesa Fixa</option>
                            <option value="variavel">Despesa Variável</option>
                            <option value="parcelada">Despesa Parcelada</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Status</label>
                        <select type="text" wire:model="status" class="w-full border rounded px-2 py-2">
                            <option value="">Selecione o status</option>
                            <option value="paga">Paga</option>
                            <option value="a pagar">A pagar</option>
                        </select>
                    </div>

                    <div>
                        <button type="submit" class="btn text-white p-1 rounded bg-purple-900 hover:bg-purple-600 cursor-pointer">Salvar</button>
                        <button type="button" wire:click="closeEditModal" class="btn text-white p-1 rounded bg-gray-600 hover:bg-gray-400 cursor-pointer">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
        @endif
    </div>
</div>