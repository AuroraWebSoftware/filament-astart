<x-filament-panels::page>
    <div class="flex w-full flex-1 items-center justify-center md:mt-24">
        <div class="w-full max-w-2xl">
            <div class="mb-10 text-center">
                <h1 class="mb-2 text-3xl font-bold text-gray-900 dark:text-white">
                    {{ __('filament-astart::role-switch.select_role_title') }}
                </h1>
                <p class="text-gray-600 dark:text-gray-300">
                    {{ __('filament-astart::role-switch.select_role_description') }}
                </p>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                @foreach ($this->roles as $role)
                    <div
                        wire:click="switchRole({{ $role->role_id }})"
                        class="group cursor-pointer"
                    >
                        <div
                            class="relative h-[140px] overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm ring-1 ring-gray-950/5 transition-all duration-200 hover:shadow-md dark:border-gray-800 dark:bg-gray-900 dark:ring-white/10"
                            style="--hover-color: var(--primary-500, rgb(245 158 11));"
                            x-data
                            x-bind:class="{ 'ring-[--primary-500] border-[--primary-500]': $el.closest('.group').matches(':hover') }"
                        >
                            <div class="flex h-full flex-col justify-between p-5">
                                <div class="mb-3 flex items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-md text-sm font-bold text-white shadow-sm"
                                        style="background-color: var(--primary-500, rgb(245 158 11));"
                                    >
                                        {{ mb_substr($role->role_name, 0, 1) }}
                                    </div>
                                    <h3 class="truncate text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ $role->role_name }}
                                    </h3>
                                </div>

                                <div class="flex items-start gap-2 text-sm">
                                    <x-heroicon-o-map-pin class="mt-0.5 h-4 w-4 shrink-0 text-gray-400"/>
                                    <span class="line-clamp-2 text-gray-500 dark:text-gray-300">
                                        {{ $role->node_name }}
                                    </span>
                                </div>

                                <div class="absolute bottom-3 right-3 opacity-0 transition-opacity group-hover:opacity-100">
                                    <x-heroicon-o-arrow-right
                                        class="h-4 w-4"
                                        style="color: var(--primary-500, rgb(245 158 11));"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-10 text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('filament-astart::role-switch.switch_role_footer') }}
                </p>
            </div>
        </div>
    </div>

    <div class="fixed bottom-6 right-6 z-20">
        @if (Filament\Facades\Filament::auth()->check())
            <form method="POST" action="{{ filament()->getLogoutUrl() }}">
                @csrf
                <x-filament::button
                    color="gray"
                    icon="heroicon-m-arrow-left-on-rectangle"
                    type="submit"
                >
                    {{ __('filament-astart::role-switch.logout') }}
                </x-filament::button>
            </form>
        @endif
    </div>
</x-filament-panels::page>
