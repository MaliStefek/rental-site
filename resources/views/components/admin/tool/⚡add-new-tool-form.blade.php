<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Category;
use App\Models\Tool;
use App\Enums\PricingType;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\UniqueConstraintViolationException;

new class extends Component
{
    use WithFileUploads; 

    public $categories;

    public $name = '';
    public $description = '';
    public $selectedCategoryId = '';
    public $is_active = true;
    public $image;
    
    public array $prices = [];

    public function mount()
    {
        $this->categories = Category::all();
        
        $this->prices = [
            ['type' => PricingType::DAILY->value, 'price' => null]
        ];
    }

    public function addPriceRow()
    {
        if (count($this->prices) < count(PricingType::cases())) {
            $this->prices[] = ['type' => '', 'price' => null];
        }
    }

    public function removePriceRow($index)
    {
        unset($this->prices[$index]);
        $this->prices = array_values($this->prices);
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
            
            'prices' => 'required|array|min:1',
            'prices.*.type' => ['required', 'string', Rule::enum(PricingType::class)],
            'prices.*.price' => ['required', 'numeric', 'min:0.01'],
        ]);

        $types = collect($this->prices)->pluck('type')->filter();
        if ($types->count() !== $types->unique()->count()) {
            $this->addError('prices', __('Each pricing type can only be used once.'));
            return;
        }

        $oldImagePath = null;
        $path = null;

        try {
            DB::transaction(function () use (&$oldImagePath, &$path) {
                $tool = Tool::onlyTrashed()->where('name', $this->name)->first();
                
                try {
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
                        $tool = Tool::create([
                            'name' => $this->name, 
                            'description' => $this->description,
                            'slug' => Str::slug($this->name),
                            'category_id' => $this->selectedCategoryId,
                            'is_active' => $this->is_active,
                            'image_path' => $path,
                        ]);
                    }

                    $tool->prices()->delete(); 
                    
                    foreach ($this->prices as $priceData) {
                        $tool->prices()->create([
                            'pricing_type' => $priceData['type'],
                            'price_cents' => (int) round($priceData['price'] * 100), 
                        ]);
                    }
                } catch (\Throwable $e) {
                    if ($path) {
                        Storage::disk('public')->delete($path);
                    }
                    throw $e;
                }
            });
            
        } catch (UniqueConstraintViolationException $e) {
            if ($path) {
                 Storage::disk('public')->delete($path);
            }
            $this->addError('name', __('A tool with this name was just created. Please try a different name.'));
            return;
        }

        if ($oldImagePath) {
            Storage::disk('public')->delete($oldImagePath);
        }

        $this->reset(['name', 'description', 'selectedCategoryId', 'image']);
        $this->is_active = true;
        $this->prices = [['type' => PricingType::DAILY->value, 'price' => null]];
        
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

    <flux:modal name="confirm-tool-addition" class="max-w-lg pt-12">
        <form wire:submit="addTool" class="space-y-6">
            <flux:heading size="lg">{{ __('Add New Tool') }}</flux:heading>

            <flux:input wire:model="name" :label="__('Tool Name')" />
            <flux:textarea wire:model="description" :label="__('Description')" />

            <flux:select wire:model="selectedCategoryId" :label="__('Category')" placeholder="Choose a category...">
                @foreach ($categories as $category)
                    <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="space-y-4 p-4 border border-zinc-200 dark:border-white/10 rounded-xl">
                <div class="flex items-center justify-between">
                    <flux:heading size="sm" class="font-semibold">{{ __('Pricing Tiers') }}</flux:heading>
                    
                    @if(count($prices) < count(\App\Enums\PricingType::cases()))
                        <flux:button size="xs" icon="plus" wire:click="addPriceRow" type="button">
                            {{ __('Add Type') }}
                        </flux:button>
                    @endif
                </div>

                <flux:error name="prices" />

                <div class="space-y-3">
                    @php
                        $selectedTypes = collect($prices)->pluck('type')->filter()->toArray();
                    @endphp

                    @foreach($prices as $index => $priceRow)
                        <div class="flex items-start gap-3" wire:key="price-row-{{ $index }}">
                            
                            <div class="flex-1">
                                <flux:select wire:model="prices.{{ $index }}.type" aria-label="{{ __('Pricing Type') }}">
                                    <flux:select.option value="" disabled>{{ __('Select type...') }}</flux:select.option>
                                    
                                    @foreach(\App\Enums\PricingType::cases() as $case)
                                        @php
                                            $isDisabled = in_array($case->value, $selectedTypes) && $prices[$index]['type'] !== $case->value;
                                        @endphp
                                        <flux:select.option value="{{ $case->value }}" :disabled="$isDisabled">
                                            {{ ucfirst($case->value) }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>

                            <div class="flex-1 relative">
                                <flux:input 
                                    wire:model="prices.{{ $index }}.price" 
                                    type="number" 
                                    step="0.01" 
                                    min="0"
                                    icon="currency-dollar" 
                                    placeholder="0.00" 
                                    aria-label="{{ __('Price') }}" 
                                />
                            </div>

                            @if($index > 0)
                                <flux:button variant="danger" icon="trash" class="mt-1" wire:click="removePriceRow({{ $index }})" aria-label="{{ __('Remove price') }}" type="button" />
                            @else
                                <div class="w-10 mt-1"></div> 
                            @endif
                        </div>
                        
                        <div class="flex gap-3 mt-1">
                            <div class="flex-1"><flux:error name="prices.{{ $index }}.type" /></div>
                            <div class="flex-1"><flux:error name="prices.{{ $index }}.price" /></div>
                            <div class="w-10"></div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="space-y-2">
                <flux:label for="image-upload">{{ __('Featured Image') }}</flux:label>
                
                @if ($this->imagePreviewUrl)
                    <img src="{{ $this->imagePreviewUrl }}" class="w-full h-48 object-cover rounded-lg mb-2" alt="{{ __('Image preview') }}">
                @endif
                
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