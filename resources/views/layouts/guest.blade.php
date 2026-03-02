<x-filament-panels::layout.base :livewire="$livewire">
    {{ $slot }}

    @if (Filament\Facades\Filament::auth()->check())
        <div class="fixed bottom-6 right-6 z-50">
            <form method="POST" action="{{ filament()->getLogoutUrl() }}">
                @csrf
                <x-filament::button
                    color="gray"
                    icon="heroicon-m-arrow-left-on-rectangle"
                    type="submit"
                >
                    {{ __('filament-astart::filament-astart.role_switch.logout') }}
                </x-filament::button>
            </form>
        </div>
    @endif
</x-filament-panels::layout.base>
