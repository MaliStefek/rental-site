<?php

use Livewire\Component;
use App\Models\Tool;

new class extends Component
{
    public Tool $tool;
};
?>

<div class="flex flex-col h-full !rounded-none border border-gray-800 bg-dark overflow-hidden transition-all duration-300 hover:border-primary hover:shadow-[4px_4px_0px_0px_var(--color-primary)] group max-w-sm mx-auto w-full relative -translate-x-0 -translate-y-0 hover:-translate-x-1 hover:-translate-y-1">

    <a href="{{ route('items.show', $tool->slug ?? $tool->id) }}">
        <div class="relative w-full h-40 bg-white overflow-hidden shrink-0 border-b border-gray-800">
            @if($tool->image_path)
                <img src="{{ asset('storage/'.$tool->image_path) }}" alt="{{ $tool->name }}" class="w-full h-full object-contain transition-transform duration-400 group-hover:scale-105 opacity-85 group-hover:opacity-100" />
            @else
                <div class="w-full h-full flex items-center justify-center bg-text-main">
                    <flux:icon.camera class="w-12 h-12 text-gray-700" />
                </div>
            @endif

            <div class="absolute top-4 left-4 z-10 flex flex-col gap-2">
                @if($tool->available_stock > 0)
                    <span class="bg-emerald-600 text-white text-[10px] font-black uppercase tracking-widest px-3 py-1.5 !rounded-none shadow-md border border-emerald-500">
                        {{ __('Available') }}
                    </span>
                @else
                    <span class="bg-red-600 text-white text-[10px] font-black uppercase tracking-widest px-3 py-1.5 !rounded-none shadow-md border border-red-500">
                        {{ __('Out of Stock') }}
                    </span>
                @endif
            </div>
        </div>
    </a>

    <div class="p-6 flex flex-col flex-1 bg-dark">

        <div class="text-[10px] font-black uppercase tracking-widest text-primary mb-2">
            {{ $tool->category?->name ?? __('Uncategorized') }}
        </div>

        <h3 class="text-3xl font-black text-white leading-tight mb-4 line-clamp-3 uppercase tracking-tighter">
            {{ $tool->name }}
        </h3>

        @if($tool->description)
            <p class="text-sm text-gray-400 line-clamp-3 leading-relaxed mb-6 font-medium">
                {{ Str::words(strip_tags(Str::markdown($tool->description)), 20, '...') }}
            </p>
        @endif

        <div class="mt-auto">
            <div class="pt-5 border-t border-gray-800 mb-5">
                @if($tool->prices && $tool->prices->isNotEmpty())
                    @php 
                        $displayPrice = $tool->prices->sortByDesc('price_cents')->first(); 
                    @endphp
                    
                    <div class="flex items-end gap-2">
                        <span class="text-[11px] font-black text-gray-500 uppercase tracking-widest pb-1 whitespace-nowrap shrink-0">
                            {{ __('Up to') }}
                        </span>
                        
                        <span class="text-5xl font-black text-primary italic tracking-tighter leading-none">
                            €{{ number_format($displayPrice->price_cents / 100, 2) }}
                        </span>
                        
                        <span class="text-sm font-bold text-gray-500 uppercase tracking-widest pb-1 whitespace-nowrap shrink-0">
                            {{ __('/day') }}
                        </span>
                    </div>
                @else
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">{{ __('Price Pending') }}</span>
                @endif
            </div>

            <a href="{{ route('items.show', $tool->slug ?? $tool->id) }}"
               class="flex items-center justify-center w-full bg-text-main border border-gray-700 py-3.5 px-4 text-white font-black uppercase tracking-widest text-xs transition-all duration-300 group-hover:bg-primary group-hover:text-text-main group-hover:border-primary !rounded-none">
                {{ __('View Details') }}
                <flux:icon.arrow-right class="w-4 h-4 ml-2 transition-transform duration-300 group-hover:translate-x-1" />
            </a>
        </div>
    </div>
</div>