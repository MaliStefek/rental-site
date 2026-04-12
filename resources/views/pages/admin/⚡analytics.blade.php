<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\DB;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\Category;
use Carbon\Carbon;

new #[Layout('layouts.admin')] class extends Component 
{
    
    public function with(): array
    {
        $monthlyRevenue = Payment::select(
            DB::raw('sum(amount_cents) as total'),
            DB::raw("DATE_FORMAT(paid_at, '%Y-%m') as month")
        )
        ->where('amount_cents', '>', 0)
        ->where('paid_at', '>=', now()->subMonths(11)->startOfMonth())
        ->groupBy('month')
        ->orderBy('month')
        ->get()
        ->mapWithKeys(function ($item) {
            return [Carbon::parse($item->month . '-01')->format('M Y') => $item->total / 100];
        });

        $chartMonths = collect();
        for ($i = 11; $i >= 0; $i--) {
            $monthStr = now()->subMonths($i)->format('M Y');
            $chartMonths[$monthStr] = $monthlyRevenue->get($monthStr, 0);
        }

        $categoryRevenue = DB::table('rental_items')
            ->join('tools', 'rental_items.tool_id', '=', 'tools.id')
            ->join('categories', 'tools.category_id', '=', 'categories.id')
            ->join('rentals', 'rental_items.rental_id', '=', 'rentals.id')
            ->whereIn('rentals.status', ['confirmed', 'active', 'returned', 'overdue'])
            ->select('categories.name', DB::raw('SUM(rental_items.unit_price_cents * rental_items.quantity * (DATEDIFF(rentals.end_at, rentals.start_at) + 1)) as category_total'))
            ->groupBy('categories.name')
            ->get()
            ->mapWithKeys(fn($item) => [$item->name => max($item->category_total / 100, 0)]);

        $topTools = DB::table('rental_items')
            ->join('tools', 'rental_items.tool_id', '=', 'tools.id')
            ->select('tools.name', DB::raw('SUM(rental_items.quantity) as total_rented'))
            ->groupBy('tools.name')
            ->orderByDesc('total_rented')
            ->limit(5)
            ->get();

        return [
            'revenueLabels' => $chartMonths->keys()->toArray(),
            'revenueData' => $chartMonths->values()->toArray(),
            'categoryLabels' => $categoryRevenue->keys()->toArray(),
            'categoryData' => $categoryRevenue->values()->toArray(),
            'topTools' => $topTools,
        ];
    }
};
?>

<div>
    <x-pages::admin.layout :heading="__('Business Analytics')" :subheading="__('Key metrics and revenue insights.')">

        @assets
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        @endassets

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-zinc-200 p-6">
                <h3 class="font-bold text-zinc-800 mb-4">{{ __('Monthly Revenue (Last 12 Months)') }}</h3>
                <div class="h-72">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-zinc-200 p-6">
                <h3 class="font-bold text-zinc-800 mb-4">{{ __('Revenue by Category (Projected)') }}</h3>
                <div class="h-72 flex justify-center">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-zinc-200 overflow-hidden">
            <div class="p-6 border-b border-zinc-200">
                <h3 class="font-bold text-zinc-800">{{ __('Top 5 Most Rented Tools') }}</h3>
            </div>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Tool Name') }}</flux:table.column>
                    <flux:table.column class="text-right">{{ __('Times Rented (Qty)') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($topTools as $tool)
                        <flux:table.row>
                            <flux:table.cell class="font-medium">{{ $tool->name }}</flux:table.cell>
                            <flux:table.cell class="text-right font-bold text-primary">{{ $tool->total_rented }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>

        @script
        <script>
            const revCtx = document.getElementById('revenueChart');
            new Chart(revCtx, {
                type: 'bar',
                data: {
                    labels: @json($revenueLabels),
                    datasets: [{
                        label: 'Revenue (€)',
                        data: @json($revenueData),
                        backgroundColor: '#FACC15',
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: '#f4f4f5' } },
                        x: { grid: { display: false } }
                    }
                }
            });

            const catCtx = document.getElementById('categoryChart');
            new Chart(catCtx, {
                type: 'doughnut',
                data: {
                    labels: @json($categoryLabels),
                    datasets: [{
                        data: @json($categoryData),
                        backgroundColor: ['#FACC15', '#18181b', '#3f3f46', '#a1a1aa', '#f4f4f5'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'right' }
                    }
                }
            });
        </script>
        @endscript
    </x-pages::admin.layout>
</div>