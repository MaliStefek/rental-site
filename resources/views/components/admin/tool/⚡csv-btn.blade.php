<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Tool;
use Illuminate\Support\Facades\DB;

new class extends Component {
    use WithFileUploads;

    public Tool $tool;
    public $csvFile;

    public function import(): void
    {
        $this->validate([
            'csvFile' => 'required|mimes:csv,txt|max:1024',
        ]);

        $path = $this->csvFile->getRealPath();
        $file = fopen($path, 'r');
        
        try {
            $firstLine = fgets($file);
            $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
            rewind($file);

            $count = DB::transaction(function () use ($file, $delimiter): int {
                fgetcsv($file, 0, $delimiter);

                $count = 0;
                while (($row = fgetcsv($file, 0, $delimiter)) !== FALSE) {
                    if (empty($row[0])) {
                        continue;
                    }

                    $this->tool->assets()->firstOrCreate(
                        ['sku' => trim($row[0])],
                        [
                            'serial_number'  => isset($row[1]) ? trim($row[1]) : null,
                            'internal_notes' => isset($row[2]) ? trim($row[2]) : null,
                            'status'         => 'available'
                        ]
                    );
                    $count++;
                }
                return $count;
            });

        } finally {
            fclose($file);
        }

        $this->reset('csvFile');
        $this->modal("import-assets-{$this->tool->id}")->close();
        
        $this->dispatch('assetsImported');
    }
}; ?>

<section>
    <flux:modal.trigger name="import-assets-{{ $tool->id }}">
        <flux:button icon="document-plus" size="sm" class="btn-action-neutral">
            {{ __('Import CSV') }}
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="import-assets-{{ $tool->id }}" class="max-w-lg !bg-dark !rounded-none border border-zinc-700 shadow-2xl">
        <form wire:submit.prevent="import" class="space-y-6 p-2">
            
            <div class="border-b border-zinc-800 pb-4">
                <flux:heading size="lg" class="font-black uppercase tracking-tight !text-primary">
                    {{ __('Bulk Add Units') }}
                </flux:heading>
                <flux:subheading class="!text-zinc-400 font-bold uppercase tracking-widest text-[10px] mt-1">
                    {{ __('Importing for: ') }} <span class="text-white">{{ $tool->name }}</span>
                </flux:subheading>
            </div>

            <div class="space-y-4">
                <div class="p-6 border-2 border-dashed border-zinc-800 bg-zinc-900/30 flex flex-col items-center justify-center text-center group hover:border-primary/50 transition-colors relative">
                    <flux:icon.document-text class="w-8 h-8 text-zinc-600 mb-3 group-hover:text-primary transition-colors" />
                    
                    <flux:label class="!text-zinc-300 font-bold uppercase tracking-tighter cursor-pointer">
                        {{ __('Select CSV File') }}
                        <input 
                            type="file" 
                            wire:model="csvFile" 
                            class="absolute inset-0 opacity-0 cursor-pointer"
                            accept=".csv"
                        />
                    </flux:label>
                    
                    <flux:text size="xs" class="!text-zinc-500 mt-1 uppercase font-black">
                        {{ $csvFile ? $csvFile->getClientOriginalName() : __('Click to browse') }}
                    </flux:text>
                </div>
                
                <div class="bg-zinc-800/50 p-3 border-l-2 border-primary">
                    <flux:text size="xs" class="!text-zinc-400 leading-relaxed font-mono uppercase">
                        <span class="text-primary font-bold">{{ __('Format:') }}</span> SKU, Serial Number, Notes
                    </flux:text>
                </div>
                
                <flux:error name="csvFile" class="!text-red-400 font-bold text-xs uppercase" />
            </div>

            <div class="flex justify-end gap-3 pt-6 border-t border-zinc-800 mt-6">
                <flux:modal.close>
                    <flux:button class="btn-action-subtle">
                        {{ __('Cancel') }}
                    </flux:button>
                </flux:modal.close>
                
                <flux:button type="submit" class="btn-action-save" wire:loading.attr="disabled">
                    <div wire:loading.flex wire:target="import" class="items-center gap-2">
                        <flux:icon.arrow-path class="w-4 h-4 animate-spin" />
                        {{ __('Processing...') }}
                    </div>
                    <span wire:loading.remove wire:target="import">{{ __('Start Import') }}</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>