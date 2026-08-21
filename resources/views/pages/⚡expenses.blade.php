<?php

use Livewire\Component;
use App\Models\Expense;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.app'), Title('Despesas')] class extends Component
{
    use WithFileUploads, WithPagination;

    public $file;

    public $modalAdd = false;
    public $modalImport = false;
    public $modalEdit = false;
    public $modalBulkUpdate = false;

    public $date;
    public $description;
    public $value;
    public $type;
    public $payment_method;
    public $status;

    public $filterType;
    public $filterPaymentMethod;
    public $filterStatus = 'a pagar';
    public $filterDateStart;
    public $filterDateEnd;
    public $filterDescription;

    public $editingExpenseId;
    public array $selectedExpenseIds = [];
    public $bulkUpdateField = 'status';
    public $bulkUpdateValue = 'paga';

    public $installments = 1;

    public function getExpensesProperty()
    {
        if (!auth()->check()) {
            return collect();
        }

        $query = Expense::where('user', auth()->id());

        if ($this->filterDescription) {
            $query->where('description','like', '%' . $this->filterDescription . '%');
        }

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
            'description' => $this->filterDescription,
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

        $expense = Expense::where('id', $idExpense)
            ->where('user', auth()->id())
            ->firstOrFail();

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
                    'date' => Carbon::parse($this->date)->addMonths($i),
                    'description' => $this->description . ' (' . ($i + 1) . '/' . $installments . ')',
                    'value' => $this->value,
                    'type' => 'parcelada',
                    'payment_method' => $this->payment_method,
                    'status' => $this->status,
                ]);
            }
        }

        $this->reset(['installments']);
        $this->closeAddExpense();
        $this->dispatch('toast', message: 'Despesa adicionada com sucesso!', type: 'success');
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

            if (count($row) < 6 || empty($row[4])) {
                continue;
            }

            $date = $this->parseDate($row[0]);

            if (!$date) {
                continue; // ignora datas inválidas
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

        $this->dispatch('toast', message: $count . ' despesas importadas com sucesso!', type: 'success');
    }

    /**
     * Converte qualquer formato comum de data para Y-m-d
     */
    private function parseDate($value)
    {
        try {
            $formats = [
                'd/m/Y',
                'd-m-Y',
                'Y-m-d',
                'Y/m/d',
                'm/d/Y',
                'm-d-Y',
            ];

            foreach ($formats as $format) {
                try {
                    return Carbon::createFromFormat($format, $value)->format('Y-m-d');
                } catch (\Exception $e) {
                    // formato não corresponde, tenta próximo
                }
            }

            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    public function exportExpenses()
    {
        $fileName = 'expenses.csv';
        $path = storage_path('app/public/' . $fileName);

        $file = fopen($path, 'w');

        fputcsv($file, ['Data', 'Descrição', 'Valor', 'Tipo', 'Pagamento', 'Status']);

        $this->reset();

        $expenses = Expense::where('user', auth()->id())->get();

        foreach ($expenses as $expense) {
            fputcsv($file, [
                $expense->date,
                $expense->description,
                $expense->value,
                $expense->type,
                $expense->payment_method,
                $expense->status,
            ]);
        }

        fclose($file);

        return Storage::disk('public')->download($fileName);
    }


    public function removeExpense($id)
    {
        Expense::where('id', $id)
            ->where('user', auth()->id())
            ->delete();

        $this->dispatch('toast', message: 'Despesa removida com sucesso!', type: 'success');
    }

    public function editExpense()
    {
        Expense::where('id', $this->editingExpenseId)
            ->where('user', auth()->id())
            ->update([
                'date' => $this->date,
                'description' => $this->description,
                'value' => $this->value,
                'type' => $this->type,
                'payment_method' => $this->payment_method,
                'status' => $this->status,
            ]);

        $this->closeEditModal();
        $this->dispatch('toast', message: 'Modificações salvas com sucesso!', type: 'success');
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

    public function showBulkUpdateModal()
    {
        if (empty($this->selectedExpenseIds)) {
            $this->dispatch('toast', message: 'Selecione ao menos uma despesa para atualizar.', type: 'warning');
            return;
        }

        $this->modalBulkUpdate = true;
    }

    public function closeBulkUpdateModal()
    {
        $this->modalBulkUpdate = false;
        $this->resetValidation();
    }

    public function updatedBulkUpdateField()
    {
        $this->bulkUpdateValue = '';
    }

    public function bulkUpdate()
    {
        $allowedFields = ['date', 'description', 'type', 'payment_method', 'status'];

        if (empty($this->selectedExpenseIds)) {
            $this->dispatch('toast', message: 'Selecione ao menos uma despesa para atualizar.', type: 'warning');
            return;
        }

        if (!in_array($this->bulkUpdateField, $allowedFields, true) || $this->bulkUpdateValue === '') {
            $this->addError('bulkUpdateValue', 'Selecione o campo e informe o novo valor.');
            return;
        }

        Expense::whereIn('id', $this->selectedExpenseIds)
            ->where('user', auth()->id())
            ->update([$this->bulkUpdateField => $this->bulkUpdateValue]);

        $this->selectedExpenseIds = [];
        $this->closeBulkUpdateModal();
        $this->dispatch('toast', message: 'Despesas atualizadas com sucesso!', type: 'success');
    }
};
?>

<div class="page-container dark:text-white">
    @if (session()->has('message'))
    <div class="bg-green-500 text-white p-2 text-center mt-2">
        {{ session('message') }}
    </div>
    @endif
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"><div><span class="badge bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-500/10 dark:text-rose-300">Lançamentos</span><h1 class="page-heading mt-3">Despesas</h1><p class="page-subtitle">Acompanhe e organize as saídas do seu orçamento.</p></div>

    <div class="flex flex-wrap gap-2 sm:justify-end">
        <button
            wire:click="showAddExpense"
            class="btn btn-primary"><i class="fa-solid fa-plus"></i>Adicionar
        </button>
        <button
            wire:click="showImport"
            class="btn btn-secondary"><i class="fa-solid fa-file-import"></i>Importar
        </button>
        <button
            wire:click="exportExpenses"
            class="btn btn-success"><i class="fa-solid fa-file-export"></i>Exportar
        </button>
        <button
            wire:click="showBulkUpdateModal"
            class="btn btn-info"><i class="fa-solid fa-check"></i>Atualizar em massa
    </div>
    </div>

    <div class="panel overflow-hidden">
        <div class="flex flex-wrap gap-2 border-b border-slate-100 p-4 dark:border-slate-800">
            <input type="text" wire:model="filterDescription" placeholder="Filtrar por descrição" class="min-w-48 flex-1">

            <select wire:model="filterType" class="border p-1 rounded">
                <option class="dark:text-white" value="">Tipo</option>
                <option class="dark:text-white" value="fixa">Fixa</option>
                <option class="dark:text-white" value="variavel">Variável</option>
                <option class="dark:text-white" value="parcelada">Parcelada</option>
            </select>

            <select wire:model="filterPaymentMethod" class="border p-1 rounded">
                <option class="dark:text-white" value="">Pagamento</option>
                <option class="dark:text-white" value="credito">Crédito</option>
                <option class="dark:text-white" value="debito">Débito</option>
                <option class="dark:text-white" value="pix">Pix</option>
                <option class="dark:text-white" value="dinheiro">Dinheiro</option>
            </select>

            <select wire:model="filterStatus" class="border p-1 rounded">
                <option class="dark:text-white" value="">Status</option>
                <option class="dark:text-white" value="paga">Paga</option>
                <option class="dark:text-white" value="a pagar">A pagar</option>
            </select>

            <input type="date" wire:model="filterDateStart" class="border p-1 rounded">
            <input type="date" wire:model="filterDateEnd" class="border p-1 rounded">

            <button wire:click="applyFilters" class="btn w-24 text-white bg-yellow-600 rounded p-1 hover:bg-yellow-800 cursor-pointer">Filtrar</button>
        </div>
        @if ($this->expenses->isEmpty())
        <p class="m-4 rounded-lg border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">Nenhuma despesa registrada. Adicione uma nova despesa para começar.</p>
        @else
        <div class="overflow-x-auto">
            <table class="data-table hidden min-w-full md:table">
                <thead>
                    <tr>
                        <th class="border dark:border-white p-2 w-10">
                            <span class="sr-only">Selecionar</span>
                        </th>
                        <th class="border dark:border-white p-2 w-1/9">Data</th>
                        <th class="border dark:border-white p-2 w-1/3">Descrição</th>
                        <th class="border dark:border-white p-2 w-1/9">Valor</th>
                        <th class="border dark:border-white p-2 w-1/9">Tipo</th>
                        <th class="border dark:border-white p-2 w-1/6">Forma de pagamento</th>
                        <th class="border dark:border-white p-2 w-1/9">Status</th>
                        <th class="border dark:border-white p-2 w-1/9"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($this->expenses as $expense)
                    <tr wire:click="showEditModal({{ $expense->id }})"
                        class="cursor-pointer transition">
                        <td class="border dark:border-white p-2 text-center">
                            <input
                                type="checkbox"
                                value="{{ $expense->id }}"
                                wire:model="selectedExpenseIds"
                                wire:click.stop
                                aria-label="Selecionar despesa {{ $expense->description }}">
                        </td>
                        <td class="border dark:border-white p-2">{{ Carbon::parse($expense->date)->format('d/m/Y') }}</td>
                        <td class="border dark:border-white p-2">{{ $expense->description }}</td>
                        <td class="border dark:border-white p-2">R$ {{ number_format($expense->value, 2, ',', '.') }}</td>
                        <td class="border dark:border-white p-2">
                            <p class="rounded-xl p-1 {{ $this->getTypeColor($expense->type) }}">{{ $expense->type }}</p>
                        </td>
                        <td class="border dark:border-white p-2">
                            <p class="rounded-xl p-1 {{ $this->getPaymentMethodColor($expense->payment_method) }}">{{ $expense->payment_method }}</p>
                        </td>
                        <td class="border dark:border-white p-2">
                            <p class="rounded-xl p-1 {{ $this->getStatusColor($expense->status) }}">{{ $expense->status }}</p>
                        </td>
                        <td class="border dark:border-white p-2">
                            <button wire:click.stop="removeExpense({{ $expense->id }})" class="btn text-white bg-red-800 rounded p-1 hover:bg-red-600 cursor-pointer">Excluir</button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="md:hidden mt-4 space-y-3">
                @foreach ($this->expenses as $expense)
                <div wire:click="showEditModal({{ $expense->id }})" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <input
                                type="checkbox"
                                value="{{ $expense->id }}"
                                wire:model="selectedExpenseIds"
                                wire:click.stop
                                aria-label="Selecionar despesa {{ $expense->description }}">
                            <span>{{ $expense->description }}</span>
                        </div>
                        <span>R$ {{ number_format($expense->value, 2, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm text-gray-500">
                        <div>
                            <span>{{ Carbon::parse($expense->date)->format('d/m/Y') }}</span>
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
        <div class="modal-backdrop">
            <div class="modal-card">
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
                            <option class="dark:text-white" value="">Selecione o tipo</option>
                            <option class="dark:text-white" value="fixa">Despesa Fixa</option>
                            <option class="dark:text-white" value="variavel">Despesa Variável</option>
                            <option class="dark:text-white" value="parcelada">Despesa Parcelada</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Qtd parcelas</label>
                        <input type="number" wire:model="installments" id="installments" class="w-full border rounded px-2 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Forma de pagamento</label>
                        <select name="payment_method" id="payment_method" wire:model="payment_method" class="w-full border rounded px-2 py-2">
                            <option class="dark:text-white" value="">Selecione o método</option>
                            <option class="dark:text-white" value="credito">Crédito</option>
                            <option class="dark:text-white" value="debito">Débito</option>
                            <option class="dark:text-white" value="pix">Pix</option>
                            <option class="dark:text-white" value="dinheiro">Dinheiro</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Status</label>
                        <select name="status" id="status" wire:model="status" class="w-full border rounded px-2 py-2">
                            <option class="dark:text-white" value="">Selecione um status</option>
                            <option class="dark:text-white" value="paga">Paga</option>
                            <option class="dark:text-white" value="a pagar">À pagar</option>
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
        <div class="modal-backdrop">
            <div class="modal-card">
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
        <div class="modal-backdrop">
            <div class="modal-card">
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
                            <option class="dark:text-white" value="">Selecione o tipo</option>
                            <option class="dark:text-white" value="fixa">Despesa Fixa</option>
                            <option class="dark:text-white" value="variavel">Despesa Variável</option>
                            <option class="dark:text-white" value="parcelada">Despesa Parcelada</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Status</label>
                        <select type="text" wire:model="status" class="w-full border rounded px-2 py-2">
                            <option class="dark:text-white" value="">Selecione o status</option>
                            <option class="dark:text-white" value="paga">Paga</option>
                            <option class="dark:text-white" value="a pagar">A pagar</option>
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

        @if ($modalBulkUpdate)
        <div class="modal-backdrop">
            <div class="modal-card">
                <h2 class="font-bold pb-5">Atualizar despesas</h2>
                <form wire:submit.prevent="bulkUpdate" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Campo</label>
                        <select wire:model.live="bulkUpdateField" class="w-full border rounded px-2 py-2">
                            <option class="dark:text-white" value="date">Data</option>
                            <option class="dark:text-white" value="description">Descrição</option>
                            <option class="dark:text-white" value="type">Tipo</option>
                            <option class="dark:text-white" value="payment_method">Forma de pagamento</option>
                            <option class="dark:text-white" value="status">Status</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Novo valor</label>
                        @if ($bulkUpdateField === 'type')
                        <select wire:model="bulkUpdateValue" class="w-full border rounded px-2 py-2">
                            <option value="">Selecione o tipo</option>
                            <option value="fixa">Despesa fixa</option>
                            <option value="variavel">Despesa variável</option>
                            <option value="parcelada">Despesa parcelada</option>
                        </select>
                        @elseif ($bulkUpdateField === 'payment_method')
                        <select wire:model="bulkUpdateValue" class="w-full border rounded px-2 py-2">
                            <option value="">Selecione a forma de pagamento</option>
                            <option value="credito">Crédito</option>
                            <option value="debito">Débito</option>
                            <option value="pix">Pix</option>
                            <option value="dinheiro">Dinheiro</option>
                        </select>
                        @elseif ($bulkUpdateField === 'status')
                        <select wire:model="bulkUpdateValue" class="w-full border rounded px-2 py-2">
                            <option value="">Selecione o status</option>
                            <option value="paga">Paga</option>
                            <option value="a pagar">A pagar</option>
                        </select>
                        @else
                        <input type="{{ $bulkUpdateField === 'date' ? 'date' : 'text' }}" wire:model="bulkUpdateValue" class="w-full border rounded px-2 py-2">
                        @endif
                        @error('bulkUpdateValue')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <button type="submit" class="btn text-white p-1 rounded bg-purple-900 hover:bg-purple-600 cursor-pointer">Salvar</button>
                        <button type="button" wire:click="closeBulkUpdateModal" class="btn text-white p-1 rounded bg-gray-600 hover:bg-gray-400 cursor-pointer">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
        @endif
    </div>
</div>
