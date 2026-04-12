<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Models\Tool;
use App\Models\Category;
use App\Enums\AssetStatus;
use Illuminate\Support\Facades\Cache;

new #[Layout('layouts.app')] class extends Component 
{
    #[Computed]
    public function categories()
    {
        return Category::orderBy('name')
            ->take(4)
            ->get();
    }

    #[Computed]
    public function featuredTools()
    {
        $toolIds = Cache::remember('home.featured_tool_ids', now()->addMinutes(30), fn() => Tool::where('is_active', true)
            ->inRandomOrder()
            ->take(4)
            ->pluck('id'));

        if ($toolIds->isEmpty()) {
            return collect();
        }

        return Tool::with(['prices', 'category'])
            ->withCount(['assets as available_assets_count' => fn($q) => $q->where('status', AssetStatus::AVAILABLE->value)])
            ->whereIn('id', $toolIds)
            ->where('is_active', true)
            ->get();
    }
};
?>

<div class="bg-dark min-h-screen font-sans flex flex-col">
    
    @if(session()->has('cart') && count(session('cart')) > 0)
        <div class="w-full bg-primary text-dark text-center py-2 font-black uppercase tracking-widest text-xs z-40 fixed top-[96px] lg:top-[96px]">
            {{ __('You have') }} {{ count(session('cart')) }} {{ __('items in your bag. ') }} 
            <a href="{{ route('checkout') }}" class="underline hover:text-white transition-colors">{{ __('Complete Reservation') }}</a>
        </div>
    @endif

    <section class="relative pt-32 pb-24 lg:pt-48 lg:pb-36 overflow-hidden flex flex-col items-center text-center px-6">
        <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(#FACC15 1px, transparent 1px); background-size: 60px 60px;"></div>

        <div class="max-w-3xl space-y-8 z-10">
            <flux:heading size="xl" class="text-5xl md:text-7xl font-black tracking-tighter text-white uppercase drop-shadow-lg">
                {{ __('Equip your next') }} <span class="text-primary">{{ __('big project.') }}</span>
            </flux:heading>
            
            <flux:subheading class="text-xl md:text-2xl max-w-2xl mx-auto text-gray-400 font-bold tracking-tight">
                {{ __('From heavy machinery to precision power tools, we have everything you need to get the job done right.') }}
            </flux:subheading>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-6 pt-6">
                <a href="{{ route('items') }}" class="bg-primary text-text-main py-5 px-10 font-black tracking-widest uppercase shadow-[6px_6px_0px_0px_#ffffff] hover:shadow-none hover:translate-y-[6px] hover:translate-x-[6px] transition-all flex items-center gap-3">
                    {{ __('Browse Catalog') }}
                    <flux:icon.arrow-right class="w-5 h-5" />
                </a>
                
                <a href="#how-it-works" class="text-white hover:text-primary py-5 px-8 font-black tracking-widest uppercase transition-colors flex items-center gap-2">
                    {{ __('How it works') }}
                </a>
            </div>
        </div>

        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-full opacity-5 pointer-events-none">
            <flux:icon.wrench class="absolute top-20 left-10 w-48 h-48 rotate-12 text-white" />
            <flux:icon.cog-6-tooth class="absolute bottom-10 right-10 w-64 h-64 -rotate-12 text-white" />
        </div>
    </section>

    <div class="bg-text-main border-t border-gray-800 py-16 space-y-20 shadow-2xl relative z-10 flex-grow overflow-hidden">
        
        <div class="absolute inset-0 w-full h-full opacity-[0.025] pointer-events-none overflow-hidden">
            <flux:icon.bolt class="absolute top-[5%] left-[2%] w-48 h-48 rotate-12 text-white" />
            <flux:icon.cog-6-tooth class="absolute top-[25%] -left-[15%] w-[45rem] h-[45rem] rotate-45 text-white" />
            <flux:icon.wrench-screwdriver class="absolute -top-[10%] right-[25%] w-[35rem] h-[35rem] rotate-[110deg] text-white" />
            <flux:icon.cog class="absolute top-[15%] -right-[15%] w-[55rem] h-[55rem] rotate-[15deg] text-white" />
            <flux:icon.wrench class="absolute bottom-[-5%] left-[25%] w-[50rem] h-[50rem] rotate-[65deg] text-white" />
            <flux:icon.cog-8-tooth class="absolute bottom-[5%] right-[10%] w-32 h-32 -rotate-12 text-white" />
        </div>

        @if($this->categories->isNotEmpty())
            <section class="max-w-7xl mx-auto px-6 lg:px-8 relative z-10">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-2 h-6 bg-primary"></div>
                    <flux:heading class="font-black text-white uppercase tracking-widest text-lg">{{ __('Browse by Category') }}</flux:heading>
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    @foreach($this->categories as $category)
                        <a href="{{ route('items', ['category' => $category->slug]) }}" 
                           class="block p-8 bg-dark border border-gray-800 hover:border-primary hover:shadow-[4px_4px_0px_0px_#FACC15] -translate-x-1 -translate-y-1 hover:translate-x-0 hover:translate-y-0 transition-all text-center group !rounded-none">
                            <div class="w-16 h-16 bg-text-main flex items-center justify-center mx-auto mb-4 group-hover:bg-primary transition-colors">
                                <flux:icon.rectangle-group class="w-8 h-8 text-gray-400 group-hover:text-text-main transition-colors" />
                            </div>
                            <flux:heading size="md" class="font-black text-white uppercase tracking-widest group-hover:text-primary transition-colors">
                                {{ $category->name }}
                            </flux:heading>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        @if($this->featuredTools->isNotEmpty())
            <section class="max-w-7xl mx-auto px-6 lg:px-8 relative z-10 pb-8">
                <div class="flex items-center justify-between mb-8 border-b-2 border-gray-800 pb-4">
                    <div class="flex items-center gap-4">
                        <div class="w-2 h-8 bg-primary"></div>
                        <flux:heading class="font-black text-3xl text-white uppercase tracking-tight">{{ __('Hot Deals & Featured') }}</flux:heading>
                    </div>
                    <a href="{{ route('items') }}" class="text-gray-400 hover:text-primary font-black uppercase tracking-widest text-sm transition-colors">
                        {{ __('View all') }}
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 items-stretch">
                    @foreach($this->featuredTools as $tool)
                        <livewire:frontend.tool-card :tool="$tool" wire:key="featured-tool-{{ $tool->id }}" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</div>