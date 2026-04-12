<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ActivityLog;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;

new class extends Component 
{
    use WithPagination;

    public string $search = '';
    public string $actionFilter = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
    
    public function updatedActionFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function actionTypes()
    {
        return ActivityLog::select('action')->distinct()->pluck('action');
    }

    #[Computed]
    public function logs()
    {
        $query = ActivityLog::with(['user', 'model'])->latest();

        if ($this->actionFilter) {
            $query->where('action', $this->actionFilter);
        }

        if ($this->search) {
            $query->whereHas('user', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            });
        }

        return $query->paginate(4);
    }
};
?>

<section class="p-4 space-y-4 bg-dark">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
        <div>
            <flux:heading size="xl" level="1" class="text-primary">{{ __('System Activity Logs') }}</flux:heading>
        </div>
        
        <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
            <flux:input 
                wire:model.live.debounce.300ms="search" 
                icon="magnifying-glass" 
                placeholder="Search by admin name..." 
                class="!text-primary !bg-dark placeholder:text-primary/50 min-w-[250px]"
            />
            
            <flux:select wire:model.live="actionFilter" placeholder="Filter by action..." class="!text-primary !bg-dark min-w-[200px]">
                <flux:select.option value="">All Actions</flux:select.option>
                @foreach($this->actionTypes as $action)
                    <flux:select.option value="{{ $action }}">{{ str_replace('_', ' ', Str::title($action)) }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <flux:table :paginate="$this->logs" class="text-primary">
        <flux:table.columns>
            <flux:table.column class="!text-primary">{{ __('Timestamp') }}</flux:table.column>
            <flux:table.column class="!text-primary">{{ __('User') }}</flux:table.column>
            <flux:table.column class="!text-primary">{{ __('Action') }}</flux:table.column>
            <flux:table.column class="!text-primary">{{ __('Target') }}</flux:table.column>
            <flux:table.column class="!text-primary">{{ __('Details') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse($this->logs as $log)
                <flux:table.row :key="$log->id" class="border-b border-primary/20 hover:bg-primary/5">
                    <flux:table.cell class="whitespace-nowrap text-primary/80 font-medium">
                        {{ $log->created_at->format('M d, Y H:i') }}
                    </flux:table.cell>
                    
                    <flux:table.cell class="font-bold text-primary">
                        {{ $log->user->name ?? 'System' }}
                    </flux:table.cell>
                    
                    <flux:table.cell>
                        <flux:badge size="sm" variant="info" class="uppercase tracking-widest text-[10px] !rounded-none">
                            {{ str_replace('_', ' ', $log->action) }}
                        </flux:badge>
                    </flux:table.cell>
                    
                    <flux:table.cell class="text-primary/80 text-sm">
                        {{ class_basename($log->model_type) }} #{{ $log->model_id }}
                    </flux:table.cell>
                    
                    <flux:table.cell class="text-xs text-primary/80">
                        @if($log->properties)
                            <pre class="font-mono bg-zinc-900/50 border border-zinc-800 p-2 !rounded-none overflow-x-auto max-w-xs md:max-w-md text-zinc-300">{{ json_encode($log->properties, JSON_PRETTY_PRINT) }}</pre>
                        @else
                            <span class="italic text-zinc-600">-</span>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center py-8 text-zinc-500 font-bold uppercase tracking-widest text-xs">
                        {{ __('No activity logs found.') }}
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</section>