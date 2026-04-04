<?php

use Livewire\Component;
use App\Models\Asset;
use App\Models\ToolMaintenanceLog;
use App\Enums\AssetStatus;
use App\Enums\AppEvents;
use Illuminate\Support\Facades\DB;

new class extends Component {
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
        $this->authorize('update', $this->asset->tool);

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
                'cost_cents' => (int) round((float) str_replace(',', '.', (string) $this->cost) * 100),
                'description' => $this->description,
                'maintenance_date' => $this->maintenance_date,
                'next_due_date' => $this->next_due_date ?: null,
            ]);

            if ($this->setStatusToMaintenance) {
                $this->asset->update([
                    'status' => AssetStatus::MAINTENANCE->value
                ]);
            }
        });

        $this->reset(['cost', 'description', 'next_due_date']);
        $this->maintenance_date = now()->format('Y-m-d');

        $this->setStatusToMaintenance = $this->asset->status !== AssetStatus::MAINTENANCE;

        $this->dispatch(AppEvents::INVENTORY_UPDATED->value);
        $this->modal("log-maintenance-{$this->asset->id}")->close();
    }
};
?>

<div>
    <flux:modal.trigger name="log-maintenance-{{ $asset->id }}">
        <flux:button size="sm" variant="subtle" icon="wrench" aria-label="{{ __('Log Maintenance') }}"
            class="!text-zinc-400 hover:!text-white hover:!bg-zinc-800/50 transition-colors">
            {{ __('Log Maintenance') }}
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="log-maintenance-{{ $asset->id }}"
        class="max-w-lg !bg-dark !rounded-none border border-zinc-700 shadow-2xl">
        <form wire:submit.prevent="logMaintenance" class="space-y-6 p-6">

            <div class="border-b border-zinc-800 pb-4">
                <flux:heading size="lg" class="font-black uppercase tracking-tight !text-primary">
                    {{ __('Log Maintenance') }}
                </flux:heading>
                <flux:subheading class="!text-zinc-400 font-bold uppercase tracking-widest text-[10px] mt-1">
                    {{ __('Recording for SKU: ') }}
                    <span
                        class="text-white underline decoration-primary decoration-2 underline-offset-4">{{ $asset->sku }}</span>
                    @if($asset->serial_number)
                        <span class="ml-1 text-zinc-500">(SN: {{ $asset->serial_number }})</span>
                    @endif
                </flux:subheading>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div
                    class="[&>label]:!text-zinc-400 [&_input]:!bg-zinc-800/50 [&_input]:!border-zinc-700 [&_input]:!text-white [&_input]:!rounded-none focus-within:[&_input]:!border-primary">
                    <flux:input type="date" wire:model="maintenance_date" :label="__('Date of Service')" required />
                </div>

                <div
                    class="[&>label]:!text-zinc-400 [&_input]:!bg-zinc-800/50 [&_input]:!border-zinc-700 [&_input]:!text-white [&_input]:!rounded-none focus-within:[&_input]:!border-primary [&_svg]:!text-primary">
                    <flux:input type="number" step="0.01" min="0" wire:model="cost" icon="currency-euro"
                        :label="__('Cost')" placeholder="0.00" required />
                </div>
            </div>

            <div
                class="[&>label]:!text-zinc-400 [&_textarea]:!bg-zinc-800/50 [&_textarea]:!border-zinc-700 [&_textarea]:!text-white [&_textarea]:!rounded-none focus-within:[&_textarea]:!border-primary">
                <flux:textarea wire:model="description" :label="__('Service Details / Notes')" rows="3"
                    placeholder="What was repaired or replaced?" />
            </div>

            <div
                class="[&>label]:!text-zinc-400 [&_input]:!bg-zinc-800/50 [&_input]:!border-zinc-700 [&_input]:!text-white [&_input]:!rounded-none focus-within:[&_input]:!border-primary">
                <flux:input type="date" wire:model="next_due_date" :label="__('Next Maintenance Due (Optional)')" />
            </div>

            <div class="p-4 bg-zinc-900/30 border border-zinc-800 !rounded-none">
                <div
                    class="[&>label]:!text-zinc-300 [&_input]:!bg-zinc-800 [&_input]:!border-zinc-600 checked:[&_input]:!bg-primary checked:[&_input]:!border-primary [&_p]:!text-zinc-500 [&_input]:!rounded-none">
                    <flux:checkbox wire:model="setStatusToMaintenance"
                        :label="__('Set unit status to \'In Maintenance\'')"
                        :description="__('This prevents the unit from being rented out.')" />
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-6 border-t border-zinc-800 mt-6">
                <flux:modal.close>
                    <flux:button class="btn-action-subtle">
                        {{ __('Cancel') }}
                    </flux:button>
                </flux:modal.close>

                <flux:button type="submit" class="btn-action-save" data-test="confirm-log-maintenance-button">
                    {{ __('Save Log Entry') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>