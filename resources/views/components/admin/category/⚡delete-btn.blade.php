<?php

use Livewire\Component;
use App\Models\Category;

new class extends Component {
    public Category $category;

    public function deleteCategory(): void
    {
        $this->authorize('delete', $this->category);

        $this->category->delete();

        $this->modal("confirm-category-deletion-{$this->category->id}")->close();

        $this->dispatch('categoryDeleted', categoryId: $this->category->id);
    }
}; ?>

<section>
    <flux:modal.trigger name="confirm-category-deletion-{{ $category->id }}">
        <flux:button variant="danger" size="sm" class="btn-action-danger" data-test="delete-category-button">
            {{ __('Delete') }}
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="confirm-category-deletion-{{ $category->id }}" :show="$errors->isNotEmpty()" focusable class="max-w-lg pt-12 !rounded-none shadow-2xl bg-bg dark:!bg-dark">
        <form method="POST" wire:submit.prevent="deleteCategory" class="space-y-6">
            @csrf
            
            <div class="text-center pb-2">
                
                <flux:icon.exclamation-triangle class="w-12 h-12 text-danger mx-auto mb-4" />

                <flux:heading size="lg" class="font-black text-danger uppercase tracking-tight">
                    {{ __('Confirm Deletion') }}
                </flux:heading>
                
                <flux:subheading class="text-sm font-bold text-gray-400 mt-2">
                    {!! __('Are you sure you want to delete <strong class="text-text-main dark:text-white uppercase">:name</strong>?', ['name' => e($category->name)]) !!}
                    <br>
                    {{ __('This action cannot be undone.') }}
                </flux:subheading>
            </div>

            <div class="flex justify-end gap-3 pt-6 border-t border-gray-200 dark:border-gray-800">
                <flux:modal.close>
                    <flux:button variant="subtle" class="btn-action-subtle">
                        {{ __('Cancel') }}
                    </flux:button>
                </flux:modal.close>

                <flux:button variant="danger" type="submit" class="btn-action-danger" data-test="confirm-delete-category-button">
                    {{ __('Delete category') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>