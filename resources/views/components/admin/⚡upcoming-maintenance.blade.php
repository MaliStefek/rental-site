<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use App\Models\ToolMaintenanceLog;
use App\Enums\AssetStatus;
use Carbon\Carbon;

new class extends Component {
    
    #[Computed]
    public function upcoming()
    {
        return ToolMaintenanceLog::with(['asset.tool'])
            ->whereHas('asset', function($q) {
                $q->where('status', '!=', AssetStatus::MAINTENANCE->value);
            })
            ->whereNotNull('next_due_date')
            ->where('next_due_date', '<=', Carbon::now()->addDays(14))
            ->orderBy('next_due_date', 'asc')
            ->get();
    }
};
?>

<div class="bg-dark border border-zinc-800 p-6">
    <div class="flex items-center gap-3 mb-6 border-b border-zinc-800 pb-4">
        <flux:icon.wrench-screwdriver class="w-6 h-6 text-red-500" />
        <flux:heading size="lg" class="font-black uppercase tracking-tight text-white">
            {{ __('Upcoming Maintenance') }}
        </flux:heading>
    </div>

    @if($this->upcoming->isEmpty())
        <div class="text-center py-6">
            <p class="text-xs font-bold uppercase tracking-widest text-zinc-500">
                {{ __('All active equipment is up to date.') }}
            </p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($this->upcoming as $log)
                @php
                    $dueDate = Carbon::parse($log->next_due_date);
                    $isPast = $dueDate->isPast();
                    $daysDiff = $isPast ? $dueDate->diffInDays(now()) : now()->diffInDays($dueDate);
                @endphp
                
                <div class="flex items-center justify-between bg-zinc-900/50 p-4 border-l-4 {{ $isPast ? 'border-red-500' : 'border-primary' }}">
                    <div>
                        <h4 class="font-black text-white uppercase text-sm">
                            {{ $log->asset?->tool?->name ?? __('Unknown Tool') }}
                        </h4>
                        <p class="text-xs font-mono text-zinc-400 mt-1">
                            SKU: {{ $log->asset?->sku ?? 'N/A' }}
                        </p>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] font-bold uppercase tracking-widest text-zinc-500 block mb-1">
                            {{ $isPast ? __('Overdue By') : __('Due In') }}
                        </span>
                        <span class="font-black text-sm {{ $isPast ? 'text-red-500' : 'text-primary' }}">
                            {{ $daysDiff }} {{ __('Days') }}
                        </span>
                        <span class="text-[9px] font-bold text-zinc-600 block mt-1">{{ $dueDate->format('M d, Y') }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>