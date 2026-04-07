<?php

use Livewire\Component;
use App\Models\Rental;
use App\Models\Payment;
use App\Models\Asset;
use App\Models\Tool;
use App\Enums\AssetStatus;
use App\Enums\RentalStatus;
use Carbon\Carbon;
use Livewire\Attributes\Layout;

new #[Layout('layouts.admin')] class extends Component 
{
    
    /**
     * Helper to get status colors without needing a method on the Enum.
     */
    public function getStatusColor(string $status): string
    {
        return match($status) {
            'confirmed' => 'bg-blue-500/10 text-blue-500 border-blue-500/20',
            'active'    => 'bg-green-500/10 text-green-500 border-green-500/20',
            'overdue'   => 'bg-red-500/10 text-red-500 border-red-500/20',
            default     => 'bg-gray-500/10 text-gray-400 border-gray-500/20',
        };
    }

    public function with(): array
    {
        // 1. Performance Metrics
        $revenue = Payment::where('amount_cents', '>', 0)->sum('amount_cents') / 100;
        
        $activeRentals = Rental::whereIn('status', [
            RentalStatus::CONFIRMED->value, 
            RentalStatus::ACTIVE->value, 
            RentalStatus::OVERDUE->value
        ])->count();

        $inMaintenance = Asset::where('status', AssetStatus::MAINTENANCE->value)->count();
        
        // 2. Recent Operational Activity
        $recentRentals = Rental::with('user')
            ->whereNotIn('status', [RentalStatus::DRAFT->value])
            ->latest()
            ->take(7)
            ->get();

        // 3. Inventory Critical Alerts
        $lowStockTools = Tool::with(['category'])
            ->where('is_active', true)
            ->whereHas('assets', function ($query) {
                $query->where('status', AssetStatus::AVAILABLE->value);
            }, '<=', 1)
            ->take(4)
            ->get();

        return [
            'revenue' => $revenue,
            'activeRentals' => $activeRentals,
            'inMaintenance' => $inMaintenance,
            'recentRentals' => $recentRentals,
            'lowStockTools' => $lowStockTools,
        ];
    }
};
?>

<x-pages::admin.layout :heading="__('Admin Dashboard')" :subheading="__('Live overview of your rental operations.')">
    <div class="space-y-8">
        {{-- TOP METRICS --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-dark border-2 border-gray-800 p-6 shadow-[8px_8px_0px_0px_var(--color-primary)]">
                <h3 class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] mb-2">{{ __('Total Revenue') }}</h3>
                <p class="text-4xl font-black text-primary italic tracking-tighter">€{{ number_format($revenue, 2) }}</p>
            </div>

            <div class="bg-dark border-2 border-gray-800 p-6">
                <h3 class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] mb-2">{{ __('Active Rentals') }}</h3>
                <p class="text-4xl font-black text-white tracking-tighter">{{ $activeRentals }}</p>
            </div>

            <div class="bg-dark border-2 border-gray-800 p-6 {{ $inMaintenance > 0 ? 'border-red-500/50' : '' }}">
                <h3 class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] mb-2">{{ __('In Maintenance') }}</h3>
                <p class="text-4xl font-black {{ $inMaintenance > 0 ? 'text-red-500' : 'text-white' }} tracking-tighter">{{ $inMaintenance }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- RECENT ORDERS --}}
            <div class="lg:col-span-2 bg-dark border-2 border-gray-800">
                <div class="p-6 border-b-2 border-gray-800 bg-text-main flex justify-between items-center">
                    <h2 class="font-black text-lg text-white uppercase tracking-widest">{{ __('Recent Reservations') }}</h2>
                    <a href="{{ route('rentals.edit') }}" class="text-[10px] font-black text-primary uppercase tracking-widest">{{ __('View All') }}</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-dark text-[10px] font-black text-gray-500 uppercase border-b border-gray-800">
                                <th class="p-4">{{ __('ID') }}</th>
                                <th class="p-4">{{ __('Customer') }}</th>
                                <th class="p-4">{{ __('Status') }}</th>
                                <th class="p-4">{{ __('Total') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800/50">
                            @forelse($recentRentals as $rental)
                                <tr class="hover:bg-primary/5 transition-colors">
                                    <td class="p-4 font-bold text-white text-sm">#{{ $rental->id }}</td>
                                    <td class="p-4 font-bold text-gray-300 text-sm">{{ $rental->user?->name ?? 'Guest' }}</td>
                                    <td class="p-4">
                                        {{-- We call the helper method from the class here --}}
                                        <span class="px-2 py-1 text-[9px] font-black uppercase tracking-widest border {{ $this->getStatusColor($rental->status->value) }}">
                                            {{ $rental->status->value }}
                                        </span>
                                    </td>
                                    <td class="p-4 font-black italic text-primary text-sm">€{{ number_format($rental->total_cents / 100, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="p-8 text-center text-xs font-bold text-gray-500 uppercase">No rentals yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="lg:col-span-1 space-y-8">
                {{-- LOW STOCK --}}
                <div class="bg-dark border-2 border-gray-800">
                    <div class="p-6 border-b-2 border-gray-800 bg-text-main">
                        <h2 class="font-black text-lg text-white uppercase tracking-widest">{{ __('Low Stock') }}</h2>
                    </div>
                    <div class="p-6 space-y-4">
                        @forelse($lowStockTools as $tool)
                            <div class="flex justify-between items-center border-b border-gray-800 pb-4 last:border-0 last:pb-0">
                                <div>
                                    <h4 class="font-bold text-sm text-white uppercase">{{ $tool->name }}</h4>
                                    <p class="text-[10px] font-black text-gray-500 uppercase">{{ $tool->category->name ?? 'Tools' }}</p>
                                </div>
                                <span class="text-xl font-black {{ $tool->available_stock === 0 ? 'text-red-500' : 'text-primary' }}">
                                    {{ $tool->available_stock }}
                                </span>
                            </div>
                        @empty
                            <p class="text-xs font-bold text-gray-400 text-center py-4">Stock levels healthy.</p>
                        @endforelse
                    </div>
                </div>

                {{-- MAINTENANCE --}}
                <livewire:admin.upcoming-maintenance />
            </div>
        </div>
    </div>
</x-pages::admin.layout>