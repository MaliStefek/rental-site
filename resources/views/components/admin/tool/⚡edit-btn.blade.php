<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Validation\Rule;
use App\Models\Tool;
use App\Models\Category;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    use WithFileUploads; 

    public Tool $tool;
    public $name;
    public $description;
    public $categories;
    public $selectedCategoryId;
    public $is_active;
    public $image;

    public function mount(Tool $tool)
    {
        $this->tool = $tool;
        $this->name = $tool->name;
        $this->description = $tool->description;
        $this->selectedCategoryId = $tool->category_id;
        $this->is_active = $tool->is_active;
        
        $this->categories = Category::all();
    }

    public function editTool()
    {
        $this->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('tools')->ignore($this->tool->id)->whereNull('deleted_at')
            ],
            'description' => 'nullable|string|max:255',
            'selectedCategoryId' => 'required|exists:categories,id',
            'is_active' => 'boolean',
            'image' => 'nullable|image|max:2048',
        ]);

        $imagePath = $this->tool->image_path;

        if ($this->image) {
            $newImagePath = $this->image->store('tools', 'public');
            
            if ($this->tool->image_path) {
                Storage::disk('public')->delete($this->tool->image_path);
            }
            $imagePath = $newImagePath;
        }

        $this->tool->update([
            'name' => $this->name,
            'description' => $this->description,
            'category_id' => $this->selectedCategoryId,
            'is_active' => $this->is_active,
            'image_path' => $imagePath,
        ]);

        $this->dispatch('toolUpdated', toolId: $this->tool->id);
        $this->modal("confirm-tool-edit-{$this->tool->id}")->close();
    }
};
?>

<section class="mt-10 space-y-6">
    <flux:modal.trigger name="confirm-tool-edit-{{ $tool->id }}">
        <flux:button variant="primary" data-test="edit-tool-button">
            {{ __('Edit tool') }}
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="confirm-tool-edit-{{ $tool->id }}" class="max-w-lg">
        <form wire:submit="editTool" class="space-y-6">
            <flux:heading size="lg">{{ __('Edit Tool') }}</flux:heading>

            <flux:input wire:model="name" :label="__('Tool Name')" />
            <flux:textarea wire:model="description" :label="__('Tool Description')" />

            <flux:select wire:model="selectedCategoryId" :label="__('Category')">
                @foreach ($categories as $category)
                    <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="space-y-2">
                <flux:label>{{ __('Update Image') }}</flux:label>
                <input type="file" wire:model="image" class="block w-full text-sm" accept="image/*" />
                <div class="mt-2">
                    @if ($image) 
                        <img src="{{ $image->temporaryUrl() }}" class="w-20 h-20 rounded shadow-sm object-cover" />
                    @elseif ($tool->image_path)
                        <img src="{{ asset('storage/'.$tool->image_path) }}" class="w-20 h-20 rounded shadow-sm object-cover" />
                    @endif
                </div>
            </div>

            <flux:checkbox wire:model="is_active" :label="__('Available for rent')" />

            <div class="flex justify-end space-x-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="primary" type="submit">
                    {{ __('Save Changes') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>