<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Validate;
use App\Models\Category;
use App\Models\Tool;
use App\Enums\PricingType;
use App\Enums\AppEvents;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\UniqueConstraintViolationException;

new class extends Component {
    use WithFileUploads;

    public $categories;

    #[Validate('required|string|max:255|unique:tools,name')]
    public string $name = '';
    
    #[Validate('nullable|string')]
    public ?string $description = null;
    
    #[Validate('required|exists:categories,id')]
    public string $selectedCategoryId = '';
    
    #[Validate('boolean')]
    public bool $is_active = true;
    
    #[Validate('nullable|image|max:2048')]
    public $image;

    #[Validate([
        'prices' => 'required|array|min:1',
        'prices.*.type' => 'required|string',
        'prices.*.price' => 'required|numeric|min:0.01|max:99999.99'
    ])]
    public array $prices = [];

    public function mount()
    {
        $this->categories = Category::all();

        $this->prices = [
            ['type' => PricingType::DAILY_SHORT->value, 'price' => null]
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
        $this->authorize('create', Tool::class);
        $this->validate();

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
                            'price_cents' => (int) round((float) str_replace(',', '.', (string) $priceData['price']) * 100),
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
        $this->prices = [['type' => PricingType::DAILY_SHORT->value, 'price' => null]];

        $this->dispatch(AppEvents::TOOL_ADDED->value);
        $this->modal('confirm-tool-addition')->close();
    }
};
?>

<section class="h-full max-w-sm mx-auto w-full">
    <flux:modal.trigger name="confirm-tool-addition" class="cursor-pointer group h-full w-full block">
        <div class="flex flex-col h-full rounded-2xl border-2 border-dashed border-zinc-200 bg-zinc-50/50 hover:bg-white hover:border-primary/50 transition-all duration-300">
            
            <div class="w-full h-80 flex items-center justify-center border-b border-transparent shrink-0">
                <div class="w-20 h-20 bg-white rounded-full shadow-sm flex items-center justify-center group-hover:scale-110 group-hover:shadow-primary/20 transition-all duration-500">
                    <flux:icon.plus class="w-10 h-10 text-primary" />
                </div>
            </div>

            <div class="p-6 flex flex-col flex-1 text-center">
                <h3 class="text-3xl font-black text-zinc-950 leading-tight mb-2 uppercase tracking-tight">
                    {{ __('Add New') }}
                </h3>

                <p class="text-sm text-zinc-500 leading-relaxed px-4">
                    {{ __('Register new heavy machinery into the catalog.') }}
                </p>
                
                <div class="mt-auto">
                    <div class="pt-5 border-t border-transparent mb-6">
                         <div class="h-10"></div> 
                    </div>
                </div>
            </div>
        </div>
    </flux:modal.trigger>

    <flux:modal name="confirm-tool-addition"
        class="max-w-7xl pt-12 !bg-dark !rounded-none border border-zinc-700 shadow-2xl">
        <form wire:submit.prevent="addTool" class="space-y-8 p-2">

            <div class="border-b border-zinc-800 pb-6">
                <flux:heading size="xl" class="font-black uppercase tracking-tight text-primary">
                    {{ __('Create New Tool') }}</flux:heading>
                <flux:subheading class="!text-zinc-400 font-bold uppercase tracking-widest text-xs mt-1">
                    {{ __('Enter the details to add a new item to your catalog') }}
                </flux:subheading>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">

                <div class="space-y-6">
                    <div class="space-y-4">
                        <flux:heading size="md" class="font-black text-white uppercase tracking-tight">
                            {{ __('General Information') }}</flux:heading>

                        <div
                            class="[&>label]:!text-zinc-400 [&_input]:!bg-zinc-800/50 [&_input]:!border-zinc-700 [&_input]:!text-white [&_input]:!rounded-none focus-within:[&_input]:!border-primary">
                            <flux:input wire:model="name" :label="__('Tool Name')" placeholder="e.g. Hilti TE-70" />
                        </div>

                        <div
                            class="[&>label]:!text-zinc-400 [&_textarea]:!bg-zinc-800/50 [&_textarea]:!border-zinc-700 [&_textarea]:!text-white [&_textarea]:!rounded-none focus-within:[&_textarea]:!border-primary">
                            <flux:textarea wire:model="description" :label="__('Description')" rows="10"
                                placeholder="Technical specifications, usage instructions..." />
                        </div>

                        <div
                            class="[&>label]:!text-zinc-400 [&_select]:!bg-zinc-800/50 [&_select]:!border-zinc-700 [&_select]:!text-white [&_select]:!rounded-none focus-within:[&_select]:!border-primary">
                            <flux:select wire:model="selectedCategoryId" :label="__('Category')"
                                placeholder="Select a category">
                                @foreach ($categories as $category)
                                    <flux:select.option value="{{ $category->id }}">{{ $category->name }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>
                    </div>
                </div>

                <div class="space-y-8">

                    <div class="p-6 border border-zinc-800 bg-zinc-900/30 rounded-none space-y-4 shadow-inner">
                        <div class="flex items-center justify-between mb-4">
                            <flux:heading size="sm" class="font-black uppercase tracking-widest text-zinc-400">
                                {{ __('Pricing Tiers') }}</flux:heading>

                            @if(count($prices) < count(\App\Enums\PricingType::cases()))
                                <flux:button size="xs" icon="plus" wire:click="addPriceRow" type="button"
                                    class="rounded-none !bg-zinc-800 hover:!bg-zinc-700 !border-zinc-600">
                                    {{ __('Add Type') }}
                                </flux:button>
                            @endif
                        </div>

                        <flux:error name="prices" class="!text-red-400 font-bold text-xs uppercase" />

                        <div class="space-y-3">
                            @php $selectedTypes = collect($prices)->pluck('type')->filter()->toArray(); @endphp

                            @foreach($prices as $index => $priceRow)
                                <div class="flex flex-col gap-1" wire:key="add-price-row-{{ $index }}">
                                    <div class="flex items-start gap-3">
                                        <div
                                            class="flex-1 [&_select]:!bg-zinc-800/50 [&_select]:!border-zinc-700 [&_select]:!text-white [&_select]:!rounded-none">
                                            <flux:select wire:model="prices.{{ $index }}.type">
                                                <flux:select.option value="" disabled>{{ __('Type') }}</flux:select.option>
                                                @foreach(\App\Enums\PricingType::cases() as $case)
                                                    @php $isDisabled = in_array($case->value, $selectedTypes) && $prices[$index]['type'] !== $case->value; @endphp
                                                    <flux:select.option value="{{ $case->value }}" :disabled="$isDisabled">
                                                        {{ ucfirst($case->value) }}
                                                    </flux:select.option>
                                                @endforeach
                                            </flux:select>
                                        </div>

                                        <div
                                            class="flex-1 [&_input]:!bg-zinc-800/50 [&_input]:!border-zinc-700 [&_input]:!text-white [&_input]:!rounded-none [&_svg]:!text-primary">
                                            <flux:input wire:model="prices.{{ $index }}.price" type="number" step="0.01"
                                                icon="currency-euro" placeholder="0.00" />
                                        </div>

                                        @if($index > 0)
                                            <flux:button variant="danger" icon="trash"
                                                class="!rounded-none border-none !bg-red-900/20 hover:!bg-red-600 !text-red-400 hover:!text-white transition-all"
                                                wire:click="removePriceRow({{ $index }})" type="button" />
                                        @else
                                            <div class="w-10"></div>
                                        @endif
                                    </div>
                                    
                                    <div class="grid grid-cols-2 gap-3 px-1">
                                        <flux:error name="prices.{{ $index }}.type" class="!text-red-400 font-bold text-[10px] uppercase" />
                                        <flux:error name="prices.{{ $index }}.price" class="!text-red-400 font-bold text-[10px] uppercase" />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="space-y-3">
                            <flux:label class="font-black uppercase text-[10px] tracking-[0.2em] text-zinc-500">
                                {{ __('Featured Image') }}</flux:label>

                            <div
                                class="relative group w-full h-40 border-2 border-dashed border-zinc-700 flex items-center justify-center bg-zinc-900/50 overflow-hidden transition-colors hover:border-primary/50">
                                @if ($this->imagePreviewUrl)
                                    <img src="{{ $this->imagePreviewUrl }}" class="w-full h-full object-cover" />
                                    <div
                                        class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                                        <span
                                            class="text-xs font-black text-white uppercase tracking-widest">{{ __('Change') }}</span>
                                    </div>
                                @else
                                    <div class="text-center">
                                        <flux:icon.camera class="w-8 h-8 text-zinc-600 mx-auto mb-2" />
                                        <span
                                            class="text-[10px] font-bold text-zinc-500 uppercase tracking-widest">{{ __('Upload Photo') }}</span>
                                    </div>
                                @endif

                                <input type="file" wire:model="image" class="absolute inset-0 opacity-0 cursor-pointer"
                                    accept="image/*" />
                            </div>
                            <flux:error name="image" class="!text-red-400" />
                        </div>

                        <div class="flex flex-col justify-center space-y-6 border border-zinc-800 p-6 bg-zinc-900/20">
                            <div>
                                <flux:heading size="sm" class="font-black uppercase tracking-widest text-zinc-400 mb-4">
                                    {{ __('Status') }}</flux:heading>
                                <div
                                    class="[&_input]:!bg-zinc-800 [&_input]:!border-zinc-600 checked:[&_input]:!bg-primary checked:[&_input]:!border-primary">
                                    <flux:checkbox label="Is Active?" wire:model="is_active"
                                        class="font-bold text-white uppercase tracking-tighter" />
                                </div>
                                <p class="text-[10px] text-zinc-500 mt-2 italic">
                                    {{ __('If inactive, customers cannot see or rent this tool.') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-8 border-t border-zinc-800 mt-4">
                <flux:modal.close>
                    <flux:button variant="subtle" class="btn-action-subtle">
                        {{ __('Cancel') }}
                    </flux:button>
                </flux:modal.close>

                <flux:button type="submit" class="btn-action-save" data-test="confirm-add-tool-button">
                    {{ __('Save Tool') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>