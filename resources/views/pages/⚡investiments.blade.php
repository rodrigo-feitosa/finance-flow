<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Models\Investment;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Carbon\Carbon;

new #[Layout('layouts.app'), Title('Investimentos')] class extends Component
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
    public $category;
    public $institution;
    public $status;
    public $is_initial;

    public $filterType;
    public $filterInstitution;
    public $filterStatus;
    public $filterCategory;
    public $filterDateStart;
    public $filterDateEnd;
    public $filterDescription;

    public $editingInvestmentId;

    public function updated()
    {
        $this->resetPage();
    }

    public function getInvestmentsProperty()
    {
        if (!auth()->check()) {
            return [];
        }

        $query = Investment::where('user', auth()->id());

        if ($this->filterDescription) {
            $query->where('description', 'like', '%' . $this->filterDescription . '%');
        }

        if ($this->filterType) {
            $query->where('type', $this->filterType);
        }

        if ($this->filterInstitution) {
            $query->where('institution', $this->filterInstitution);
        }

        if ($this->filterCategory) {
            $query->where('category', $this->filterCategory);
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
        $this->getInvestmentsProperty([
            'description' => $this->filterDescription,
            'type' => $this->filterType,
            'institution' => $this->filterInstitution,
            'category' => $this->filterCategory,
            'status' => $this->filterStatus,
            'date_start' => $this->filterDateStart,
            'date_end' => $this->filterDateEnd,
        ]);
    }

    public function showAddInvestment()
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $this->modalAdd = true;
    }

    public function closeAddInvestment()
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

    public function showEditModal($idInvestment)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $this->modalEdit = true;

        $investment = Investment::where('id', $idInvestment)
            ->where('user', auth()->id())
            ->firstOrFail();

        $this->editingInvestmentId = $investment->id;

        $this->date = $investment->date;
        $this->description = $investment->description;
        $this->value = $investment->value;
        $this->type = $investment->type;
        $this->category = $investment->category;
        $this->institution = $investment->institution;
        $this->status = $investment->status;
        $this->is_initial = $investment->is_initial;
    }

    public function closeEditModal()
    {
        $this->modalEdit = false;
    }

    public function addInvestment()
    {
        // if (!auth()->check()) {
        //     return redirect()->route('login');
        // }

        Investment::create([
            'user' => auth()->id(),
            'date' => $this->date,
            'description' => $this->description,
            'value' => $this->value,
            'type' => $this->type,
            'institution' => $this->institution,
            'status' => $this->status,
            'is_initial' => $this->is_initial,
        ]);

        $this->closeAddInvestment();
        $this->dispatch('toast', message: 'Investimento adicionado com sucesso!', type: 'success');
    }

    public function importInvestments()
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
            $date = $this->parseDate($row[0]);

            if (!$date) {
                continue;
            }

            if (count($row) < 6 || empty($row[4])) {
                continue;
            }

            Investment::create([
                'user' => auth()->id(),
                'date' => $date,
                'description' => $row[1],
                'value' => (float) $row[2],
                'type' => $row[3],
                'category' => $row[4],
                'institution' => $row[5],
                'status' => $row[6] ?? null,
            ]);

            $count++;
        }

        fclose($file);

        $this->closeImport();

        $this->dispatch('toast', message: $count . 'investimentos importados com sucesso!', type: 'success');
    }

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
                    // tenta próximo
                }
            }

            // fallback inteligente
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    public function exportInvestments()
    {
        $fileName = 'investments.csv';
        $path = storage_path('app/public/' . $fileName);

        $file = fopen($path, 'w');

        fputcsv($file, ['Data', 'Descrição', 'Valor', 'Tipo', 'Categoria', 'Status']);

        $investments = Investment::where('user', auth()->id())->get();

        foreach ($investments as $investment) {
            fputcsv($file, [
                $investment->date,
                $investment->description,
                $investment->type,
                $investment->category,
                $investment->value,
                $investment->institution,
                $investment->status,
            ]);
        }

        fclose($file);

        return Storage::disk('public')->download($fileName);
    }

    public function removeInvestment($id)
    {
        Investment::where('id', $id)
            ->where('user', auth()->id())
            ->delete();

        $this->dispatch('toast', message: 'Investimento removido com sucesso!', type: 'success');
    }

    public function editInvestment()
    {
        Investment::where('id', $this->editingInvestmentId)
            ->where('user', auth()->id())
            ->update([
                'date' => $this->date,
                'description' => $this->description,
                'value' => $this->value,
                'type' => $this->type,
                'category' => $this->category,
                'institution' => $this->institution,
                'status' => $this->status,
                'is_initial' => $this->is_initial,
            ]);

        $this->closeEditModal();
        $this->dispatch('toast', message: 'Modificações salvas com sucesso!', type: 'success');
    }

    public function getTypeColor($type)
    {
        return match ($type) {
            'renda fixa' => 'bg-purple-300 text-purple-900 shadow-sm outline-1 outline-purple-400',
            'renda variavel' => 'bg-yellow-300 text-yellow-900 shadow-sm outline-1 outline-yellow-400',
            'cripto' => 'bg-rose-300 text-rose-800 shadow-sm outline-1 outline-rose-400',
            'outros' => 'bg-gray-300 text-gray-800 shadow-sm outline-1 outline-gray-400',
        };
    }

    public function getCategoryColor($category)
    {
        return match ($category) {
            'tesouro' => 'bg-blue-300 text-blue-900 shadow-sm outline-1 outline-blue-400',
            'CDB' => 'bg-green-300 text-green-900 shadow-sm outline-1 outline-green-400',
            'ações' => 'bg-emerald-300 text-emerald-900 shadow-sm outline-1 outline-emerald-400',
            'FII' => 'bg-yellow-300 text-gray-800 shadow-sm outline-1 outline-yellow-400',
            'outros' => 'bg-gray-300 text-gray-800 shadow-sm outline-1 outline-gray-400',
        };
    }

    public function getStatusColor($status)
    {
        return match ($status) {
            'ativo' => 'bg-green-300 text-green-900 shadow-sm outline-1 outline-green-400',
            'inativo' => 'bg-red-300 text-red-900 shadow-sm outline-1 outline-red-400',
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
            wire:click="showAddInvestment"
            class="btn w-24 text-white bg-blue-800 rounded p-1 hover:bg-blue-600 cursor-pointer">
            Adicionar
        </button>
        <button
            wire:click="showImport"
            class="btn w-24 text-white bg-fuchsia-800 rounded p-1 hover:bg-fuchsia-600 cursor-pointer">
            Importar
        </button>
        <button
            wire:click="exportInvestments"
            class="btn w-24 text-white bg-emerald-800 rounded p-1 hover:bg-green-600 cursor-pointer">
            Exportar
        </button>
    </div>

    <div class="mx-auto max-w-6xl px-4">
        @if ($this->investments->isEmpty())
        <p class="mt-6 text-gray-600">Nenhum investimento registrada. Adicione um novo investimento para começar a gerenciar suas finanças.</p>
        @else
        <div class="flex flex-wrap gap-2 mt-4 justify-center">
            <input wire:model="filterDescription" type="text" placeholder="Filtrar por descrição" class="border p-1 rounded">
            <select wire:model="filterType" class="border p-1 rounded">
                <option class="dark:text-black" value="">Tipo</option>
                <option class="dark:text-black" value="renda fixa">Renda Fixa</option>
                <option class="dark:text-black" value="renda variavel">Renda Variável</option>
                <option class="dark:text-black" value="cripto">Criptomoedas</option>
                <option class="dark:text-black" value="outros">Outros</option>
            </select>

            <select wire:model="filterCategory" class="border p-1 rounded">
                <option class="dark:text-black" value="">Categoria</option>
                <option class="dark:text-black" value="cdb">CDB</option>
                <option class="dark:text-black" value="ações">Ações</option>
                <option class="dark:text-black" value="FII">FII</option>
                <option class="dark:text-black" value="outros">Outros</option>
            </select>

            <select wire:model="filterInstitution" class="border p-1 rounded">
                <option class="dark:text-black" value="">Instituição</option>
                @foreach ($this->investments->pluck('institution')->unique() as $institution)
                <option class="dark:text-black" value="{{ $institution }}">{{ $institution }}</option>
                @endforeach
            </select>

            <input type="date" wire:model="filterDateStart" class="border p-1 rounded">
            <input type="date" wire:model="filterDateEnd" class="border p-1 rounded">

            <button wire:click="applyFilters" class="btn w-24 text-white bg-yellow-600 rounded p-1 hover:bg-yellow-800 cursor-pointer">Filtrar</button>
        </div>

        <table class="hidden md:table min-w-full text-sm mt-6 border border-gray-200 rounded-lg overflow-hidden shadow-xl shadow-purple-600">
            <thead class="bg-violet-800 dark:bg-[#0B0618] text-white">
                <tr>
                    <th class="border dark:border-white p-2 w-1/9">Data</th>
                    <th class="border dark:border-white p-2 w-1/4">Descrição</th>
                    <th class="border dark:border-white p-2 w-1/9">Valor</th>
                    <th class="border dark:border-white p-2 w-1/9">Tipo</th>
                    <th class="border dark:border-white p-2 w-1/9">Categoria</th>
                    <th class="border dark:border-white p-2 w-1/6">Instituição</th>
                    <th class="border dark:border-white p-2 w-1/10">Status</th>
                    <th class="border dark:border-white p-2 w-1/10"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($this->investments as $investment)
                <tr wire:click="showEditModal({{ $investment->id }})"
                    class="odd:bg-white even:bg-gray-100 hover:bg-violet-200 
                    dark:odd:bg-[#1A1233] dark:even:bg-[#21184A] dark:hover:bg-[#2A1F5E] transition cursor-pointer">
                    <td class="border dark:border-white p-2">{{ Carbon::parse($investment->date)->format('d/m/Y') }}</td>
                    <td class="border dark:border-white p-2">{{ $investment->description }}</td>
                    <td class="border dark:border-white p-2">R$ {{ number_format($investment->value, 2, ',', '.') }}</td>
                    <td class="border dark:border-white p-2">
                        <p class="rounded-xl p-1 {{ $this->getTypeColor($investment->type) }}">{{ $investment->type }}</p>
                    </td>
                    <td class="border dark:border-white p-2">
                        <p class="rounded-xl p-1 {{ $this->getCategoryColor($investment->category) }}">{{ $investment->category }}</p>
                    </td>
                    <td class="border dark:border-white p-2">
                        <p class="rounded-xl p-1">{{ $investment->institution }}</p>
                    </td>
                    <td class="border dark:border-white p-2">
                        <p class="rounded-xl p-1 {{ $this->getStatusColor($investment->status) }}">{{ $investment->status }}</p>
                    </td>
                    <td class="border dark:border-white p-2">
                        <button wire:click.stop="removeInvestment({{ $investment->id }})" class="btn text-white bg-red-800 rounded p-1 hover:bg-red-600 cursor-pointer">Excluir</button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="md:hidden mt-4 space-y-3">
            @foreach ($this->investments as $investment)
            <div wire:click="showEditModal({{ $investment->id }})" class="dark:bg-[#0B0618] bg-white p-3 rounded shadow-xs shadow-gray-500">
                <div class="flex justify-between mb-2">
                    <span>{{ $investment->description }}</span>
                    <span>R$ {{ number_format($investment->value, 2, ',', '.') }}</span>
                </div>
                <div class="flex justify-between items-center text-sm text-gray-500">
                    <div>
                        <span>{{ Carbon::parse($investment->date)->format('d/m/Y') }}</span>
                        <span class="ml-2 px-2 py-1 rounded {{ $this->getStatusColor($investment->status) }}">{{ $investment->status }}</span>
                    </div>
                    <div>
                        <button wire:click.stop="removeInvestment({{ $investment->id }})" class="text-lg text-red-500 hover:text-red-700 cursor-pointer"><i class="fa-regular fa-trash-can"></i></button>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <div class="mt-6">
            {{ $this->investments->links() }}
        </div>
        @endif
    </div>

    @if ($modalAdd)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white dark:bg-[#0B0618] p-6 rounded shadow-lg w-96">
            <h2 class="font-bold pb-5">Adicionar investimento</h2>
            <form wire:submit.prevent="addInvestment" class="space-y-4">
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
                        <option class="dark:text-black" value="">Selecione um tipo</option>
                        <option class="dark:text-black" value="renda fixa">Renda fixa</option>
                        <option class="dark:text-black" value="renda variavel">Renda Variável</option>
                        <option class="dark:text-black" value="cripto">Criptomoedas</option>
                        <option class="dark:text-black" value="outros">Outros</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Categoria</label>
                    <select type="text" wire:model="category" class="w-full border rounded px-2 py-2">
                        <option class="dark:text-black" value="">Selecione uma categoria</option>
                        <option class="dark:text-black" value="tesouro">Tesouro</option>
                        <option class="dark:text-black" value="CDB">CDB</option>
                        <option class="dark:text-black" value="ações">Ações</option>
                        <option class="dark:text-black" value="FII">FII</option>
                        <option class="dark:text-black" value="outros">Outros</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Instituição</label>
                    <input type="text" wire:model="institution" class="w-full border rounded px-2 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Status</label>
                    <select name="status" id="status" wire:model="status" class="w-full border rounded px-2 py-2">
                        <option class="dark:text-black" value="">Selecione um status</option>
                        <option class="dark:text-black" value="ativo">Ativo</option>
                        <option class="dark:text-black" value="inativo">Inativo</option>
                    </select>
                </div>
                <div>
                    <input type="radio" wire:model="is_initial" value="1">
                    <label class="text-sm font-medium mb-1">Valor pré-existente</label>
                </div>
                <div>
                    <button type="submit" class="btn text-white p-1 rounded bg-purple-900 hover:bg-purple-600 cursor-pointer">Salvar</button>
                    <button type="button" wire:click="closeAddInvestment" class="btn text-white p-1 rounded bg-gray-600 hover:bg-gray-400 cursor-pointer">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    @if ($modalImport)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white dark:bg-[#0B0618] p-6 rounded shadow-lg w-96">
            <input type="file" wire:model="file" class="file:mr-4 file:rounded-full file:border-0 file:bg-violet-500 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-violet-800 mb-2">

            <button wire:click="importInvestments"
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
            <h2 class="font-bold pb-5">Editar investimento</h2>
            <form wire:submit.prevent="editInvestment" class="space-y-4">
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
                        <option class="dark:text-black" value="">Selecione um tipo</option>
                        <option class="dark:text-black" value="renda fixa">Renda fixa</option>
                        <option class="dark:text-black" value="renda variavel">Renda Variável</option>
                        <option class="dark:text-black" value="cripto">Criptomoedas</option>
                        <option class="dark:text-black" value="outros">Outros</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Categoria</label>
                    <select type="text" wire:model="category" class="w-full border rounded px-2 py-2">
                        <option class="dark:text-black" value="">Selecione uma categoria</option>
                        <option class="dark:text-black" value="tesouro">Tesouro</option>
                        <option class="dark:text-black" value="CDB">CDB</option>
                        <option class="dark:text-black" value="ações">Ações</option>
                        <option class="dark:text-black" value="FII">FII</option>
                        <option class="dark:text-black" value="outros">Outros</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Instituição</label>
                    <input type="text" wire:model="institution" class="w-full border rounded px-2 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Status</label>
                    <select name="status" id="status" wire:model="status" class="w-full border rounded px-2 py-2">
                        <option class="dark:text-black" value="">Selecione um status</option>
                        <option class="dark:text-black" value="ativo">Ativo</option>
                        <option class="dark:text-black" value="inativo">Inativo</option>
                    </select>
                </div>
                <div>
                    <input type="radio" wire:model="is_initial" value="1">
                    <label class="text-sm font-medium mb-1">Investimento pré-existente</label>
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