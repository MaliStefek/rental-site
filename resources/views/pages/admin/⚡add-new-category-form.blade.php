<?php

use Livewire\Component;
use App\Models\Category;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

new class extends Component {
    public $name = '';
    public $description = '';
    public $slug = '';

    public function addCategory()
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories')->whereNull('deleted_at')],
            'description' => 'nullable|string|max:255',
        ]);

        $category = Category::onlyTrashed()->whereRaw('LOWER(name) = ?', [strtolower($this->name)])->first();
        if ($category) {
            $category->restore();
            $category->update([
                'description' => $this->description,
                'slug' => Str::slug($this->name),
            ]);
        } else {
            Category::create([
                'name' => $this->name, 
                'description' => $this->description,
                'slug' => Str::slug($this->name)
            ]);
        }

        $this->reset(['name', 'description', 'slug']);
        $this->dispatch('categoryAdded');
        $this->modal('confirm-category-addition')->close();
    }
};
?>

<section class="mt-10 space-y-6">
    <div class="relative mb-5">
        <flux:heading>{{ __('Add New Category') }}</flux:heading>
        <flux:subheading>{{ __('Add a new category to your rental site') }}</flux:subheading>
    </div>

    <flux:modal.trigger name="confirm-category-addition">
        <flux:button variant="primary" x-data=""
            x-on:click.prevent="$dispatch('open-modal', 'confirm-category-addition')" data-test="add-category-button">
            {{ __('Add new category') }}
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="confirm-category-addition" :show="$errors->isNotEmpty()" focusable class="max-w-lg">
        <form method="POST" wire:submit="addCategory" class="space-y-6">
            @csrf
            <div>
                <flux:heading size="lg">{{ __('Add New Category') }}</flux:heading>

                <flux:subheading>
                    {{ __('Please fill in the details for the new category you want to add.') }}
                </flux:subheading>
            </div>

            <flux:input wire:model="name" :label="__('Category Name')" />

            <flux:textarea wire:model="description" :label="__('Category Description')" />

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="primary" type="submit" data-test="confirm-add-category-button">
                    {{ __('Add new category') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>