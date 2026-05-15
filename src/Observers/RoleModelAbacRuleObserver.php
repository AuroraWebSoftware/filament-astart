<?php

namespace AuroraWebSoftware\FilamentAstart\Observers;

use AuroraWebSoftware\AAuth\Models\RoleModelAbacRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Forwards ABAC rule CRUD events to LogiAudit. Only registered when
 * the LogiAudit package and the `addLog()` global helper are present;
 * a missing package degrades silently to a no-op.
 *
 * Each log entry carries:
 *   - tag:        'abac'
 *   - level:      'info'
 *   - model_id:   RoleModelAbacRule primary key
 *   - model_type: RoleModelAbacRule::class
 *   - ip_address: request remote address (when available)
 *   - context:
 *       - action:          created | updated | deleted
 *       - role_id:         affected aauth role
 *       - abac_model_type: filtered model_type (e.g. 'document')
 *       - user_id:         authenticated user id (null on CLI / no-auth)
 *       - user_name:       user's name attribute when present
 *       - user_class:      user model FQCN
 *       - before?:         previous rules_json (updated / deleted)
 *       - after?:          new rules_json (created / updated)
 *
 * NOTE: `logiaudit_logs` has no `user_id` / `causer_type` column;
 * causer info is therefore stored inside `context` so we don't
 * touch LogiAudit's schema. If LogiAudit later grows first-class
 * causer columns the observer should be migrated to use them.
 */
class RoleModelAbacRuleObserver
{
    public function created(RoleModelAbacRule $rule): void
    {
        $this->log('created', $rule, [
            'after' => $rule->rules_json,
        ]);
    }

    public function updated(RoleModelAbacRule $rule): void
    {
        if (! $rule->wasChanged('rules_json')) {
            return;
        }

        $this->log('updated', $rule, [
            'before' => $rule->getOriginal('rules_json'),
            'after' => $rule->rules_json,
        ]);
    }

    public function deleted(RoleModelAbacRule $rule): void
    {
        $this->log('deleted', $rule, [
            'before' => $rule->getOriginal('rules_json'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function log(string $action, RoleModelAbacRule $rule, array $extra): void
    {
        if (! function_exists('addLog')) {
            return;
        }

        $user = Auth::user();

        $context = array_merge([
            'action' => $action,
            'role_id' => $rule->role_id,
            'abac_model_type' => $rule->model_type,
            'user_id' => $user?->getKey(),
            'user_name' => is_object($user) && isset($user->name) ? $user->name : null,
            'user_class' => $user !== null ? $user::class : null,
        ], $extra);

        $message = $this->buildMessage($action, $rule, $user);

        addLog('info', $message, [
            'model_id' => $rule->id,
            'model_type' => RoleModelAbacRule::class,
            'tag' => 'abac',
            'ip_address' => $this->resolveIpAddress(),
            'context' => $context,
        ]);
    }

    private function buildMessage(string $action, RoleModelAbacRule $rule, ?object $user): string
    {
        $actor = match (true) {
            $user === null => 'sistem',
            is_object($user) && isset($user->name) && is_string($user->name) && $user->name !== '' => sprintf('%s (#%s)', $user->name, $user->getKey()),
            default => sprintf('#%s', $user->getKey()),
        };

        $verb = match ($action) {
            'created' => 'oluşturdu',
            'updated' => 'güncelledi',
            'deleted' => 'sildi',
            default => $action,
        };

        return sprintf(
            '%s, "%s" model tipinde rol #%d için ABAC kuralı %s',
            $actor,
            $rule->model_type,
            $rule->role_id,
            $verb
        );
    }

    private function resolveIpAddress(): ?string
    {
        try {
            return Request::ip();
        } catch (\Throwable) {
            return null;
        }
    }
}
