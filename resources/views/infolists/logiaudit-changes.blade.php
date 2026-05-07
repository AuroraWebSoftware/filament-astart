<div>
    @php
        $data = $getState();
        $columns = $data['column'] ?? [];
        $oldValues = $data['old_value'] ?? [];
        $newValues = $data['new_value'] ?? [];
    @endphp

    @if(empty($columns))
        <div class="fi-ta-empty-state text-center py-6">
            <p class="text-sm fi-color-gray">
                {{ __('filament-astart::filament-astart.resources.logiaudit_history.no_changes') }}
            </p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="fi-ta-table w-full text-sm">
                <thead>
                    <tr class="astart-table-header">
                        <th class="fi-ta-header-cell px-3 py-3 text-left text-sm font-semibold fi-color-gray">
                            {{ __('filament-astart::filament-astart.resources.logiaudit_history.changes.column') }}
                        </th>
                        <th class="fi-ta-header-cell px-3 py-3 text-left text-sm font-semibold fi-color-gray">
                            {{ __('filament-astart::filament-astart.resources.logiaudit_history.changes.old_value') }}
                        </th>
                        <th class="fi-ta-header-cell px-3 py-3 text-left text-sm font-semibold fi-color-gray">
                            {{ __('filament-astart::filament-astart.resources.logiaudit_history.changes.new_value') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="astart-table-body">
                    @foreach($columns as $index => $column)
                        <tr class="fi-ta-row">
                            <td class="fi-ta-cell px-3 py-3 font-medium fi-color-text">
                                {{ $column }}
                            </td>
                            <td class="fi-ta-cell px-3 py-3 font-mono text-xs">
                                <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-danger-50 text-danger-700 ring-danger-600/20 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30">
                                    {{ is_array($oldValues[$index] ?? null) ? json_encode($oldValues[$index]) : ($oldValues[$index] ?? '-') }}
                                </span>
                            </td>
                            <td class="fi-ta-cell px-3 py-3 font-mono text-xs">
                                <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30">
                                    {{ is_array($newValues[$index] ?? null) ? json_encode($newValues[$index]) : ($newValues[$index] ?? '-') }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
