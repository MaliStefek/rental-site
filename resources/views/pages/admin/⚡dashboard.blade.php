<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Rental;
use App\Models\Payment;
use App\Models\Asset;
use App\Models\Tool;
use App\Enums\AssetStatus;
use App\Enums\RentalStatus;

new #[Layout('layouts.admin')] class extends Component
{
    public function with(): array
    {
        $totalRevenueCents = Payment::sum('amount_cents');

        $activeRentals = Rental::whereIn('status', [
            RentalStatus::CONFIRMED->value, 
            RentalStatus::ACTIVE->value, 
            RentalStatus::OVERDUE->value
        ])->count();

        $inMaintenance = Asset::where('status', AssetStatus::MAINTENANCE->value)->count();

        $recentRentals = Rental::with('user')->latest()->take(5)->get();

        $lowStockTools = Tool::with(['category'])->withCount(['assets as available_assets_count' => function($query) {
            $query->where('status', AssetStatus::AVAILABLE->value);
        }])->having('available_assets_count', '<=', 1)->where('is_active', true)->take(4)->get();

        return [
            'revenue' => $totalRevenueCents / 100,
            'activeRentals' => $activeRentals,
            'inMaintenance' => $inMaintenance,
            'recentRentals' => $recentRentals,
            'lowStockTools' => $lowStockTools,
        ];
    }
};
?>

<x-pages::admin.layout 
    :heading="__('Admin Dashboard')" 
    :subheading="__('Live overview of your rental operations and site performance.')"
>
    <div class="space-y-8">
        
        {{-- TOP METRICS ROW --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Revenue Card --}}
            <div class="bg-dark border-2 border-gray-800 p-6 shadow-[8px_8px_0px_0px_var(--color-primary)] relative overflow-hidden group">
                <div class="absolute -right-4 -top-4 opacity-10 group-hover:scale-110 transition-transform">
                    <flux:icon.currency-euro class="w-32 h-32 text-primary" />
                </div>
                <div class="relative z-10">
                    <h3 class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] mb-2">{{ __('Total Revenue') }}</h3>
                    <p class="text-4xl font-black text-primary italic tracking-tighter">€{{ number_format($revenue, 2) }}</p>
                </div>
            </div>

            {{-- Active Rentals Card --}}
            <div class="bg-dark border-2 border-gray-800 p-6 relative overflow-hidden group hover:border-primary transition-colors">
                <div class="absolute -right-4 -top-4 opacity-5 group-hover:opacity-10 transition-opacity">
                    <flux:icon.clipboard-document-check class="w-32 h-32 text-white" />
                </div>
                <div class="relative z-10">
                    <h3 class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] mb-2">{{ __('Active/Confirmed Rentals') }}</h3>
                    <p class="text-4xl font-black text-white tracking-tighter">{{ $activeRentals }}</p>
                </div>
            </div>

            {{-- Maintenance Card --}}
            <div class="bg-dark border-2 border-gray-800 p-6 relative overflow-hidden group {{ $inMaintenance > 0 ? 'border-red-500/50 hover:border-red-500' : 'hover:border-primary' }} transition-colors">
                <div class="absolute -right-4 -top-4 opacity-5 group-hover:opacity-10 transition-opacity">
                    <flux:icon.wrench class="w-32 h-32 {{ $inMaintenance > 0 ? 'text-red-500' : 'text-white' }}" />
                </div>
                <div class="relative z-10">
                    <h3 class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] mb-2">{{ __('Units in Maintenance') }}</h3>
                    <p class="text-4xl font-black {{ $inMaintenance > 0 ? 'text-red-500' : 'text-white' }} tracking-tighter">{{ $inMaintenance }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            {{-- RECENT ORDERS TABLE --}}
            <div class="lg:col-span-2 bg-dark border-2 border-gray-800 flex flex-col">
                <div class="p-6 border-b-2 border-gray-800 flex justify-between items-center bg-text-main">
                    <h2 class="font-black text-lg text-white uppercase tracking-widest">{{ __('Recent Reservations') }}</h2>
                    <a href="{{ route('rentals') }}" class="text-[10px] font-black text-primary uppercase tracking-widest hover:text-white transition-colors">{{ __('View All') }}</a>
                </div>
                <div class="flex-1 p-0 overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-dark text-[10px] font-black text-gray-500 uppercase tracking-widest border-b border-gray-800">
                                <th class="p-4">{{ __('ID') }}</th>
                                <th class="p-4">{{ __('Customer') }}</th>
                                <th class="p-4">{{ __('Status') }}</th>
                                <th class="p-4">{{ __('Total') }}</th>
                                <th class="p-4">{{ __('Date') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800/50 text-sm">
                            @forelse($recentRentals as $rental)
                                @php
                                    $statusVal = is_string($rental->status) ? $rental->status : $rental->status?->value;
                                @endphp
                                <tr class="hover:bg-primary/5 transition-colors">
                                    <td class="p-4 font-bold text-white">#{{ $rental->id }}</td>
                                    <td class="p-4 font-bold text-gray-300">{{ $rental->user?->name ?? 'Guest' }}</td>
                                    <td class="p-4">
                                        <span class="px-2 py-1 text-[9px] font-black uppercase tracking-widest 
                                            {{ $statusVal === 'confirmed' ? 'bg-blue-500/10 text-blue-500 border border-blue-500/20' : '' }}
                                            {{ $statusVal === 'active' ? 'bg-green-500/10 text-green-500 border border-green-500/20' : '' }}
                                            {{ in_array($statusVal, ['draft', 'returned', 'cancelled']) ? 'bg-gray-500/10 text-gray-400 border border-gray-500/20' : '' }}
                                            {{ $statusVal === 'overdue' ? 'bg-red-500/10 text-red-500 border border-red-500/20' : '' }}
                                        ">
                                            {{ $statusVal }}
                                        </span>
                                    </td>
                                    <td class="p-4 font-black italic text-primary">€{{ number_format($rental->total_cents / 100, 2) }}</td>
                                    <td class="p-4 text-xs font-bold text-gray-500">{{ $rental->created_at->format('M d, Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-8 text-center text-xs font-bold text-gray-500 uppercase tracking-widest">
                                        {{ __('No rentals found yet.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- LOW STOCK WARNINGS --}}
            <div class="lg:col-span-1 bg-dark border-2 border-gray-800 flex flex-col">
                <div class="p-6 border-b-2 border-gray-800 bg-text-main flex items-center gap-3">
                    <flux:icon.exclamation-triangle class="w-5 h-5 text-primary" />
                    <h2 class="font-black text-lg text-white uppercase tracking-widest">{{ __('Low Stock Alerts') }}</h2>
                </div>
                <div class="p-6 flex-1 space-y-4">
                    @forelse($lowStockTools as $tool)
                        <div class="flex items-center justify-between border-b border-gray-800 pb-4 last:border-0 last:pb-0">
                            <div>
                                <h4 class="font-bold text-sm text-white uppercase tracking-tight">{{ $tool->name }}</h4>
                                <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest mt-1">{{ $tool->category->name ?? 'Tools' }}</p>
                            </div>
                            <div class="flex flex-col items-end">
                                <span class="text-xl font-black {{ $tool->available_stock === 0 ? 'text-red-500' : 'text-primary' }}">{{ $tool->available_stock }}</span>
                                <span class="text-[9px] font-black uppercase tracking-widest text-gray-500">{{ __('Available') }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center py-10 text-center">
                            <flux:icon.check-circle class="w-10 h-10 text-green-500 mb-3" />
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">{{ __('All tools are well stocked.') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-pages::admin.layout>