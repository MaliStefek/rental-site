<?php

use Livewire\Component;
use App\Models\Tool;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public Tool $tool;

    #[On('toolUpdated')]
    public function reloadTools($toolId)
    {
        if ($this->tool->id == $toolId) {
            $this->tool->refresh()->load('category');
        }
    }
};
?>
<flux:card class="flex flex-col justify-between h-full transition-shadow hover:shadow-md overflow-hidden">
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <flux:text size="xs" class="uppercase tracking-widest font-semibold text-zinc-400">
                {{ $tool->category?->name ?? __('Uncategorized') }}
            </flux:text>
            
            @if (auth()->user()->isAdmin())
                <flux:badge size="sm" :color="$tool->is_active ? 'green' : 'zinc'" inset="top bottom">
                    {{ $tool->is_active ? __('Active') : __('Inactive') }}
                </flux:badge>
            @endif
        </div>

        <div class="relative group">
            @if($tool->image_path)
                <img src="{{ asset('storage/'.$tool->image_path) }}" alt="{{ $tool->name }}" class="w-full h-48 object-cover rounded-lg border border-zinc-100 dark:border-white/10" />
            @else
                <div class="w-full h-48 bg-zinc-50 dark:bg-white/5 border border-dashed border-zinc-200 dark:border-white/10 rounded-lg flex items-center justify-center">
                    <flux:icon.camera class="text-zinc-300" />
                </div>
            @endif
        </div>

        <div>
            <flux:heading size="lg" class="font-bold tracking-tight">
                {{ $tool->name }}
            </flux:heading>
            
            @if($tool->description)
                <flux:text class="line-clamp-2 mt-2">
                    {{ $tool->description }}
                </flux:text>
            @else
                <flux:text class="italic text-zinc-400 mt-2">
                    {{ __('No description provided.') }}
                </flux:text>
            @endif
        </div>
    </div>

    <div class="mt-6 pt-4 border-t border-zinc-100 dark:border-white/10 flex justify-end items-center gap-2">
        <livewire:admin.tool.csv-btn :tool="$tool" :wire:key="'csv-'.$tool->id" />
        <livewire:admin.tool.edit-btn :tool="$tool" :wire:key="'edit-'.$tool->id" />
        <livewire:admin.tool.delete-btn :tool="$tool" :wire:key="'delete-'.$tool->id" />
    </div>
</flux:card>