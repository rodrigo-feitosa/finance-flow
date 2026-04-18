<?php

use Livewire\Component;
use App\Models\Revenue;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Carbon\Carbon;

new #[Layout('layouts.app'), Title('Receitas')] class extends Component
{
    use WithFileUploads, WithPagination;

    public $file;

    public $modalAdd = false;
    public $modalImport = false;
    public $modalEdit = false;

    public $date;
    public $description;
    public $value;
    public $status;

    public $filterStatus;
    public $filterDateStart;
    public $filterDateEnd;

    public $editingRevenueId;

    public function getRevenuesProperty()
    {
        if (!auth()->check()) {
            return collect();
        }

        $query = Revenue::where('user', auth()->id());

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
        $this->getRevenues([
            'status' => $this->filterStatus,
            'date_start' => $this->filterDateStart,
            'date_end' => $this->filterDateEnd,
        ]);
    }

    public function showAddRevenue()
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $this->modalAdd = true;
    }

    public function closeAddRevenue()
    {
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


    public function showEditModal($idRevenue)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $this->modalEdit = true;

        $revenue = Revenue::where('id', $idRevenue)
            ->where('user', auth()->id())
            ->firstOrFail();

        $this->editingRevenueId = $revenue->id;

        $this->date = $revenue->date;
        $this->description = $revenue->description;
        $this->value = $revenue->value;
        $this->status = $revenue->status;
    }

    public function closeEditModal()
    {
        $this->modalEdit = false;
    }

    public function addRevenue()
    {
        // if (!auth()->check()) {
        //     return redirect()->route('login');
        // }

        Revenue::create([
            'user' => auth()->id(),
            'date' => $this->date,
            'description' => $this->description,
            'value' => $this->value,
            'status' => $this->status,
        ]);

        $this->closeAddRevenue();
        $this->dispatch('toast', message: 'Receita adicionada com sucesso!', type: 'success');
    }

    public function importRevenues()
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
            $date = Carbon::createFromFormat('d/m/Y', $row[0])->format('Y-m-d');

            if (count($row) < 4 || empty($row[3])) {
                continue;
            }

            Revenue::create([
                'user' => auth()->id(),
                'date' => $date,
                'description' => $row[1],
                'value' => $row[2],
                'status' => $row[3],
            ]);

            $count++;
        }

        fclose($file);

        $this->closeImport();

        $this->dispatch('toast', message: $count . 'receitas importadas com sucesso!', type: 'success');
    }

    public function exportRevenues()
    {
        $fileName = 'revenues.csv';
        $path = storage_path('app/public/' . $fileName);

        $file = fopen($path, 'w');

        fputcsv($file, ['Data', 'Descrição', 'Valor', 'Tipo', 'Categoria', 'Status']);

        $revenues = Revenue::where('user', auth()->id())->get();

        foreach ($revenues as $revenue) {
            fputcsv($file, [
                $revenue->date,
                $revenue->description,
                $revenue->value,
                $revenue->status,
            ]);
        }

        fclose($file);

        return Storage::disk('public')->download($fileName);
    }

    public function removeRevenue($id)
    {
        Revenue::where('id', $id)
            ->where('user', auth()->id())
            ->delete();
        
        $this->dispatch('toast', message: 'Receita removida com sucesso', type: 'success');
    }

    public function editRevenue()
    {
        Revenue::where('id', $this->editingRevenueId)
            ->where('user', auth()->id())
            ->update([
                'date' => $this->date,
                'description' => $this->description,
                'value' => $this->value,
                'status' => $this->status,
            ]);

        $this->closeEditModal();
        $this->dispatch('toast', message: 'Modificações salvas com sucesso!', type: 'success');
    }

    public function getStatusColor($status)
    {
        return match ($status) {
            'recebida' => 'bg-green-300 text-green-900 shadow-sm outline-1 outline-green-400',
            'a receber' => 'bg-red-300 text-red-900 shadow-sm outline-1 outline-red-400',
        };
    }
};
?>

<div class="dark:text-white">
    @if (session()->has('message'))
    <div class="bg-green-500 text-white p-2 text-center mt-2">
        {{ session('message') }}
    </div>
    @endif
    <h1 class="text-3xl font-bold text-center pt-10">Bem-vindo ao FinanceFlow</h1>
    <p class="text-center mt-4 text-gray-600 dark:text-gray-300">Gerencie suas finanças pessoais de forma fácil e eficiente.</p>

    <div class="text-center gap-1">
        <button
            wire:click="showAddRevenue"
            class="btn w-24 text-white bg-blue-800 rounded p-1 hover:bg-blue-600 cursor-pointer">
            Adicionar
        </button>
        <button
            wire:click="showImport"
            class="btn w-24 text-white bg-fuchsia-800 rounded p-1 hover:bg-fuchsia-600 cursor-pointer">
            Importar
        </button>
        <button
            wire:click="exportRevenues"
            class="btn w-24 text-white bg-emerald-800 rounded p-1 hover:bg-emerald-600 cursor-pointer">
            Exportar
        </button>
    </div>

    <div class="mx-auto max-w-5xl px-4">
        @if ($this->revenues->isEmpty())
        <p class="mt-6 text-gray-600">Nenhuma receita registrada. Adicione uma nova receita para começar a gerenciar suas finanças.</p>
        @else
        <div>
            <div class="flex flex-wrap gap-2 mt-4 justify-center">
                <select wire:model="filterStatus" class="border p-1 rounded">
                    <option value="">Status</option>
                    <option value="recebida">Recebida</option>
                    <option value="a receber">A receber</option>
                </select>

                <input type="date" wire:model="filterDateStart" class="border p-1 rounded w-1/3">
                <input type="date" wire:model="filterDateEnd" class="border p-1 rounded w-1/3">

                <button wire:click="applyFilters" class="btn w-24 text-white bg-yellow-600 rounded p-1 hover:bg-yellow-800 cursor-pointer">Filtrar</button>
            </div>
            <table class="hidden md:table min-w-full text-sm mt-6 border border-white rounded-lg overflow-hidden shadow-xl shadow-purple-600 bg-[#1A1233]">
                <thead class="bg-violet-800 dark:bg-[#0B0618] text-white">
                    <tr>
                        <th class="border dark:border-white p-2 w-1/9">Data</th>
                        <th class="border dark:border-white p-2 w-1/2">Descrição</th>
                        <th class="border dark:border-white p-2 w-1/6">Valor</th>
                        <th class="border dark:border-white p-2 w-1/9">Status</th>
                        <th class="border dark:border-white p-2 w-1/12"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($this->revenues as $revenue)
                    <tr wire:click="showEditModal({{ $revenue->id }})" 
                    <tr wire:click="showEditModal({{ $revenue->id }})"
                        class="odd:bg-white even:bg-gray-100 hover:bg-violet-200 
                        dark:odd:bg-[#1A1233] dark:even:bg-[#21184A] dark:hover:bg-[#2A1F5E] transition cursor-pointer">
                        <td class="border dark:border-white p-2">{{ Carbon::parse($revenue->date)->format('d/m/Y') }}</td>
                        <td class="border dark:border-white p-2">{{ $revenue->description }}</td>
                        <td class="border dark:border-white p-2">R$ {{ number_format($revenue->value, 2, ',', '.') }}</td>
                        <td class="border dark:border-white p-2">
                            <p class="rounded-xl p-1 {{ $this->getStatusColor($revenue->status) }}">
                                {{ $revenue->status }}
                            </p>
                        </td>
                        <td class="border dark:border-white p-2">
                            <button wire:click.stop="removeRevenue({{ $revenue->id }})" class="btn text-white bg-red-800 rounded p-1 hover:bg-red-600 cursor-pointer">Excluir</button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="md:hidden mt-4 space-y-3">
                @foreach ($this->revenues as $revenue)
                <div wire:click="showEditModal({{ $revenue->id }})" class="bg-[#0B0618] p-3 rounded shadow-xs shadow-gray-500">
                    <div class="flex justify-between mb-2">
                        <span>{{ $revenue->description }}</span>
                        <span>R$ {{ number_format($revenue->value, 2, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm text-gray-500">
                        <div>
                            <span>{{ Carbon::parse($revenue->date)->format('d/m/Y') }}</span>
                            <span class="ml-2 px-2 py-1 rounded {{ $this->getStatusColor($revenue->status) }}">{{ $revenue->status }}</span>
                        </div>
                        <div>
                            <button wire:click.stop="removeRevenue({{ $revenue->id }})" class="text-lg text-red-500 hover:text-red-700 cursor-pointer"><i class="fa-regular fa-trash-can"></i></button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="mt-6">
                {{ $this->revenues->links() }}
            </div>
            @endif
        </div>

        @if ($modalAdd)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
            <div class="bg-white dark:bg-[#0B0618] p-6 rounded shadow-lg w-96">
                <h2 class="font-bold pb-5">Adicionar Receita</h2>
                <form wire:submit.prevent="addRevenue" class="space-y-4">
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
                        <label class="block text-sm font-medium mb-1">Status</label>
                        <select name="status" id="status" wire:model="status" class="w-full border rounded px-2 py-2">
                            <option value="">Selecione um status</option>
                            <option value="recebida">Recebida</option>
                            <option value="a receber">À receber</option>
                        </select>
                    </div>

                    <div>
                        <button type="submit" class="btn text-white p-1 rounded bg-purple-900 hover:bg-purple-600 cursor-pointer">Salvar</button>
                        <button type="button" wire:click="closeAddRevenue" class="btn text-white p-1 rounded bg-gray-600 hover:bg-gray-400 cursor-pointer">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        @if ($modalImport)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
            <div class="bg-white dark:bg-[#0B0618] p-6 rounded shadow-lg w-96">
                <input type="file" wire:model="file" class="file:mr-4 file:rounded-full file:border-0 file:bg-violet-500 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-violet-800 mb-2">

                <button wire:click="importRevenues"
                    class="btn text-white bg-green-800 rounded p-1 hover:bg-green-600">
                    Confirmar Importação
                </button>
                <button type="button" wire:click="closeImport" class="btn text-white p-1 rounded bg-gray-600 hover:bg-gray-400 cursor-pointer">Cancelar</button>
            </div>
        </div>
        @endif


        @if ($modalEdit)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
            <div class="bg-white dark:bg-[#0B0618] p-6 rounded shadow-lg w-96">
                <h2 class="font-bold pb-5">Editar receita</h2>
                <form wire:submit.prevent="editRevenue" class="space-y-4">
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
                        <label class="block text-sm font-medium mb-1">Status</label>
                        <select name="status" id="status" wire:model="status" class="w-full border rounded px-2 py-2">
                            <option value="">Selecione um status</option>
                            <option value="recebida">Recebida</option>
                            <option value="a receber">À receber</option>
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