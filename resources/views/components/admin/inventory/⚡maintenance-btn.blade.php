<?php

use Livewire\Component;
use App\Models\Asset;
use App\Models\ToolMaintenanceLog;
use App\Enums\AssetStatus;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public Asset $asset;

    public $cost = '';
    public $description = '';
    public $maintenance_date;
    public $next_due_date = '';
    
    public $setStatusToMaintenance = true;

    public function mount(Asset $asset)
    {
        $this->asset = $asset;
        $this->maintenance_date = now()->format('Y-m-d');
        
        if ($asset->status === AssetStatus::MAINTENANCE) {
            $this->setStatusToMaintenance = false;
        }
    }

    public function logMaintenance()
    {
        $this->validate([
            'cost' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:1000',
            'maintenance_date' => 'required|date',
            'next_due_date' => 'nullable|date|after_or_equal:maintenance_date',
            'setStatusToMaintenance' => 'boolean',
        ]);

        DB::transaction(function () {
            ToolMaintenanceLog::create([
                'asset_id' => $this->asset->id,
                'cost_cents' => (int) round((float) $this->cost * 100),
                'description' => $this->description,
                'maintenance_date' => $this->maintenance_date,
                'next_due_date' => $this->next_due_date ?: null,
            ]);

            if ($this->setStatusToMaintenance) {
                $this->asset->update([
                    'status' => AssetStatus::MAINTENANCE
                ]);
            }
        });

        $this->reset(['cost', 'description', 'next_due_date']);
        $this->maintenance_date = now()->format('Y-m-d');
        
        $this->setStatusToMaintenance = $this->asset->status !== AssetStatus::MAINTENANCE;

        $this->dispatch('inventoryUpdated');
        $this->modal("log-maintenance-{$this->asset->id}")->close();
    }
};
?>

<div>
    <flux:modal.trigger name="log-maintenance-{{ $asset->id }}">
        <flux:button size="sm" variant="subtle" icon="wrench" aria-label="{{ __('Log Maintenance') }}">
            {{ __('Log Maintenance') }}
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="log-maintenance-{{ $asset->id }}" class="max-w-lg pt-12">
        <form wire:submit.prevent="logMaintenance" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Log Maintenance') }}</flux:heading>
                <flux:subheading>
                    {{ __('Recording maintenance for: ') }} 
                    <strong class="font-mono text-zinc-800 dark:text-zinc-200">{{ $asset->sku }}</strong> 
                    @if($asset->serial_number)
                        (SN: {{ $asset->serial_number }})
                    @endif
                </flux:subheading>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:input type="date" wire:model="maintenance_date" :label="__('Date of Service')" required />
                
                <flux:input 
                    type="number" 
                    step="0.01" 
                    min="0" 
                    wire:model="cost" 
                    icon="currency-dollar" 
                    :label="__('Cost')" 
                    placeholder="0.00" 
                    required 
                />
            </div>

            <flux:textarea wire:model="description" :label="__('Service Details / Notes')" rows="3" placeholder="What was repaired or replaced?" />

            <flux:input type="date" wire:model="next_due_date" :label="__('Next Maintenance Due (Optional)')" />

            <div class="p-4 bg-zinc-50 dark:bg-white/5 rounded-lg border border-zinc-200 dark:border-white/10">
                <flux:checkbox 
                    wire:model="setStatusToMaintenance" 
                    :label="__('Set unit status to \'In Maintenance\'')" 
                    :description="__('This prevents the unit from being rented out until you mark it available again.')" 
                />
            </div>

            <div class="flex justify-end space-x-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="primary" type="submit">
                    {{ __('Save Log') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>