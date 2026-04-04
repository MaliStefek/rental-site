<?php

use Livewire\Component;
use App\Models\Asset;
use App\Models\ToolMaintenanceLog;
use Livewire\Attributes\Computed;
use Carbon\Carbon;

new class extends Component
{
    public Asset $asset;

    #[Computed]
    public function logs()
    {
        return ToolMaintenanceLog::where('asset_id', $this->asset->id)
            ->orderBy('maintenance_date', 'desc')
            ->get();
    }

    public function getTotalCostProperty()
    {
        return $this->logs->sum('cost_cents') / 100;
    }
};
?>

<div>
    <flux:modal.trigger name="view-history-{{ $asset->id }}">
        <flux:button size="sm" variant="subtle" icon="clipboard-document-list" aria-label="{{ __('View Maintenance History') }}" class="!text-zinc-400 hover:!text-white hover:!bg-zinc-800/50 transition-colors">
            {{ __('History') }}
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="view-history-{{ $asset->id }}" class="max-w-2xl !bg-dark !rounded-none border border-zinc-700 shadow-2xl">
        <div class="space-y-6 p-6">
            
            <div class="flex items-start justify-between border-b border-zinc-800 pb-4 gap-4">
                <div>
                    <flux:heading size="lg" class="font-black uppercase tracking-tight !text-primary">
                        {{ __('Maintenance History') }}
                    </flux:heading>
                    <flux:subheading class="mt-1 !text-zinc-400 font-bold uppercase tracking-widest text-[10px]">
                        {{ $asset->tool->name ?? 'Tool' }} — 
                        <span class="text-white underline decoration-primary decoration-2 underline-offset-4 font-mono text-xs">{{ $asset->sku }}</span>
                    </flux:subheading>
                </div>
                
                <div class="text-right">
                    <flux:heading size="xs" class="uppercase tracking-widest !text-red-500 font-black">
                        {{ __('Total Investment') }}
                    </flux:heading>
                    <flux:heading size="lg" class="font-black !text-red-500 leading-none mt-1">
                        €{{ number_format($this->totalCost, 2) }}
                    </flux:heading>
                </div>
            </div>

            <div class="border border-zinc-800 !rounded-none overflow-hidden bg-zinc-900/30">
                @if($this->logs->isEmpty())
                    <div class="p-12 text-center">
                        <flux:icon.wrench class="w-10 h-10 mx-auto mb-4 !text-zinc-700" />
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-zinc-500">
                            {{ __('No maintenance records found.') }}
                        </p>
                    </div>
                @else
                    <div class="divide-y divide-zinc-800 max-h-[50vh] overflow-y-auto custom-scrollbar">
                        @foreach($this->logs as $log)
                            <div class="p-5 hover:bg-zinc-800/40 transition-colors">
                                <div class="flex justify-between items-start mb-3 gap-4">
                                    <div>
                                        <flux:heading size="sm" class="font-black uppercase tracking-tight !text-white leading-tight">
                                            {{ Carbon::parse($log->maintenance_date)->format('d. m. Y') }}
                                        </flux:heading>
                                        @if($log->next_due_date)
                                            <div class="flex items-center gap-2 mt-1.5">
                                                <div class="w-1.5 h-1.5 bg-red-600 !rounded-none"></div>
                                                <p class="text-[10px] font-bold uppercase tracking-widest !text-zinc-500 leading-none">
                                                    {{ __('Next Due:') }} {{ Carbon::parse($log->next_due_date)->format('d. m. Y') }}
                                                </p>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="font-mono text-sm font-black text-zinc-300 bg-zinc-800 px-3 py-1 border border-zinc-700 !rounded-none shrink-0 shadow-sm">
                                        €{{ number_format($log->cost_cents / 100, 2) }}
                                    </div>
                                </div>
                                @if($log->description)
                                    <div class="bg-zinc-950/50 p-3 border border-zinc-800/50 !rounded-none mt-3">
                                        <p class="text-xs font-medium !text-zinc-400 leading-relaxed italic">"{{ $log->description }}"</p>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="flex justify-end pt-4 border-t border-zinc-800 mt-2">
                <flux:modal.close>
                    <flux:button class="btn-action-save !rounded-none" data-test="close-history-button">
                        {{ __('Close History') }}
                    </flux:button>
                </flux:modal.close>
            </div>
            
        </div>
    </flux:modal>
</div>