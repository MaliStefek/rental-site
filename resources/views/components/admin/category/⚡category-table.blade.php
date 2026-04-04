<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Category;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;

new class extends Component
{
    use WithPagination;

    #[On('categoryAdded'), On('categoryUpdated'), On('categoryDeleted')]
    public function refreshTable(): void
    {
        unset($this->categories);
    }

    #[Computed]
    public function categories()
    {
        return Category::latest()->paginate(15);
    }
};
?>

<div class="mt-6 bg-dark p-6 space-y-4">
    <flux:table :paginate="$this->categories" class="text-primary">
        <flux:table.columns>
            <flux:table.column class="!text-primary font-black uppercase tracking-widest">
                {{ __('Category Name') }}
            </flux:table.column>
            
            <flux:table.column class="w-full !text-primary font-black uppercase tracking-widest">
                {{ __('Description') }}
            </flux:table.column>
            
            <flux:table.column class="text-right !text-primary font-black uppercase tracking-widest">
                {{ __('Actions') }}
            </flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->categories as $category)
                <flux:table.row :key="$category->id" class="border-b border-primary/20 hover:bg-primary/5">
                    
                    <flux:table.cell class="font-bold text-primary">
                        {{ $category->name }}
                    </flux:table.cell>

                    <flux:table.cell class="text-primary/80">
                        <div class="line-clamp-2 max-w-xl">
                            {{ $category->description ?: __('No description provided.') }}
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-2">
                            <livewire:admin.category.edit-btn :category="$category" :wire:key="'edit-'.$category->id" />
                            <livewire:admin.category.delete-btn :category="$category" :wire:key="'delete-'.$category->id" />
                        </div>
                    </flux:table.cell>

                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="3" class="text-center py-12 text-primary/80 font-bold uppercase tracking-widest">
                        {{ __('No categories yet. Create your first one above!') }}
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>