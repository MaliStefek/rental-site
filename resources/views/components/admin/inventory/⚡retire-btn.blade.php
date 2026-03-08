<?php

use Livewire\Component;
use App\Models\Asset;
use App\Enums\AssetStatus;

new class extends Component
{
    public Asset $asset;

    public function retireAsset(): void
    {
        $this->authorize('retire', $this->asset);

        $this->asset->update([
            'status' => AssetStatus::RETIRED
        ]);

        $this->dispatch('inventoryUpdated');
        
        $this->modal("confirm-retire-{$this->asset->id}")->close();
    }
};
?>

<div>
    <flux:modal.trigger name="confirm-retire-{{ $asset->id }}">
        <flux:button size="sm" variant="danger" icon="archive-box" aria-label="{{ __('Retire Unit') }}">
            {{ __('Retire') }}
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="confirm-retire-{{ $asset->id }}" class="max-w-lg pt-12">
        <form wire:submit.prevent="retireAsset" class="space-y-6">
            <div class="text-center">
                <flux:heading size="lg">{{ __('Retire Unit') }}</flux:heading>
                <flux:subheading class="mt-2 text-zinc-500">
                    {{ __('Are you sure you want to permanently retire unit ') }} 
                    <strong class="font-mono text-zinc-800 dark:text-zinc-200">{{ $asset->sku }}</strong>?
                    <br><br>
                    {{ __('This will remove it from circulation so it can no longer be rented, but will preserve all of its historical data and maintenance logs.') }}
                </flux:subheading>
            </div>

            <div class="flex justify-center space-x-2 mt-6">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" type="submit">
                    {{ __('Yes, Retire Unit') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>