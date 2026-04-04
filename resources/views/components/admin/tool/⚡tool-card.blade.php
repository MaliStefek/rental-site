<?php

use Livewire\Component;
use App\Models\Tool;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;

new class extends Component
{
    use WithFileUploads;

    public Tool $tool;

    #[On('toolUpdated')]
    public function reloadTools($toolId): void
    {
        if ($this->tool->id == $toolId) {
            $this->tool->refresh()->load(['category', 'prices']);
        }
    }
};
?>

<div class="flex flex-col h-full rounded-2xl border border-zinc-200 bg-white overflow-hidden transition-all duration-300 hover:shadow-2xl group max-w-sm mx-auto w-full">
    
    <div class="relative w-full h-52 bg-white overflow-hidden shrink-0 border-b border-zinc-100">
        @if($tool->image_path)
            <img src="{{ asset('storage/'.$tool->image_path) }}" alt="{{ $tool->name }}" class="w-full h-full object-contain transition-transform duration-500 group-hover:scale-105" />
        @else
            <div class="w-full h-full flex items-center justify-center">
                <flux:icon.camera class="w-12 h-12 text-zinc-300" />
            </div>
        @endif

        <div class="absolute top-4 left-4 z-10">
            <span class="{{ $tool->is_active ? 'bg-emerald-500' : 'bg-zinc-400' }} text-white text-[10px] font-black uppercase tracking-widest px-3 py-1.5 rounded-full shadow-lg">
                {{ $tool->is_active ? __('Active') : __('Inactive') }}
            </span>
        </div>
    </div>

    <div class="p-6 flex flex-col flex-1">
        
        <div class="text-[11px] font-black uppercase tracking-widest text-zinc-500 mb-2">
            {{ $tool->category?->name ?? __('Uncategorized') }}
        </div>

        <h3 class="text-3xl font-black text-zinc-950 leading-tight mb-5 line-clamp-3">
            {{ $tool->name }}
        </h3>

        @if($tool->description)
            <p class="text-sm text-zinc-600 line-clamp-3 leading-relaxed mb-6">
                {{ Str::words(strip_tags(Str::markdown($tool->description)), 20, '...') }}
            </p>
        @endif

        <div class="mt-auto">
            <div class="pt-5 border-t border-zinc-100 mb-6">
                @if($tool->prices && $tool->prices->isNotEmpty())
                    @php $primaryPrice = $tool->prices->sortByDesc('price_cents')->first(); @endphp
                    <div class="flex items-baseline gap-2">
                        <span class="text-sm font-bold text-zinc-500">{{ __('up to') }}</span>
                        <span class="text-4xl font-black text-primary italic tracking-tight">€{{ number_format($primaryPrice->price_cents / 100, 2) }}</span>
                        <span class="text-sm font-bold text-primary uppercase tracking-wider">{{ __('/day') }}</span>
                    </div>
                @else
                    <span class="text-xs font-bold text-zinc-400 uppercase tracking-widest">{{ __('Price Pending') }}</span>
                @endif
            </div>

            <div class="flex items-stretch gap-2">
                <div class="flex-1">
                    <livewire:admin.tool.edit-btn :tool="$tool" :wire:key="'edit-'.$tool->id" />
                </div>

                <div class="flex gap-2 shrink-0">
                    <livewire:admin.tool.csv-btn :tool="$tool" :wire:key="'csv-'.$tool->id" />
                    <livewire:admin.tool.delete-btn :tool="$tool" :wire:key="'delete-'.$tool->id" />
                </div>
            </div>
        </div>
    </div>
</div>