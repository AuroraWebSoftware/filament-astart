@if($users->isEmpty())
    <div class="fi-ta-empty-state text-center py-6">
        <div class="astart-empty-icon">
            <x-filament::icon icon="heroicon-o-users" class="h-8 w-8 text-gray-400" />
        </div>
        <p class="text-sm fi-color-gray">
            {{ __('filament-astart::filament-astart.resources.role.assigned_users.empty') }}
        </p>
    </div>
@else
    <div class="overflow-x-auto">
        <table class="fi-ta-table w-full text-sm">
            <thead>
                <tr class="astart-table-header">
                    <th class="fi-ta-header-cell px-3 py-3 text-left text-sm font-semibold fi-color-gray">ID</th>
                    <th class="fi-ta-header-cell px-3 py-3 text-left text-sm font-semibold fi-color-gray">{{ __('filament-astart::filament-astart.resources.role.assigned_users.name') }}</th>
                    <th class="fi-ta-header-cell px-3 py-3 text-left text-sm font-semibold fi-color-gray">{{ __('filament-astart::filament-astart.resources.role.assigned_users.email') }}</th>
                </tr>
            </thead>
            <tbody class="astart-table-body">
                @foreach($users as $user)
                    <tr class="fi-ta-row">
                        <td class="fi-ta-cell px-3 py-3 text-sm fi-color-text">{{ $user->id }}</td>
                        <td class="fi-ta-cell px-3 py-3 text-sm font-medium fi-color-text">{{ $user->name }}</td>
                        <td class="fi-ta-cell px-3 py-3 text-sm fi-color-gray">{{ $user->email }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
