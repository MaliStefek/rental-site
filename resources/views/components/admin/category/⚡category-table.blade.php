<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Category;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;

new class extends Component
{
    use WithPagination;

    #[On('categoryAdded')]
    #[On('categoryUpdated')]
    #[On('categoryDeleted')]
    public function refreshTable()
    {
        
    }

    #[Computed]
    public function categories()
    {
        return Category::latest()->paginate(15);
    }
};
?>

<div class="mt-6">
    <flux:table :paginate="$this->categories">
        <flux:table.columns>
            <flux:table.column>{{ __('Category Name') }}</flux:table.column>
            <flux:table.column class="w-full">{{ __('Description') }}</flux:table.column>
            <flux:table.column class="text-right">{{ __('Actions') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->categories as $category)
                <flux:table.row :key="$category->id">
                    
                    <flux:table.cell class="font-medium text-zinc-800 dark:text-white">
                        {{ $category->name }}
                    </flux:table.cell>

                    <flux:table.cell class="text-zinc-500 max-w-xs truncate">
                        {{ $category->description ?: __('No description provided.') }}
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="flex items-center gap-2">
                            <livewire:admin.category.edit-btn :category="$category" :wire:key="'edit-'.$category->id" />
                            <livewire:admin.category.delete-btn :category="$category" :wire:key="'delete-'.$category->id" />
                        </div>
                    </flux:table.cell>

                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="3" class="text-center py-8 text-zinc-500">
                        {{ __('No categories found. Create your first one above!') }}
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>