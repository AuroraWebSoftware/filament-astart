<?php

namespace AuroraWebSoftware\FilamentAstart\Utils;

use Filament\Actions\Action;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Builds the host-configured, dynamic user actions (links) defined under the
 * `filament-astart.user_actions` config key.
 *
 * Each definition points to a route declared in the host application and can
 * decide where it renders (the User table row and/or the User detail page).
 * A single resolver is used from both places so the logic is never duplicated.
 */
class UserCustomActions
{
    /**
     * Build the configured custom user actions for a given placement.
     *
     * @param  'table'|'view'  $placement
     * @return array<int, Action>
     */
    public static function for(string $placement): array
    {
        $definitions = config('filament-astart.user_actions', []);

        if (! is_array($definitions) || $definitions === []) {
            return [];
        }

        $built = [];

        foreach (array_values($definitions) as $index => $definition) {
            if (! is_array($definition)) {
                continue;
            }

            $action = self::buildAction($definition, $placement, $index);

            if ($action !== null) {
                $built[] = [
                    'sort' => (int) ($definition['sort'] ?? 0),
                    'action' => $action,
                ];
            }
        }

        usort($built, fn (array $a, array $b) => $a['sort'] <=> $b['sort']);

        return array_map(fn (array $item) => $item['action'], $built);
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  'table'|'view'  $placement
     */
    protected static function buildAction(array $definition, string $placement, int $index): ?Action
    {
        // Disabled definitions never render.
        if (! ($definition['enabled'] ?? true)) {
            return null;
        }

        // Placement gate: only render where the definition asked for.
        $placements = (array) ($definition['placement'] ?? ['table', 'view']);
        if (! in_array($placement, $placements, true)) {
            return null;
        }

        // The route lives in the host application. If it is not registered,
        // the button is not rendered at all (no broken links).
        $route = $definition['route'] ?? null;
        if (! is_string($route) || $route === '' || ! Route::has($route)) {
            return null;
        }

        $key = (string) ($definition['key'] ?? $index);
        $params = is_array($definition['params'] ?? null) ? $definition['params'] : [];
        $query = is_array($definition['query'] ?? null) ? $definition['query'] : [];
        $permission = $definition['permission'] ?? null;

        return Action::make('astart_user_'.$key)
            ->label(self::resolveLabel($definition, $key))
            ->icon($definition['icon'] ?? 'heroicon-o-link')
            ->color($definition['color'] ?? 'gray')
            ->url(fn ($record) => route($route, self::resolveParams($params, $query, $record)))
            ->openUrlInNewTab((bool) ($definition['new_tab'] ?? false))
            ->visible(fn () => self::passesPermission($permission));
    }

    /**
     * Optional permission gate: string => AAuth permission slug, callable => custom
     * check, null/empty => visible to everyone.
     */
    protected static function passesPermission(mixed $permission): bool
    {
        if ($permission === null || $permission === '') {
            return true;
        }

        if (is_callable($permission)) {
            return (bool) $permission();
        }

        if (is_string($permission)) {
            return AAuthUtil::can($permission);
        }

        return true;
    }

    /**
     * Map route parameters from record attributes and merge in static query params.
     *
     * @param  array<string, string>  $params  route_param => record attribute (e.g. ['user' => 'id'])
     * @param  array<string, mixed>  $query  extra static query parameters
     * @return array<string, mixed>
     */
    protected static function resolveParams(array $params, array $query, mixed $record): array
    {
        $resolved = [];

        foreach ($params as $routeParam => $recordAttribute) {
            $resolved[$routeParam] = data_get($record, $recordAttribute);
        }

        return array_merge($resolved, $query);
    }

    /**
     * Resolve the label: explicit config label wins, then a translation key
     * (`resources.user.user_actions.{key}`) if present, otherwise a humanized key.
     *
     * @param  array<string, mixed>  $definition
     */
    protected static function resolveLabel(array $definition, string $key): string
    {
        if (! empty($definition['label'])) {
            return (string) $definition['label'];
        }

        $transKey = 'filament-astart::filament-astart.resources.user.user_actions.'.$key;

        if (trans()->has($transKey)) {
            return __($transKey);
        }

        return Str::headline($key);
    }
}
