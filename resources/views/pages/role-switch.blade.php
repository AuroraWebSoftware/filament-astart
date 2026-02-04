<x-filament-panels::page>
    <div class="flex w-full flex-1 items-center justify-center px-4 py-6 md:px-0 md:mt-16">
        <div class="w-full max-w-xl">
            <div class="mb-6 md:mb-8 text-center">
                <h1 class="astart-page-title-mobile">
                    {{ __('filament-astart::filament-astart.role_switch.select_role_title') }}
                </h1>
                <p class="astart-page-description">
                    {{ __('filament-astart::filament-astart.role_switch.select_role_description') }}
                </p>
            </div>

            <div class="space-y-2 md:space-y-3">
                @foreach ($this->roles as $role)
                    <div
                        wire:click="switchRole({{ $role->role_id }})"
                        class="group cursor-pointer"
                    >
                        <div class="astart-role-card-simple">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex-1 min-w-0">
                                    <h3 class="astart-role-name-simple">
                                        {{ $role->role_name }}
                                    </h3>
                                    @if($role->node_name)
                                        <div class="flex items-center gap-1.5 mt-1">
                                            <x-heroicon-o-building-office-2 class="h-3.5 w-3.5 shrink-0 text-gray-400"/>
                                            <span class="astart-role-node-simple">
                                                {{ $role->node_name }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                                <x-heroicon-o-chevron-right class="astart-role-arrow"/>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 md:mt-8 text-center">
                <p class="astart-footer-text">
                    {{ __('filament-astart::filament-astart.role_switch.switch_role_footer') }}
                </p>
            </div>

            {{-- Mobile logout button --}}
            <div class="mt-6 md:hidden">
                @if (Filament\Facades\Filament::auth()->check())
                    <form method="POST" action="{{ filament()->getLogoutUrl() }}">
                        @csrf
                        <x-filament::button
                            color="gray"
                            icon="heroicon-m-arrow-left-on-rectangle"
                            type="submit"
                            class="w-full"
                        >
                            {{ __('filament-astart::filament-astart.role_switch.logout') }}
                        </x-filament::button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    {{-- Desktop logout button --}}
    <div class="hidden md:block fixed bottom-6 right-6 z-20">
        @if (Filament\Facades\Filament::auth()->check())
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
        @endif
    </div>
</x-filament-panels::page>
