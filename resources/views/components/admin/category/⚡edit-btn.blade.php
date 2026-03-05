<?php

use Livewire\Component;
use Illuminate\Validation\Rule;
use App\Models\Category;
use Illuminate\Support\Str;

new class extends Component {

    public Category $category;
    public $name;
    public $description;


    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string|max:255',
    ];

    public function mount(Category $category)
    {
        $this->category = $category;
        $this->name = $category->name;
        $this->description = $category->description;
    }

    public function editCategory()
    {
        $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories')
                    ->ignore($this->category->id)
                    ->whereNull('deleted_at')
            ],
            'description' => 'nullable|string|max:255',
        ]);

        $this->category->update([
            'name' => $this->name,
            'description' => $this->description,
            'slug' => Str::slug($this->name),
        ]);

        $this->dispatch('categoryUpdated', categoryId: $this->category->id);

        $this->modal('confirm-category-edit')->close();
    }
};
?>

<section class="mt-10 space-y-6">
    <flux:modal.trigger name="confirm-category-edit">
        <flux:button variant="primary" x-data=""
            x-on:click.prevent="$dispatch('open-modal', 'confirm-category-edit')" data-test="edit-category-button">
            {{ __('Edit category') }}
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="confirm-category-edit" :show="$errors->isNotEmpty()" focusable class="max-w-lg">
        <form method="POST" wire:submit="editCategory" class="space-y-6">
            @csrf
            <div>
                <flux:heading size="lg">{{ __('Edit Category') }}</flux:heading>

                <flux:subheading>
                    {{ __('Please fill in the details for the category you want to edit.') }}
                </flux:subheading>
            </div>

            <flux:input wire:model="name" :label="__('Category Name')" />

            <flux:textarea wire:model="description" :label="__('Category Description')" />

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="primary" type="submit" data-test="confirm-edit-category-button">
                    {{ __('Edit category') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>