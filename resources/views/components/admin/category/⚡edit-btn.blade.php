<?php

use Livewire\Component;
use Illuminate\Validation\Rule;
use App\Models\Category;
use Illuminate\Support\Str;

new class extends Component {

    public Category $category;
    public $name;
    public $description;

    public function mount(Category $category)
    {
        $this->category = $category;
        $this->name = $category->name;
        $this->description = $category->description;
    }

    public function editCategory()
    {
        $this->authorize('update', $this->category);

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

        $this->modal("confirm-category-edit-{$this->category->id}")->close();
    }
}; ?>

<section>
    <flux:modal.trigger name="confirm-category-edit-{{ $category->id }}">
        <flux:button size="sm" class="btn-action-save" data-test="edit-category-button">
            {{ __('Edit') }}
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="confirm-category-edit-{{ $category->id }}" :show="$errors->isNotEmpty()" focusable 
        class="max-w-lg !bg-dark !rounded-none border border-zinc-700 shadow-2xl">
        
        <form method="POST" wire:submit.prevent="editCategory" class="space-y-6 p-2">
            @csrf
            
            <div class="border-b border-zinc-800 pb-4">
                <flux:heading size="lg" class="font-black text-white uppercase tracking-tight">
                    {{ __('Edit Category') }}
                </flux:heading>
                <flux:subheading class="!text-zinc-400 font-bold uppercase tracking-widest text-[10px] mt-1">
                    {{ __('Editing details for: ') }} <span class="text-primary">{{ $category->name }}</span>
                </flux:subheading>
            </div>

            <div class="space-y-4">
                <div class="[&>label]:!text-zinc-400 [&_input]:!bg-zinc-800/50 [&_input]:!border-zinc-700 [&_input]:!text-white [&_input]:!rounded-none focus-within:[&_input]:!border-primary">
                    <flux:input wire:model="name" :label="__('Category Name')" />
                </div>

                <div class="[&>label]:!text-zinc-400 [&_textarea]:!bg-zinc-800/50 [&_textarea]:!border-zinc-700 [&_textarea]:!text-white [&_textarea]:!rounded-none focus-within:[&_textarea]:!border-primary">
                    <flux:textarea wire:model="description" :label="__('Category Description')" rows="5" />
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-6 border-t border-zinc-800 mt-6">
                <flux:modal.close>
                    <flux:button class="btn-action-subtle">
                        {{ __('Cancel') }}
                    </flux:button>
                </flux:modal.close>

                <flux:button type="submit" class="btn-action-save" data-test="confirm-edit-category-button">
                    {{ __('Save Changes') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>