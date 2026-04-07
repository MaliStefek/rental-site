<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use App\Models\Tool;
use App\Models\Category;
use App\Enums\AssetStatus;

new #[Layout('layouts.app')] class extends Component 
{
    use WithPagination;

    #[Url]
    public $search = '';

    #[Url]
    public $selectedCategories = [];

    #[Url]
    public $minPrice = '';

    #[Url]
    public $maxPrice = '';

    public function updated($property): void
    {
        if (in_array($property, ['search', 'selectedCategories', 'minPrice', 'maxPrice'])) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'selectedCategories', 'minPrice', 'maxPrice']);
        $this->resetPage();
    }

    #[Computed]
    public function categories()
    {
        return Category::whereHas('tools', function($q) {
            $q->where('is_active', true);
        })->get();
    }

    #[Computed]
    public function tools()
    {
        return Tool::with(['prices', 'category'])
            ->withCount(['assets as available_assets_count' => function($q) {
                $q->where('status', \App\Enums\AssetStatus::AVAILABLE->value);
            }])
            ->where('is_active', true)
            ->when($this->search, fn($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->when($this->category, fn($q) => $q->where('category_id', $this->category))
            ->paginate(12);
    }
};
?>

<div class="bg-text-main min-h-screen font-sans text-white flex flex-col">
    
    <div class="relative pt-10 pb-8 bg-dark border-b-2 border-gray-800 overflow-hidden shrink-0 shadow-lg z-20">
        
        <div class="absolute inset-0 opacity-10 pointer-events-none" style="background-image: radial-gradient(#FACC15 1.5px, transparent 1.5px); background-size: 32px 32px;"></div>

        <div class="absolute inset-0 opacity-[0.03] pointer-events-none">
            <flux:icon.cog-8-tooth class="absolute -top-20 -left-10 w-80 h-80 rotate-12 text-white" />
            <flux:icon.wrench class="absolute top-2 right-20 w-48 h-48 -rotate-12 text-white" />
        </div>

        <div class="relative z-10 max-w-7xl mx-auto px-6 lg:px-8 flex flex-col gap-8">
            
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div class="flex items-center gap-4">
                    <div class="w-3 h-10 bg-primary"></div>
                    <flux:heading class="font-black text-4xl sm:text-5xl text-white uppercase tracking-tight leading-none drop-shadow-md">
                        {{ __('Equipment Catalog') }}
                    </flux:heading>
                </div>
                
                <div class="text-gray-400 font-bold uppercase tracking-widest text-xs bg-text-main py-2 px-4 border border-gray-800">
                    {{ __('Showing') }} <span class="font-black text-primary text-sm">{{ $this->tools->total() }}</span> {{ __('results') }}
                    @if($search)
                        <span class="mx-2">|</span> {{ __('for') }} "<span class="text-white">{{ $search }}</span>"
                    @endif
                </div>
            </div>

            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-end gap-6 pt-6 border-t border-gray-800/60 mt-2">
                
                <div class="flex flex-wrap items-center gap-y-2 gap-x-3 text-xs font-black uppercase tracking-widest">
                    <button wire:click="$set('selectedCategories', [])" class="transition-colors hover:text-primary {{ empty($selectedCategories) ? 'text-primary' : 'text-gray-500' }}">
                        {{ __('All Equipment') }}
                    </button>

                    @foreach($this->categories as $category)
                        <span class="text-gray-800">|</span>
                        <label class="cursor-pointer group m-0 flex items-center">
                            <input type="checkbox" wire:model.live="selectedCategories" value="{{ $category->id }}" class="peer sr-only" />
                            <span class="transition-colors text-gray-500 group-hover:text-white peer-checked:text-primary">
                                {{ $category->name }}
                            </span>
                        </label>
                    @endforeach
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <span class="text-[10px] font-black text-gray-600 uppercase tracking-widest mr-1">{{ __('Price:') }}</span>
                    <div class="flex items-center gap-2">
                        <input wire:model.live.debounce.500ms="minPrice" type="number" min="0" placeholder="Min €" class="w-20 h-9 bg-text-main text-white border border-gray-700 focus:border-primary focus:ring-0 placeholder-gray-600 font-bold !rounded-none text-center text-xs" />
                        <span class="text-gray-600 font-black">-</span>
                        <input wire:model.live.debounce.500ms="maxPrice" type="number" min="0" placeholder="Max €" class="w-20 h-9 bg-text-main text-white border border-gray-700 focus:border-primary focus:ring-0 placeholder-gray-600 font-bold !rounded-none text-center text-xs" />
                    </div>

                    @if($search || !empty($selectedCategories) || $minPrice || $maxPrice)
                        <button wire:click="clearFilters" class="h-9 px-3 bg-text-main border border-gray-700 text-gray-400 hover:text-primary hover:border-primary font-black uppercase tracking-widest text-[10px] transition-all !rounded-none flex items-center gap-1">
                            <flux:icon.x-mark class="w-3 h-3" />
                            <span class="hidden sm:inline">{{ __('Clear') }}</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="relative flex-1 w-full overflow-hidden">
        
        <div class="absolute inset-0 w-full h-full opacity-[0.025] pointer-events-none overflow-hidden">
            
            <flux:icon.bolt class="absolute top-[5%] left-[2%] w-48 h-48 rotate-12 text-white" />

            <flux:icon.cog-6-tooth class="absolute top-[25%] -left-[15%] w-[45rem] h-[45rem] rotate-45 text-white" />

            <flux:icon.wrench-screwdriver class="absolute -top-[10%] right-[25%] w-[35rem] h-[35rem] rotate-[110deg] text-white" />

            <flux:icon.cog class="absolute top-[15%] -right-[15%] w-[55rem] h-[55rem] rotate-[15deg] text-white" />

            <flux:icon.wrench class="absolute bottom-[-5%] left-[25%] w-[50rem] h-[50rem] rotate-[65deg] text-white" />

            <flux:icon.cog-8-tooth class="absolute bottom-[5%] right-[10%] w-32 h-32 -rotate-12 text-white" />
            
        </div>

        <div class="max-w-7xl mx-auto px-6 lg:px-8 w-full py-12 relative z-10">
            <main class="relative min-h-[500px]">
                
                <div wire:loading.delay.longer class="absolute inset-0 z-20 bg-text-main/80 backdrop-blur-sm flex items-start justify-center pt-32">
                    <div class="bg-dark border-2 border-primary px-10 py-8 shadow-[8px_8px_0px_0px_var(--color-primary)] flex flex-col items-center gap-4">
                        <flux:icon.cog-6-tooth class="w-12 h-12 text-primary animate-spin" />
                        <span class="font-black uppercase tracking-widest text-sm text-white">{{ __('Processing...') }}</span>
                    </div>
                </div>

                @if($this->tools->isEmpty())
                    <div class="w-full text-center py-32 bg-dark border-2 border-gray-800 flex flex-col items-center justify-center">
                        <flux:icon.wrench-screwdriver class="w-16 h-16 text-gray-700 mb-6" />
                        <h2 class="font-black text-2xl uppercase tracking-widest text-white">{{ __('No equipment found') }}</h2>
                        <p class="mt-3 text-sm text-gray-500 font-bold uppercase tracking-wider max-w-sm">{{ __('Try adjusting your filters or search term to find what you need.') }}</p>
                        <button wire:click="clearFilters" class="mt-8 bg-primary hover:bg-primary-dark text-text-main font-black uppercase tracking-widest py-4 px-10 transition-colors shadow-[6px_6px_0px_0px_#ffffff] hover:shadow-none hover:translate-y-[6px] hover:translate-x-[6px]">
                            {{ __('Clear all filters') }}
                        </button>
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 items-stretch">
                        @foreach($this->tools as $tool)
                            <livewire:frontend.tool-card :tool="$tool" wire:key="catalog-tool-{{ $tool->id }}" />
                        @endforeach
                    </div>

                    <div class="mt-12">
                        {{ $this->tools->links() }}
                    </div>
                @endif
            </main>
        </div>
    </div>
</div>