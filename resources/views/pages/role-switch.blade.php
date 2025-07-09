<x-filament-panels::page>
    <div class="flex flex-1 w-full items-center justify-center md:mt-24">
        <div class="w-full max-w-2xl">
            <div class="text-center mb-10">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                    {{ __('filament-astart::role-switch.select_role_title') }}
                </h1>
                <p class="text-gray-600 dark:text-gray-300">
                    {{ __('filament-astart::role-switch.select_role_description') }}
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach ($this->roles as $role)
                    <div
                        wire:click="switchRole({{ $role->role_id }})"
                        class="group cursor-pointer"
                    >
                        <div class="
                            h-[140px] overflow-hidden rounded-xl
                            bg-white dark:bg-gray-900
                            shadow-sm border border-gray-200 dark:border-gray-800
                            transition-all duration-200
                            hover:border-primary-500
                            hover:shadow-md
                            ring-1 ring-gray-950/5 dark:ring-white/10
                            group-hover:ring-primary-500 group-hover:border-primary-500
                            relative
                        ">
                            <div class="p-5 h-full flex flex-col justify-between">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-10 h-10 flex items-center justify-center bg-primary-500 text-white rounded-md font-bold text-sm shadow-sm">
                                        {{ mb_substr($role->role_name, 0, 1) }}
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white truncate">
                                        {{ $role->role_name }}
                                    </h3>
                                </div>

                                <div class="flex items-start gap-2 text-sm">
                                    <x-heroicon-o-map-pin class="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0"/>
                                    <span class="text-gray-500 dark:text-gray-300 line-clamp-2">
                                        {{ $role->node_name }}
                                    </span>
                                </div>

                                <div class="absolute bottom-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <x-heroicon-o-arrow-right class="w-4 h-4 text-primary-500"/>
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

    <div class="fixed right-6 bottom-6 z-20">
        @if (Filament\Facades\Filament::auth()->check())
            <form method="POST" action="{{ filament()->getLogoutUrl() }}">
                @csrf
                <x-filament::button
                    color="gray"
                    icon="heroicon-m-arrow-left-on-rectangle"
                    type="submit"
                    class="transition-colors hover:bg-danger-600 hover:text-white focus:outline-none"
                >
                    {{ __('filament-astart::role-switch.logout') }}
                </x-filament::button>
            </form>
        @endif
    </div>

    <style>
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</x-filament-panels::page>
