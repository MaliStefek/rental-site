<?php

use Livewire\Component;
use App\Models\Category;

new class extends Component {
    public Category $category;

    public function deleteCategory(): void
    {
        $this->category->delete();

        $this->modal("confirm-category-deletion-{$this->category->id}")->close();

        $this->dispatch('categoryDeleted', categoryId: $this->category->id);
    }
}; ?>

<section>
    <flux:modal.trigger name="confirm-category-deletion-{{ $category->id }}">
        <flux:button variant="danger" size="sm" data-test="delete-category-button">
            {{ __('Delete') }}
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="confirm-category-deletion-{{ $category->id }}" :show="$errors->isNotEmpty()" focusable class="max-w-lg pt-12">
        <form method="POST" wire:submit.prevent="deleteCategory" class="space-y-6">
            @csrf
            <div class="text-center">
                <flux:heading size="lg">{{ __('Are you sure you want to delete :name?', ['name' => $category->name]) }}</flux:heading>
            </div>

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" type="submit" data-test="confirm-delete-category-button">
                    {{ __('Delete category') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>