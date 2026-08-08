<?php

use Livewire\Component;
use App\Models\Expense;
use App\Models\Investment;
use App\Models\Revenue;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Carbon\Carbon;

new #[Layout('layouts.app'), Title('Finance Flow')] class extends Component
{
    public function getDashboardProperty()
    {
        if (!auth()->check()) return null;

        $userId = auth()->id();
        $revenues = Revenue::where('user', $userId);
        $expenses = Expense::where('user', $userId);
        $investments = Investment::where('user', $userId);

        $received = (float) (clone $revenues)->where('status', 'recebida')->sum('value');
        $paid = (float) (clone $expenses)->where('status', 'paga')->sum('value');
        $invested = (float) (clone $investments)->sum('value');
        $pendingRevenue = (float) (clone $revenues)->where('status', 'a receber')->sum('value');
        $pendingExpense = (float) (clone $expenses)->where('status', 'a pagar')->sum('value');

        return [
            'received' => $received,
            'paid' => $paid,
            'invested' => $invested,
            'balance' => $received - $paid,
            'pending' => $pendingRevenue - $pendingExpense,
            'portfolio' => (float) Investment::where('user', $userId)->sum('value'),
            'transactionCount' => $revenues->count() + $expenses->count() + $investments->count(),
        ];
    }

    public function getMonthlyDataProperty()
    {
        if (!auth()->check()) return collect();

        $userId = auth()->id();
        $months = collect();

        Revenue::where('user', $userId)->where('status', 'recebida')->get()->each(function ($item) use ($months) {
            $key = Carbon::parse($item->date)->format('Y-m');
            $month = $months->get($key, ['label' => Carbon::parse($item->date)->translatedFormat('M'), 'received' => 0, 'paid' => 0, 'invested' => 0]);
            $month['received'] += (float) $item->value;
            $months->put($key, $month);
        });
        Expense::where('user', $userId)->where('status', 'paga')->get()->each(function ($item) use ($months) {
            $key = Carbon::parse($item->date)->format('Y-m');
            $month = $months->get($key, ['label' => Carbon::parse($item->date)->translatedFormat('M'), 'received' => 0, 'paid' => 0, 'invested' => 0]);
            $month['paid'] += (float) $item->value;
            $months->put($key, $month);
        });
        Investment::where('user', $userId)->get()->each(function ($item) use ($months) {
            $key = Carbon::parse($item->date)->format('Y-m');
            $month = $months->get($key, ['label' => Carbon::parse($item->date)->translatedFormat('M'), 'received' => 0, 'paid' => 0, 'invested' => 0]);
            $month['invested'] += (float) $item->value;
            $months->put($key, $month);
        });

        return $months->sortKeys();
    }

    public function getRecentActivityProperty()
    {
        if (!auth()->check()) return collect();

        $userId = auth()->id();
        $revenues = Revenue::where('user', $userId)->get()->map(fn($item) => ['type' => 'Receita', 'description' => $item->description, 'value' => $item->value, 'date' => $item->created_at, 'color' => 'emerald']);
        $expenses = Expense::where('user', $userId)->get()->map(fn($item) => ['type' => 'Despesa', 'description' => $item->description, 'value' => $item->value, 'date' => $item->created_at, 'color' => 'rose']);
        $investments = Investment::where('user', $userId)->get()->map(fn($item) => ['type' => 'Investimento', 'description' => $item->description, 'value' => $item->value, 'date' => $item->created_at, 'color' => 'amber']);

        return $revenues->concat($expenses)->concat($investments)->sortByDesc('date')->take(5);
    }
};
?>

<div>
    @if (!auth()->check())
    <div class="page-container flex min-h-[calc(100vh-8rem)] items-center justify-center">
        <section class="w-full max-w-3xl text-center"><span class="badge bg-indigo-50 text-indigo-700 ring-indigo-600/20 dark:bg-indigo-500/10 dark:text-indigo-300">Seu espaço financeiro</span>
            <h1 class="mt-5 text-4xl font-semibold tracking-tight text-slate-950 sm:text-5xl dark:text-white">Decisões melhores começam com clareza.</h1>
            <p class="mx-auto mt-5 max-w-xl text-base leading-7 text-slate-500 dark:text-slate-400">Organize receitas, despesas e investimentos em um único lugar, com uma experiência simples e focada.</p><a href="{{ route('login') }}" class="btn btn-primary mt-8">Entrar no FinanceFlow <i class="fa-solid fa-arrow-right"></i></a>
        </section>
    </div>
    @else
    @php
    $dashboard = $this->dashboard;
    $monthlyData = $this->monthlyData;
    $maxMonthlyValue = max(1, $monthlyData->max(fn ($month) => max($month['received'], $month['paid'] + $month['invested'])));
    @endphp
    <div class="page-container">
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div><span class="badge bg-indigo-50 text-indigo-700 ring-indigo-600/20 dark:bg-indigo-500/10 dark:text-indigo-300">Visão geral</span>
                <h1 class="page-heading mt-3">Olá, {{ auth()->user()->name }}.</h1>
                <p class="page-subtitle">Acompanhe os principais indicadores da sua vida financeira.</p>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([['Saldo geral', $dashboard['balance'], 'fa-wallet', 'indigo'], ['Receitas recebidas', $dashboard['received'], 'fa-arrow-trend-up', 'emerald'], ['Despesas pagas', $dashboard['paid'], 'fa-arrow-trend-down', 'rose'], ['Patrimônio investido', $dashboard['portfolio'], 'fa-chart-line', 'amber']] as $card)
            <div class="panel p-5">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ $card[0] }}</p><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-{{ $card[3] }}-50 text-{{ $card[3] }}-600 dark:bg-{{ $card[3] }}-500/10 dark:text-{{ $card[3] }}-400"><i class="fa-solid {{ $card[2] }}"></i></span>
                </div>
                <p class="mt-4 text-2xl font-semibold tracking-tight text-{{ $card[3] }}-600 dark:text-{{ $card[3] }}-400">R$ {{ number_format($card[1], 2, ',', '.') }}</p>
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ $card[0] === 'Saldo geral' ? 'Receitas - despesas' : 'Total acumulado' }}</p>
            </div>
            @endforeach
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-[1.5fr_1fr]">
            <section class="panel p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-semibold text-slate-950 dark:text-white">Movimentação mensal</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Histórico completo</p>
                    </div><a href="{{ route('cash-flow') }}" class="text-sm font-medium text-indigo-600 dark:text-indigo-400">Ver detalhes</a>
                </div>
                <div class="mt-8 flex h-56 items-end justify-between gap-2 overflow-x-auto sm:gap-5">@foreach ($monthlyData as $month)<div class="flex h-full min-w-10 flex-1 flex-col items-center justify-end gap-2">
                        <div class="flex h-full w-full items-end justify-center gap-1">
                            <div title="Receitas: R$ {{ number_format($month['received'], 2, ',', '.') }}" class="w-1/3 rounded-t bg-emerald-400" style="height: {{ max(4, $month['received'] / $maxMonthlyValue * 100) }}%"></div>
                            <div title="Despesas: R$ {{ number_format($month['paid'], 2, ',', '.') }}" class="w-1/3 rounded-t bg-rose-400" style="height: {{ max(4, $month['paid'] / $maxMonthlyValue * 100) }}%"></div>
                        </div><span class="text-xs capitalize text-slate-500 dark:text-slate-400">{{ $month['label'] }}</span>
                    </div>@endforeach</div>
                <div class="mt-5 flex gap-5 text-xs text-slate-500 dark:text-slate-400"><span><i class="fa-solid fa-circle mr-1 text-emerald-400"></i>Receitas</span><span><i class="fa-solid fa-circle mr-1 text-rose-400"></i>Despesas</span></div>
            </section>
            <section class="panel p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-semibold text-slate-950 dark:text-white">Atividade recente</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Últimos lançamentos</p>
                    </div><span class="badge bg-slate-100 text-slate-600 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700">{{ $dashboard['transactionCount'] }} lançamentos</span>
                </div>
                <div class="mt-5 space-y-4">@forelse ($this->recentActivity as $activity)<div class="flex items-center gap-3"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-{{ $activity['color'] }}-50 text-{{ $activity['color'] }}-600 dark:bg-{{ $activity['color'] }}-500/10 dark:text-{{ $activity['color'] }}-400"><i class="fa-solid {{ $activity['type'] === 'Receita' ? 'fa-arrow-up' : ($activity['type'] === 'Despesa' ? 'fa-arrow-down' : 'fa-chart-line') }} text-xs"></i></span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-slate-800 dark:text-slate-200">{{ $activity['description'] ?: $activity['type'] }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $activity['type'] }} · {{ Carbon::parse($activity['date'])->format('d/m/Y') }}</p>
                        </div><strong class="text-sm text-{{ $activity['color'] }}-600 dark:text-{{ $activity['color'] }}-400">R$ {{ number_format($activity['value'], 2, ',', '.') }}</strong>
                    </div>@empty<p class="py-8 text-center text-sm text-slate-500 dark:text-slate-400">Nenhum lançamento cadastrado ainda.</p>@endforelse</div>
            </section>
        </div>

        <section class="mt-6">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-semibold text-slate-950 dark:text-white">Ações rápidas</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">Mantenha seu controle em dia</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-3"><a href="{{ route('expenses') }}" class="panel flex items-center gap-3 p-4 hover:border-rose-300"><span class="flex h-10 w-10 items-center justify-center rounded-lg bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400"><i class="fa-solid fa-plus"></i></span><span><strong class="block text-sm">Nova despesa</strong><small class="text-slate-500 dark:text-slate-400">Registre uma saída</small></span></a><a href="{{ route('revenues') }}" class="panel flex items-center gap-3 p-4 hover:border-emerald-300"><span class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400"><i class="fa-solid fa-plus"></i></span><span><strong class="block text-sm">Nova receita</strong><small class="text-slate-500 dark:text-slate-400">Registre uma entrada</small></span></a><a href="{{ route('investments') }}" class="panel flex items-center gap-3 p-4 hover:border-amber-300"><span class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400"><i class="fa-solid fa-chart-line"></i></span><span><strong class="block text-sm">Novo investimento</strong><small class="text-slate-500 dark:text-slate-400">Aumente seu patrimônio</small></span></a></div>
        </section>
    </div>
    @endif
</div>