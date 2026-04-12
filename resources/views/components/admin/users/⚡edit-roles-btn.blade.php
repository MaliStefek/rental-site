<?php

use Livewire\Component;
use App\Models\User;
use App\Models\Role;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Gate;

new class extends Component 
{
    public User $user;
    public array $selectedRoles = [];

    public function mount(User $user): void
    {
        $this->user = $user;
        $this->selectedRoles = $user->roles->pluck('id')->toArray();
    }

    #[Computed]
    public function allRoles()
    {
        return Role::all();
    }

    public function saveRoles(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403, 'Only admins can manage roles.');

        $this->user->roles()->sync($this->selectedRoles);
        
        flux::toast()->success('User roles updated successfully.');
        
        $this->dispatch('rolesUpdated');
        
        $this->modal("manage-roles-{$this->user->id}")->close();
    }
};
?>

<div>
    <flux:modal.trigger name="manage-roles-{{ $user->id }}">
        <flux:button size="sm" variant="subtle" icon="shield-check" aria-label="{{ __('Edit Roles') }}" class="btn-subtle !rounded-none">
            {{ __('Edit Roles') }}
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="manage-roles-{{ $user->id }}" class="max-w-md !bg-dark !rounded-none border border-zinc-700 shadow-2xl">
        <form wire:submit.prevent="saveRoles" class="space-y-6 p-6">
            
            <div class="border-b border-zinc-800 pb-4">
                <flux:heading size="lg" class="font-black uppercase tracking-tight !text-primary">
                    {{ __('Manage Roles') }}
                </flux:heading>
                <flux:subheading class="!text-zinc-400 font-bold uppercase tracking-widest text-[10px] mt-1">
                    {{ __('Editing roles for: ') }}
                    <span class="text-white underline decoration-primary decoration-2 underline-offset-4">{{ $user->name }}</span>
                </flux:subheading>
            </div>

            <div class="p-4 bg-zinc-900/30 border border-zinc-800 !rounded-none space-y-4">
                @foreach($this->allRoles as $role)
                    <div class="[&>label]:!text-zinc-300 [&_input]:!bg-zinc-800 [&_input]:!border-zinc-600 checked:[&_input]:!bg-primary checked:[&_input]:!border-primary [&_input]:!rounded-none">
                        <flux:checkbox wire:model="selectedRoles" :value="$role->id" :label="ucfirst($role->name)" />
                    </div>
                @endforeach
            </div>

            <div class="flex justify-end gap-3 pt-6 border-t border-zinc-800 mt-6">
                <flux:modal.close>
                    <flux:button class="btn-action-subtle !rounded-none">
                        {{ __('Cancel') }}
                    </flux:button>
                </flux:modal.close>
                
                <flux:button type="submit" class="btn-action-save !rounded-none">
                    {{ __('Save Roles') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>