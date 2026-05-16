<?php

namespace AuroraWebSoftware\FilamentAstart\Utils;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Throwable;

/**
 * Writes semantic LogiAuditLog entries for `user_role_organization_node`
 * pivot changes (assigning / revoking a role on a node).
 *
 * The pivot table has no Eloquent model in aauth, so observers can't
 * fire automatically — call these helpers from any UI / job site that
 * mutates the pivot. Silent no-op when audit is disabled or the
 * `addLog()` helper is missing.
 *
 * NOTE: `logiaudit_logs` has no native `user_id` column; causer info is
 * stored inside the JSON `context` field (same approach as
 * RoleModelAbacRuleObserver).
 */
class RoleAssignmentLogger
{
    public const TAG = 'rbac.assignment';

    public static function logAssigned(int $userId, int $roleId, ?int $nodeId): void
    {
        self::log('assigned', $userId, $roleId, $nodeId);
    }

    public static function logRevoked(int $userId, int $roleId, ?int $nodeId): void
    {
        self::log('revoked', $userId, $roleId, $nodeId);
    }

    private static function log(string $action, int $targetUserId, int $roleId, ?int $nodeId): void
    {
        if (! config('astart-auth.log.enabled', false)) {
            return;
        }

        if (! function_exists('addLog')) {
            return;
        }

        try {
            $causer = Auth::user();
            $targetName = self::lookupUserName($targetUserId);
            $roleName = self::lookupRoleName($roleId);
            $nodeName = $nodeId !== null ? self::lookupNodeName($nodeId) : null;

            $message = self::buildMessage($action, $causer, $targetName, $targetUserId, $roleName, $roleId, $nodeName, $nodeId);

            addLog('info', $message, [
                'tag' => self::TAG,
                'ip_address' => self::resolveIpAddress(),
                'context' => [
                    'action' => $action,
                    'target_user_id' => $targetUserId,
                    'target_user_name' => $targetName,
                    'role_id' => $roleId,
                    'role_name' => $roleName,
                    'organization_node_id' => $nodeId,
                    'organization_node_name' => $nodeName,
                    'user_id' => $causer?->getKey(),
                    'user_name' => is_object($causer) && isset($causer->name) ? $causer->name : null,
                    'user_class' => $causer !== null ? $causer::class : null,
                ],
            ]);
        } catch (Throwable) {
            // Swallow — audit failure must not break the underlying write.
        }
    }

    private static function buildMessage(
        string $action,
        ?object $causer,
        ?string $targetName,
        int $targetUserId,
        ?string $roleName,
        int $roleId,
        ?string $nodeName,
        ?int $nodeId,
    ): string {
        $actor = match (true) {
            $causer === null => 'sistem',
            is_object($causer) && isset($causer->name) && is_string($causer->name) && $causer->name !== '' => sprintf('%s (#%s)', $causer->name, $causer->getKey()),
            default => sprintf('#%s', $causer->getKey()),
        };

        $target = $targetName !== null
            ? sprintf('%s (#%d)', $targetName, $targetUserId)
            : sprintf('#%d', $targetUserId);

        $role = $roleName !== null
            ? sprintf('%s (#%d)', $roleName, $roleId)
            : sprintf('#%d', $roleId);

        $where = match (true) {
            $nodeName !== null => sprintf(' %s (#%d) biriminde', $nodeName, $nodeId),
            $nodeId !== null => sprintf(' birim #%d altında', $nodeId),
            default => '',
        };

        // assigned → "X rolünü ... kullanıcısına ... atadı"
        // revoked  → "X rolünü ... kullanıcısından ... kaldırdı"
        if ($action === 'assigned') {
            return sprintf('%s, %s rolünü %s kullanıcısına%s atadı', $actor, $role, $target, $where);
        }

        return sprintf('%s, %s rolünü %s kullanıcısından%s kaldırdı', $actor, $role, $target, $where);
    }

    private static function lookupUserName(int $userId): ?string
    {
        try {
            $value = DB::table('users')->where('id', $userId)->value('name');

            return is_string($value) ? $value : null;
        } catch (Throwable) {
            return null;
        }
    }

    private static function lookupRoleName(int $roleId): ?string
    {
        try {
            $value = DB::table('roles')->where('id', $roleId)->value('name');

            return is_string($value) ? $value : null;
        } catch (Throwable) {
            return null;
        }
    }

    private static function lookupNodeName(int $nodeId): ?string
    {
        try {
            $value = DB::table('organization_nodes')->where('id', $nodeId)->value('name');

            return is_string($value) ? $value : null;
        } catch (Throwable) {
            return null;
        }
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
