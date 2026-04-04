<?php

use Livewire\Component;
use App\Models\Tool;

new class extends Component {
    public Tool $tool;

    public function deleteTool(): void
    {
        $this->authorize('delete', $this->tool);
        
        $this->tool->delete();
        $this->modal("confirm-tool-deletion-{$this->tool->id}")->close();
        $this->dispatch('toolDeleted', toolId: $this->tool->id);
    }
}; ?>

<section>
    <flux:modal.trigger name="confirm-tool-deletion-{{ $tool->id }}">
        <flux:button size="sm" icon="trash" class="btn-action-danger" variant="danger">
            {{ __('Delete') }}
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="confirm-tool-deletion-{{ $tool->id }}" :show="$errors->isNotEmpty()" focusable
        class="max-w-lg !bg-dark !rounded-none border border-zinc-700 shadow-2xl">
        <form method="POST" wire:submit.prevent="deleteTool" class="p-6">
            @csrf

            <div class="text-center pb-2">
                <flux:icon.exclamation-triangle class="w-12 h-12 text-danger mx-auto mb-4" />

                <flux:heading size="lg" class="font-black text-danger uppercase tracking-tight">
                    {{ __('Confirm Deletion') }}
                </flux:heading>
                
                <flux:subheading class="text-sm font-bold text-gray-400 mt-2">
                    {!! __('Are you sure you want to delete <strong class="text-text-main dark:text-white uppercase">:name</strong>?', ['name' => e($tool->name)]) !!}
                    <br>
                    {{ __('This action cannot be undone.') }}
                </flux:subheading>
            </div>

            <div class="flex justify-end gap-3 pt-6 border-t border-zinc-800">
                <flux:modal.close>
                    <flux:button variant="subtle" class="btn-action-subtle">
                        {{ __('Cancel') }}
                    </flux:button>
                </flux:modal.close>

                <flux:button variant="danger" type="submit" class="btn-action-danger" data-test="confirm-delete-tool-button">
                    {{ __('Delete Tool') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>