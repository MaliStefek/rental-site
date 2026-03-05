<?php

use Livewire\Component;
use App\Models\Category;

new class extends Component {
    public Category $category;

    public function deleteCategory(): void
    {
        $this->category->delete();

        $this->modal('confirm-category-deletion')->close();

        $this->dispatch('categoryDeleted', categoryId: $this->category->id);
    }
}; ?>

<section class="mt-10 space-y-6">
    <flux:modal.trigger name="confirm-category-deletion">
        <flux:button variant="danger" x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-category-deletion')" data-test="delete-category-button">
            {{ __('Delete category') }}
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="confirm-category-deletion" :show="$errors->isNotEmpty()" focusable class="max-w-lg">
        <form method="POST" wire:submit.prevent="deleteCategory" class="space-y-6">
            @csrf
            <div>
                <flux:heading size="lg">{{ __('Are you sure you want to delete your category?') }}</flux:heading>
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