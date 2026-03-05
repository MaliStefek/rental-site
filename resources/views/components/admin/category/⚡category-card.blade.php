<?php

use Livewire\Component;
use App\Models\Category;
use Livewire\Attributes\On;

new class extends Component
{
    public Category $category;

    #[On('categoryUpdated')]
    public function reloadCategory($categoryId)
    {
        if ($this->category->id == $categoryId) {
            $this->category->refresh();
        }
    }
};
?>
<flux:card class="flex flex-col justify-between h-full transition-shadow hover:shadow-md">
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-primary/10 text-primary">
                <flux:icon.rectangle-stack variant="mini" />
            </div>
            
            <flux:badge size="sm" color="zinc" inset="top bottom">Active</flux:badge>
        </div>

        <div>
            <flux:heading size="lg" class="font-bold tracking-tight">
                {{ $category->name }}
            </flux:heading>
            
            @if($category->description)
                <flux:text class="line-clamp-2 mt-1">
                    {{ $category->description }}
                </flux:text>
            @else
                <flux:text class="italic text-zinc-400 mt-1">
                    {{ __('No description provided.') }}
                </flux:text>
            @endif
        </div>
    </div>

    <div class="mt-6 pt-4 border-t border-zinc-100 dark:border-white/10 flex justify-end items-center gap-2">
        <livewire:admin.category.edit-btn :category="$category" :wire:key="'edit-'.$category->id" />
        <livewire:admin.category.delete-btn :category="$category" :wire:key="'delete-'.$category->id" />
    </div>
</flux:card>