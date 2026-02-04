<div>
    @if(empty($getState()) || $getState()->isEmpty())
        <div class="fi-ta-empty-state text-center py-6">
            <p class="text-sm fi-color-gray">
                {{ __('filament-astart::filament-astart.resources.user.infolists.no_login_history') }}
            </p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="fi-ta-table w-full text-sm">
                <thead>
                    <tr class="astart-table-header">
                        <th class="fi-ta-header-cell px-3 py-3 text-left text-sm font-semibold fi-color-gray">
                            {{ __('filament-astart::filament-astart.resources.user.infolists.date') }}
                        </th>
                        <th class="fi-ta-header-cell px-3 py-3 text-left text-sm font-semibold fi-color-gray">
                            {{ __('filament-astart::filament-astart.resources.user.infolists.method') }}
                        </th>
                        <th class="fi-ta-header-cell px-3 py-3 text-left text-sm font-semibold fi-color-gray">
                            {{ __('filament-astart::filament-astart.resources.user.infolists.ip_address') }}
                        </th>
                        <th class="fi-ta-header-cell px-3 py-3 text-left text-sm font-semibold fi-color-gray">
                            {{ __('filament-astart::filament-astart.resources.user.infolists.location') }}
                        </th>
                        <th class="fi-ta-header-cell px-3 py-3 text-left text-sm font-semibold fi-color-gray">
                            {{ __('filament-astart::filament-astart.resources.user.infolists.status') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="astart-table-body">
                    @foreach($getState() as $attempt)
                        <tr class="fi-ta-row">
                            <td class="fi-ta-cell px-3 py-3 text-sm fi-color-text">
                                {{ $attempt->created_at?->format('d.m.Y H:i') }}
                            </td>
                            <td class="fi-ta-cell px-3 py-3">
                                <x-filament::badge color="gray">
                                    {{ ucfirst($attempt->method ?? 'email') }}
                                </x-filament::badge>
                            </td>
                            <td class="fi-ta-cell px-3 py-3 font-mono text-xs fi-color-text">
                                {{ $attempt->ip_address ?? '-' }}
                            </td>
                            <td class="fi-ta-cell px-3 py-3 text-sm fi-color-text">
                                @php
                                    $location = array_filter([$attempt->city, $attempt->country]);
                                @endphp
                                {{ implode(', ', $location) ?: '-' }}
                            </td>
                            <td class="fi-ta-cell px-3 py-3">
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
