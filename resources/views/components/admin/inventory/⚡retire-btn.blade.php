<?php

use Livewire\Component;
use App\Models\Asset;
use App\Enums\AssetStatus;

new class extends Component
{
    public Asset $asset;

    public function retireAsset(): void
    {
        if (! $this->asset->tool) {
            abort(404, 'Associated tool not found.');
        }

        $this->authorize('update', $this->asset->tool);

        $this->asset->update([
            'status' => AssetStatus::RETIRED
        ]);

        $this->dispatch('inventoryUpdated');
        
        $this->modal("confirm-retire-{$this->asset->id}")->close();
    }
};
?>

<section>
    <flux:modal.trigger name="confirm-retire-{{ $asset->id }}">
        <flux:button variant="danger" size="sm" icon="archive-box" class="btn-action-danger" aria-label="{{ __('Retire Unit') }}">
            {{ __('Retire') }}
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="confirm-retire-{{ $asset->id }}" class="max-w-lg pt-12 !rounded-none !bg-dark">
        <form wire:submit.prevent="retireAsset" class="space-y-6">
            
            <div class="text-center pb-2">
                <flux:icon.exclamation-triangle class="w-12 h-12 text-danger mx-auto mb-4" />

                <flux:heading size="lg" class="font-black text-danger uppercase tracking-tight">
                    {{ __('Confirm Retirement') }}
                </flux:heading>
                
                <flux:subheading class="text-sm font-bold !text-zinc-400 mt-2">
                    {{ __('Are you sure you want to permanently retire unit ') }} 
                    <strong class="font-mono text-white uppercase">{{ $asset->sku }}</strong>?
                    <br><br>
                    <span class="!text-zinc-500 font-normal">
                        {{ __('This will remove it from circulation so it can no longer be rented, but will preserve all of its historical data and maintenance logs.') }}
                    </span>
                </flux:subheading>
            </div>

            <div class="flex justify-end gap-3 pt-6 border-t border-zinc-800">
                <flux:modal.close>
                    <flux:button variant="subtle" class="btn-action-subtle">
                        {{ __('Cancel') }}
                    </flux:button>
                </flux:modal.close>

                <flux:button variant="danger" type="submit" class="btn-action-danger">
                    {{ __('Retire Unit') }}
                </flux:button>
            </div>
            
        </form>
    </flux:modal>
</section>