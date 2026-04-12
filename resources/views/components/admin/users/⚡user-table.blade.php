<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

new class extends Component 
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[On('rolesUpdated')]
    public function refreshUsers(): void
    {
        unset($this->users);
    }

    #[Computed]
    public function users()
    {
        return User::with('roles')
            ->where('name', 'like', '%' . $this->search . '%')
            ->orWhere('email', 'like', '%' . $this->search . '%')
            ->latest()
            ->paginate(9);
    }
};
?>

<section class="p-4 space-y-4 bg-dark">
    <div class="flex justify-between items-start">
        <div>
            <flux:heading size="xl" level="1" class="text-primary">{{ __('User Management') }}</flux:heading>
            <flux:subheading class="!text-zinc-400 font-bold uppercase tracking-widest text-[10px] mt-1">
                {{ __('Assign roles and manage system access levels.') }}
            </flux:subheading>
        </div>
        
        <div class="w-1/3">
            <flux:input 
                wire:model.live.debounce.300ms="search" 
                icon="magnifying-glass" 
                placeholder="Search by name or email..." 
                class="!text-primary !bg-dark placeholder:text-primary/50"
            />
        </div>
    </div>

    <flux:table :paginate="$this->users" class="text-primary">
        <flux:table.columns>
            <flux:table.column class="!text-primary">{{ __('User') }}</flux:table.column>
            <flux:table.column class="!text-primary">{{ __('Email') }}</flux:table.column>
            <flux:table.column class="!text-primary">{{ __('Roles') }}</flux:table.column>
            <flux:table.column class="!text-primary">{{ __('Joined') }}</flux:table.column>
            <flux:table.column class="text-right !text-primary">{{ __('Actions') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($this->users as $user)
                <flux:table.row :key="$user->id" class="border-b border-primary/20 hover:bg-primary/5">
                    <flux:table.cell class="font-bold text-primary">
                        {{ $user->name }}
                    </flux:table.cell>
                    
                    <flux:table.cell class="text-primary/80">
                        {{ $user->email }}
                    </flux:table.cell>
                    
                    <flux:table.cell>
                        <div class="flex gap-1 flex-wrap">
                            @forelse($user->roles as $role)
                                @php
                                    $variant = match($role->name) {
                                        'admin' => 'danger',
                                        'employee' => 'info',
                                        default => 'success',
                                    };
                                @endphp
                                <flux:badge size="sm" :variant="$variant" class="uppercase tracking-widest text-[10px] !rounded-none">
                                    {{ $role->name }}
                                </flux:badge>
                            @empty
                                <span class="text-[10px] font-bold uppercase tracking-widest text-zinc-500 italic">{{ __('No roles') }}</span>
                            @endforelse
                        </div>
                    </flux:table.cell>
                    
                    <flux:table.cell class="text-primary/80 text-sm">
                        {{ $user->created_at->format('M d, Y') }}
                    </flux:table.cell>
                    
                    <flux:table.cell class="text-right">
                        <livewire:admin.users.edit-roles-btn :user="$user" :wire:key="'edit-roles-'.$user->id" />
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</section>