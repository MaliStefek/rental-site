<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Validation\Rule;
use App\Models\Tool;
use App\Models\Category;
use App\Enums\PricingType;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
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

    public array $prices = [];

    public function mount(Tool $tool)
    {
        $this->tool = $tool;
        $this->name = $tool->name;
        $this->description = $tool->description;
        $this->selectedCategoryId = $tool->category_id;
        $this->is_active = $tool->is_active;

        $this->categories = Category::all();

        $this->tool->load('prices');
        if ($this->tool->prices->count() > 0) {
            foreach ($this->tool->prices as $price) {
                $this->prices[] = [
                    'type' => $price->pricing_type,
                    'price' => number_format($price->price_cents / 100, 2, '.', '')
                ];
            }
        } else {
            $this->prices = [['type' => PricingType::DAILY->value, 'price' => null]];
        }
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

    public function editTool()
    {
        $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tools')->ignore($this->tool->id)->whereNull('deleted_at')
            ],
            'description' => 'nullable|string',
            'selectedCategoryId' => 'required|exists:categories,id',
            'is_active' => 'boolean',
            'image' => 'nullable|image|max:2048',

            'prices' => 'required|array|min:1',
            'prices.*.type' => ['required', 'string', 'distinct', Rule::enum(PricingType::class)],
            'prices.*.price' => ['required', 'numeric', 'min:0.01'],
        ]);

        $types = collect($this->prices)->pluck('type')->filter();
        if ($types->count() !== $types->unique()->count()) {
            $this->addError('prices', __('Each pricing type can only be used once.'));
            return;
        }

        $oldImagePath = null;

        DB::transaction(function () use (&$oldImagePath) {
            $path = null;

            try {
                if ($this->image) {
                    $path = $this->image->store('tools', 'public');

                    if ($this->tool->image_path) {
                        $oldImagePath = $this->tool->image_path;
                    }
                }

                $this->tool->update([
                    'name' => $this->name,
                    'description' => $this->description,
                    'category_id' => $this->selectedCategoryId,
                    'is_active' => $this->is_active,
                    'image_path' => $path ?? $this->tool->image_path,
                ]);

                $this->tool->prices()->delete();

                foreach ($this->prices as $priceData) {
                    $this->tool->prices()->create([
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

        if ($oldImagePath) {
            Storage::disk('public')->delete($oldImagePath);
        }

        $this->dispatch('toolUpdated', toolId: $this->tool->id);
        $this->modal("confirm-tool-edit-{$this->tool->id}")->close();
    }
};
?>

<section>
    <flux:modal.trigger name="confirm-tool-edit-{{ $tool->id }}">
        <flux:button size="sm" variant="primary" icon="pencil-square" class="btn-action-save"
            data-test="edit-tool-button">
            {{ __('Edit') }}
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="confirm-tool-edit-{{ $tool->id }}"
        class="max-w-7xl pt-12 !bg-dark !rounded-none border border-zinc-700 shadow-2xl">
        <form wire:submit="editTool" class="space-y-8 p-2">

            <div class="border-b border-zinc-800 pb-6">
                <flux:heading size="xl" class="font-black uppercase tracking-tight text-primary">
                    {{ __('Edit Tool') }}
                </flux:heading>
                <flux:subheading class="!text-zinc-400 font-bold uppercase tracking-widest text-xs mt-1">
                    {{ __('Editing details for: ') }} <span class="text-white font-mono">{{ $tool->name }}</span>
                </flux:subheading>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">

                <div class="space-y-6">
                    <div class="space-y-4">
                        <flux:heading size="md" class="font-black text-white uppercase tracking-tight">
                            {{ __('General Information') }}
                        </flux:heading>

                        <div
                            class="[&>label]:!text-zinc-400 [&_input]:!bg-zinc-800/50 [&_input]:!border-zinc-700 [&_input]:!text-white [&_input]:!rounded-none focus-within:[&_input]:!border-primary">
                            <flux:input wire:model="name" :label="__('Tool Name')" />
                        </div>

                        <div
                            class="[&>label]:!text-zinc-400 [&_textarea]:!bg-zinc-800/50 [&_textarea]:!border-zinc-700 [&_textarea]:!text-white [&_textarea]:!rounded-none focus-within:[&_textarea]:!border-primary">
                            <flux:textarea wire:model="description" :label="__('Tool Description')" rows="10" />
                        </div>

                        <div
                            class="[&>label]:!text-zinc-400 [&_select]:!bg-zinc-800/50 [&_select]:!border-zinc-700 [&_select]:!text-white [&_select]:!rounded-none focus-within:[&_select]:!border-primary">
                            <flux:select wire:model="selectedCategoryId" :label="__('Category')">
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
                                {{ __('Pricing Tiers') }}
                            </flux:heading>

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
                                <div class="flex items-start gap-3" wire:key="edit-price-row-{{ $index }}">
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
                            @endforeach
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="space-y-3">
                            <flux:label class="font-black uppercase text-[10px] tracking-[0.2em] text-zinc-500">
                                {{ __('Tool Image') }}
                            </flux:label>

                            <div
                                class="relative group w-full h-40 border-2 border-dashed border-zinc-700 flex items-center justify-center bg-zinc-900/50 overflow-hidden transition-colors hover:border-primary/50">
                                @if ($this->imagePreviewUrl)
                                    <img src="{{ $this->imagePreviewUrl }}" class="w-full h-full object-cover" />
                                @elseif ($tool->image_path)
                                    <img src="{{ asset('storage/' . $tool->image_path) }}"
                                        class="w-full h-full object-cover" />
                                @else
                                    <div class="text-center">
                                        <flux:icon.camera class="w-8 h-8 text-zinc-600 mx-auto mb-2" />
                                        <span
                                            class="text-[10px] font-bold text-zinc-500 uppercase tracking-widest">{{ __('Upload Photo') }}</span>
                                    </div>
                                @endif

                                <div
                                    class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                                    <span
                                        class="text-xs font-black text-white uppercase tracking-widest">{{ __('Change Image') }}</span>
                                </div>

                                <input type="file" wire:model="image" class="absolute inset-0 opacity-0 cursor-pointer"
                                    accept="image/*" />
                            </div>
                            <flux:error name="image" class="!text-red-400" />
                        </div>

                        <div class="flex flex-col justify-center space-y-6 border border-zinc-800 p-6 bg-zinc-900/20">
                            <div>
                                <flux:heading size="sm" class="font-black uppercase tracking-widest text-zinc-400 mb-4">
                                    {{ __('Availability') }}
                                </flux:heading>
                                <div
                                    class="[&_input]:!bg-zinc-800 [&_input]:!border-zinc-600 checked:[&_input]:!bg-primary checked:[&_input]:!border-primary">
                                    <flux:checkbox wire:model="is_active" :label="__('Visible to customers')"
                                        class="font-bold text-white uppercase tracking-tighter" />
                                </div>
                                <p class="text-[10px] text-zinc-500 mt-2 italic">
                                    {{ __('Toggle visibility in the public catalog.') }}
                                </p>
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

                <flux:button type="submit" class="btn-action-save" data-test="confirm-edit-tool-button">
                    {{ __('Save Changes') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>