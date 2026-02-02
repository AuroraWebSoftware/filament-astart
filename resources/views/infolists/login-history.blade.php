<div>
    @if(empty($getState()) || $getState()->isEmpty())
        <div class="text-center py-6">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('filament-astart::filament-astart.resources.user.infolists.no_login_history') }}
            </p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="px-3 py-3 text-left text-sm font-semibold text-gray-600 dark:text-gray-400">
                            {{ __('filament-astart::filament-astart.resources.user.infolists.date') }}
                        </th>
                        <th class="px-3 py-3 text-left text-sm font-semibold text-gray-600 dark:text-gray-400">
                            {{ __('filament-astart::filament-astart.resources.user.infolists.method') }}
                        </th>
                        <th class="px-3 py-3 text-left text-sm font-semibold text-gray-600 dark:text-gray-400">
                            {{ __('filament-astart::filament-astart.resources.user.infolists.ip_address') }}
                        </th>
                        <th class="px-3 py-3 text-left text-sm font-semibold text-gray-600 dark:text-gray-400">
                            {{ __('filament-astart::filament-astart.resources.user.infolists.location') }}
                        </th>
                        <th class="px-3 py-3 text-left text-sm font-semibold text-gray-600 dark:text-gray-400">
                            {{ __('filament-astart::filament-astart.resources.user.infolists.status') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($getState() as $attempt)
                        <tr>
                            <td class="px-3 py-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ $attempt->created_at?->format('d.m.Y H:i') }}
                            </td>
                            <td class="px-3 py-3">
                                <x-filament::badge color="gray">
                                    {{ ucfirst($attempt->method ?? 'email') }}
                                </x-filament::badge>
                            </td>
                            <td class="px-3 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">
                                {{ $attempt->ip_address ?? '-' }}
                            </td>
                            <td class="px-3 py-3 text-sm text-gray-700 dark:text-gray-300">
                                @php
                                    $location = array_filter([$attempt->city, $attempt->country]);
                                @endphp
                                {{ implode(', ', $location) ?: '-' }}
                            </td>
                            <td class="px-3 py-3">
                                @if($attempt->is_success)
                                    <x-filament::badge color="success" icon="heroicon-o-check-circle">
                                        {{ __('filament-astart::filament-astart.resources.user.infolists.success') }}
                                    </x-filament::badge>
                                @else
                                    <x-filament::badge color="danger" icon="heroicon-o-x-circle">
                                        {{ __('filament-astart::filament-astart.resources.user.infolists.failed') }}
                                    </x-filament::badge>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
