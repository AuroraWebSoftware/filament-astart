<div class="space-y-2">
    @if(empty($getState()) || $getState()->isEmpty())
        <div class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center">
            {{ __('filament-astart::filament-astart.resources.user.infolists.no_active_sessions') }}
        </div>
    @else
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($getState() as $session)
                <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4 {{ $session->is_current ? 'ring-2 ring-primary-500 dark:ring-primary-400' : '' }}">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            {{-- Device Icon --}}
                            <div class="flex-shrink-0 p-2 bg-gray-50 dark:bg-white/5 rounded-lg">
                                @if($session->device_type === 'mobile')
                                    <x-heroicon-o-device-phone-mobile class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                                @elseif($session->device_type === 'tablet')
                                    <x-heroicon-o-device-tablet class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                                @else
                                    <x-heroicon-o-computer-desktop class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                                @endif
                            </div>
                            <div>
                                <div class="font-medium text-gray-950 dark:text-white text-sm flex items-center gap-1.5">
                                    {{ $session->browser ?? 'Unknown Browser' }}
                                    @if($session->is_current)
                                        <x-filament::badge color="primary" size="xs">
                                            {{ __('filament-astart::filament-astart.resources.user.infolists.current') }}
                                        </x-filament::badge>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $session->platform ?? 'Unknown OS' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 space-y-1.5 text-xs">
                        <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                            <x-heroicon-o-globe-alt class="w-3.5 h-3.5 flex-shrink-0" />
                            <span class="font-mono">{{ $session->ip_address ?? '-' }}</span>
                        </div>

                        @php
                            $location = array_filter([$session->city, $session->country_name ?? $session->country]);
                        @endphp
                        @if(count($location) > 0)
                            <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                                <x-heroicon-o-map-pin class="w-3.5 h-3.5 flex-shrink-0" />
                                <span>{{ implode(', ', $location) }}</span>
                            </div>
                        @endif

                        <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                            <x-heroicon-o-clock class="w-3.5 h-3.5 flex-shrink-0" />
                            <span>{{ $session->logged_in_at?->format('d.m.Y H:i') }}</span>
                        </div>

                        @if($session->last_activity_at)
                            <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                                <x-heroicon-o-signal class="w-3.5 h-3.5 flex-shrink-0" />
                                <span>{{ __('filament-astart::filament-astart.resources.user.infolists.last_activity') }}: {{ $session->last_activity_at->diffForHumans() }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
