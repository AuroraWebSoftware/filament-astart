<div class="space-y-2">
    {{-- Lock/Unlock Account --}}
    <x-filament::button
        wire:click="toggleLockAction"
        wire:confirm="{{ $isLocked
            ? __('filament-astart::filament-astart.resources.user.actions.unlock_account_confirm', ['name' => $record->name])
            : __('filament-astart::filament-astart.resources.user.actions.lock_account_confirm', ['name' => $record->name]) }}"
        color="{{ $isLocked ? 'success' : 'danger' }}"
        class="w-full justify-start"
        :icon="$isLocked ? 'heroicon-o-lock-open' : 'heroicon-o-lock-closed'"
    >
        @if($isLocked)
            {{ __('filament-astart::filament-astart.resources.user.actions.unlock_account') }}
        @else
            {{ __('filament-astart::filament-astart.resources.user.actions.lock_account') }}
        @endif
    </x-filament::button>

    {{-- Force Password Change --}}
    <x-filament::button
        wire:click="forcePasswordChangeAction"
        wire:confirm="{{ __('filament-astart::filament-astart.resources.user.actions.force_password_change_confirm', ['name' => $record->name]) }}"
        color="warning"
        class="w-full justify-start"
        icon="heroicon-o-key"
    >
        {{ __('filament-astart::filament-astart.resources.user.actions.force_password_change') }}
    </x-filament::button>

    {{-- Send Password Reset Email --}}
    <x-filament::button
        wire:click="sendPasswordResetAction"
        wire:confirm="{{ __('filament-astart::filament-astart.resources.user.actions.send_password_reset_confirm', ['email' => $record->email]) }}"
        color="info"
        class="w-full justify-start"
        icon="heroicon-o-envelope"
    >
        {{ __('filament-astart::filament-astart.resources.user.actions.send_password_reset') }}
    </x-filament::button>

    {{-- Terminate Sessions --}}
    <x-filament::button
        wire:click="terminateSessionsAction"
        wire:confirm="{{ __('filament-astart::filament-astart.resources.user.actions.terminate_sessions_confirm', ['name' => $record->name]) }}"
        color="gray"
        class="w-full justify-start"
        icon="heroicon-o-computer-desktop"
    >
        {{ __('filament-astart::filament-astart.resources.user.actions.terminate_sessions') }}
    </x-filament::button>
</div>
