<?php

use Livewire\Component;
use App\Models\Category;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

new class extends Component {
    public $name = '';
    public $description = '';
    public $slug = '';

    public function addCategory(): void
    {
        $slug = Str::slug($this->name);

        $this->validate([
            'name' => [
                'required', 
                'string', 
                'max:255', 
                Rule::unique('categories')->whereNull('deleted_at')
            ],
            'description' => 'nullable|string|max:255',
        ]);

        $slugExists = Category::where('slug', $slug)->whereNull('deleted_at')->exists();
        if ($slugExists) {
            $this->addError('name', __('A category with a similar name already exists.'));
            return;
        }

        $category = Category::onlyTrashed()->whereRaw('LOWER(name) = ?', [strtolower((string) $this->name)])->first();
        
        if ($category) {
            $category->restore();
            $category->update([
                'name' => $this->name,
                'description' => $this->description,
                'slug' => $slug,
            ]);
        } else {
            Category::create([
                'name' => $this->name, 
                'description' => $this->description,
                'slug' => $slug
            ]);
        }

        $this->reset(['name', 'description', 'slug']);
        $this->dispatch('categoryAdded');
        $this->modal('confirm-category-addition')->close();
    }
};
?>

<section>
    <flux:modal.trigger name="confirm-category-addition">
        <flux:button class="bg-primary hover:bg-accent text-dark border-none rounded-none font-black tracking-wider uppercase shadow-[4px_4px_0px_0px_#EAB308] hover:shadow-none hover:translate-y-1 hover:translate-x-1 transition-all" data-test="add-category-button">
            {{ __('Add new category') }}
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="confirm-category-addition" :show="$errors->isNotEmpty()" focusable class="max-w-lg rounded-none border-t-4 border-primary shadow-2xl bg-white dark:bg-dark">
        <form wire:submit.prevent="addCategory" class="space-y-6">
            @csrf
            
            <div class="border-b-2 border-gray-100 dark:border-gray-800 pb-4">
                <flux:heading size="lg" class="font-black text-text-main dark:text-white uppercase tracking-tight">
                    {{ __('Add New Category') }}
                </flux:heading>

                <flux:subheading class="text-xs font-bold text-gray-400 uppercase tracking-widest mt-1">
                    {{ __('Please fill in the details for the new category you want to add.') }}
                </flux:subheading>
            </div>

            <div class="space-y-4">
                <flux:input wire:model="name" :label="__('Category Name')" class="rounded-none border-gray-300 dark:border-gray-700 focus:ring-primary focus:border-primary" />
                <flux:textarea wire:model="description" :label="__('Category Description')" class="rounded-none border-gray-300 dark:border-gray-700 focus:ring-primary focus:border-primary" />
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t-2 border-gray-100 dark:border-gray-800 mt-6">
                <flux:modal.close>
                    <flux:button class="bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-text-main dark:text-white rounded-none font-bold uppercase tracking-wider transition-colors border-none">
                        {{ __('Cancel') }}
                    </flux:button>
                </flux:modal.close>

                <flux:button type="submit" class="bg-dark hover:bg-text-main text-primary rounded-none font-black uppercase tracking-wider transition-colors border-none" data-test="confirm-add-category-button">
                    {{ __('Add new category') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>