<?php

use Livewire\Component;
use App\Models\Tool;

new class extends Component {
    public Tool $tool;

    public function deleteTool(): void
    {
        $this->tool->delete();
        $this->modal("confirm-tool-deletion-{$this->tool->id}")->close();
        $this->dispatch('toolDeleted', toolId: $this->tool->id);
    }
}; ?>

<section class="mt-10 space-y-6">
    <flux:modal.trigger name="confirm-tool-deletion-{{ $tool->id }}">
        <flux:button variant="danger" data-test="delete-tool-button">
            {{ __('Delete tool') }}
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="confirm-tool-deletion-{{ $tool->id }}" :show="$errors->isNotEmpty()" focusable class="max-w-lg pt-12">
        <form method="POST" wire:submit.prevent="deleteTool" class="space-y-6">
            @csrf
            <div>
                <flux:heading size="lg">{{ __('Are you sure you want to delete :name?', ['name' => $tool->name]) }}</flux:heading>
            </div>

            <div class="flex justify-end space-x-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" type="submit">
                    {{ __('Delete tool') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>