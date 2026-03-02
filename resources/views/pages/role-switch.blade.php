<x-filament-panels::page>
    <div class="flex w-full flex-1 items-center justify-center px-4 py-6 md:px-0 md:mt-16">
        <div class="w-full max-w-2xl">
            <div class="mb-8 md:mb-10 text-center">
                <h1 class="astart-page-title-mobile">
                    {{ __('filament-astart::filament-astart.role_switch.greeting', ['name' => $this->userName]) }}
                </h1>
                <p class="astart-page-description mt-1">
                    {{ __('filament-astart::filament-astart.role_switch.select_role_description') }}
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">
                @foreach ($this->roles as $role)
                    <div
                        wire:click="switchRole({{ $role->role_id }})"
                        class="astart-role-card-grid"
                    >
                        <div class="astart-role-badge">
                            {{ mb_strtoupper(mb_substr($role->role_name, 0, 1)) }}
                        </div>
                        <div class="text-center">
                            <h3 class="astart-role-name-grid">
                                {{ $role->role_name }}
                            </h3>
                            @if($role->node_name)
                                <p class="astart-role-node-grid mt-1">
                                    {{ $role->node_name }}
                                </p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-8 md:mt-10 text-center">
                <p class="astart-footer-text">
                    {{ __('filament-astart::filament-astart.role_switch.switch_role_footer') }}
                </p>
            </div>
        </div>
    </div>
</x-filament-panels::page>
