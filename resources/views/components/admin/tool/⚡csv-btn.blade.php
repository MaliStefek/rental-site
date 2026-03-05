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

            $count = DB::transaction(function () use ($file, $delimiter) {
                fgetcsv($file, 0, $delimiter);

                $count = 0;
                while (($row = fgetcsv($file, 0, $delimiter)) !== FALSE) {
                    if (empty($row[0])) continue;

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
        <flux:button variant="subtle" icon="document-plus" size="sm">
            {{ __('Import CSV') }}
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="import-assets-{{ $tool->id }}" class="max-w-lg">
        <form wire:submit.prevent="import" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Bulk Add Units') }}</flux:heading>
                <flux:subheading>{{ __('Importing for: ') }} <strong>{{ $tool->name }}</strong></flux:subheading>
            </div>

            <div class="space-y-2">
                <flux:label>{{ __('Select CSV File') }}</flux:label>
                <input 
                    type="file" 
                    wire:model="csvFile" 
                    class="block w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-zinc-100 file:text-zinc-700 hover:file:bg-zinc-200 dark:file:bg-white/10 dark:file:text-zinc-300" 
                />
                <flux:text size="xs" class="text-zinc-500">
                    {{ __('Required format: SKU, Serial Number, Notes') }}
                </flux:text>
                <flux:error name="csvFile" />
            </div>

            <div class="flex justify-end space-x-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="import">{{ __('Start Import') }}</span>
                    <span wire:loading wire:target="import">{{ __('Processing...') }}</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>