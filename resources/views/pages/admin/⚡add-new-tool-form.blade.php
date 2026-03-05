<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Category;
use App\Models\Tool;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

new class extends Component
{
    use WithFileUploads; 

    public $categories;

    public $name = '';
    public $description = '';
    public $selectedCategoryId = '';
    public $is_active = true;
    public $image;

    public function mount()
    {
        $this->categories = Category::all();
    }

    public function getImagePreviewUrlProperty()
    {
        try {
            return $this->image?->temporaryUrl();
        } catch (\RuntimeException $e) {
            return null;
        }
    }

    public function addTool()
    {
        $this->validate([
            'name' => [
                'required', 'string', 'max:255', 
                Rule::unique('tools')->whereNull('deleted_at')
            ],
            'description' => 'nullable|string|max:255',
            'selectedCategoryId' => 'required|exists:categories,id',
            'is_active' => 'boolean',
            'image' => 'nullable|image|max:2048',
        ]);

        $oldImagePath = null;

        DB::transaction(function () use (&$oldImagePath) {
            $tool = Tool::onlyTrashed()->where('name', $this->name)->first();
            $path = $this->image ? $this->image->store('tools', 'public') : null;

            if ($tool) {
                if ($path && $tool->image_path) {
                    $oldImagePath = $tool->image_path;
                }

                $tool->restore();
                $tool->update([
                    'description' => $this->description,
                    'slug' => Str::slug($this->name),
                    'category_id' => $this->selectedCategoryId,
                    'is_active' => $this->is_active,
                    'image_path' => $path ?? $tool->image_path,
                ]);
            } else {
                Tool::create([
                    'name' => $this->name, 
                    'description' => $this->description,
                    'slug' => Str::slug($this->name),
                    'category_id' => $this->selectedCategoryId,
                    'is_active' => $this->is_active,
                    'image_path' => $path,
                ]);
            }
        });

        if ($oldImagePath) {
            Storage::disk('public')->delete($oldImagePath);
        }

        $this->reset(['name', 'description', 'selectedCategoryId', 'image', 'is_active']);
        $this->is_active = true;
        $this->dispatch('toolAdded');
        $this->modal('confirm-tool-addition')->close();
    }
};
?>

<section class="mt-10 space-y-6">
    <div class="relative mb-5">
        <flux:heading>{{ __('Add New Tool') }}</flux:heading>
        <flux:subheading>{{ __('Add a new tool to your rental site') }}</flux:subheading>
    </div>

    <flux:modal.trigger name="confirm-tool-addition">
        <flux:button variant="primary">{{ __('Add new tool') }}</flux:button>
    </flux:modal.trigger>

    <flux:modal name="confirm-tool-addition" class="max-w-lg">
        <form wire:submit="addTool" class="space-y-6">
            <flux:heading size="lg">{{ __('Add New Tool') }}</flux:heading>

            <flux:input wire:model="name" :label="__('Tool Name')" />
            <flux:textarea wire:model="description" :label="__('Description')" />

            <flux:select wire:model="selectedCategoryId" :label="__('Category')" placeholder="Choose a category...">
                @foreach ($categories as $category)
                    <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="space-y-2">
                {{-- Added 'for' attribute for screen reader accessibility --}}
                <flux:label for="image-upload">{{ __('Featured Image') }}</flux:label>
                
                {{-- Switched to the safe computed property for the preview URL --}}
                @if ($this->imagePreviewUrl)
                    <img src="{{ $this->imagePreviewUrl }}" class="w-full h-48 object-cover rounded-lg mb-2" alt="{{ __('Image preview') }}">
                @endif
                
                {{-- Added 'id' to match the label --}}
                <input type="file" wire:model="image" id="image-upload" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-primary/90">
                <flux:error name="image" />
            </div>

            <flux:checkbox label="Is Active?" wire:model="is_active" />

            <div class="flex justify-end space-x-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="primary" type="submit">
                    {{ __('Save Tool') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>