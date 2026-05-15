<?php

namespace AuroraWebSoftware\FilamentAstart\Utils;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Throwable;

/**
 * Centralised semantic logger for filament-astart UI actions.
 *
 * All plugin call sites that mutate authorisation / organisation /
 * user state route their human-readable "who did what" message through
 * this helper. Output lands in `logiaudit_logs` (via the `addLog()`
 * helper); column-level history is intentionally NOT written by this
 * plugin.
 *
 * Silent no-op when:
 *   - `astart-auth.log.enabled` is false, or
 *   - the LogiAudit `addLog()` global helper is not registered.
 *
 * Failures are caught so audit failures never break the underlying
 * mutation.
 */
class AStartLogger
{
    /**
     * Emit a log entry.
     *
     * @param  string  $tag  Tag namespace (e.g. `rbac.role`, `user.security`).
     * @param  string  $message  Human-readable summary. Use {actor} placeholder for the causer label.
     * @param  array<string, mixed>  $context  Extra payload appended to the auto-built causer context.
     * @param  int|null  $retentionDays  Days until LogiAudit's PruneLogsCommand removes the row (null = never).
     * @param  Model|null  $target  Target model — sets model_id / model_type when present.
     */
    public static function log(
        string $tag,
        string $message,
        array $context = [],
        ?int $retentionDays = null,
        ?Model $target = null,
    ): void {
        if (! config('astart-auth.log.enabled', false)) {
            return;
        }

        if (! function_exists('addLog')) {
            return;
        }

        try {
            $causer = Auth::user();
            $actor = self::describeUser($causer);

            $finalMessage = str_contains($message, '{actor}')
                ? str_replace('{actor}', $actor, $message)
                : sprintf('%s %s', $actor, $message);

            $options = [
                'tag' => $tag,
                'ip_address' => self::resolveIpAddress(),
                'context' => array_merge(self::buildCauserContext($causer), $context),
            ];

            if ($target !== null) {
                $options['model_id'] = (int) $target->getKey();
                $options['model_type'] = $target::class;
            }

            if ($retentionDays !== null) {
                $options['delete_after_days'] = $retentionDays;
            }

            addLog('info', $finalMessage, $options);
        } catch (Throwable) {
            // Swallow — log failure must not break the underlying mutation.
        }
    }

    /**
     * Build a short "Name (#id)" label for the causer, or "system" if
     * no user is authenticated.
     */
    public static function describeUser(?object $user): string
    {
        if ($user === null) {
            return 'sistem';
        }

        if (isset($user->name) && is_string($user->name) && $user->name !== '') {
            return sprintf('%s (#%s)', $user->name, self::resolveUserKey($user));
        }

        return sprintf('#%s', self::resolveUserKey($user));
    }

    /**
     * Build a short "Name (#id)" label for an arbitrary record. Falls
     * back to "#id" when the record has no `name` attribute.
     */
    public static function describeRecord(?object $record, ?string $nameAttribute = 'name'): string
    {
        if ($record === null) {
            return '—';
        }

        $key = method_exists($record, 'getKey') ? $record->getKey() : null;

        if ($nameAttribute !== null && isset($record->{$nameAttribute}) && is_scalar($record->{$nameAttribute})) {
            return sprintf("'%s' (#%s)", $record->{$nameAttribute}, $key);
        }

        return sprintf('#%s', $key);
    }

    /**
     * @return array<string, mixed>
     */
    private static function buildCauserContext(?object $user): array
    {
        return [
            'user_id' => $user !== null ? self::resolveUserKey($user) : null,
            'user_name' => isset($user->name) && is_string($user->name) ? $user->name : null,
            'user_class' => $user !== null ? $user::class : null,
        ];
    }

    private static function resolveUserKey(object $user): mixed
    {
        if (method_exists($user, 'getAuthIdentifier')) {
            return $user->getAuthIdentifier();
        }

        if (method_exists($user, 'getKey')) {
            return $user->getKey();
        }

        return null;
    }

    private static function resolveIpAddress(): ?string
    {
        try {
            return Request::ip();
        } catch (Throwable) {
            return null;
        }
    }
}
