# Changelog

All notable changes to `filament-astart` will be documented in this file.

## Unreleased

### Fixed

- ABAC: `AbacRuleTransformer::fromFormState()` now wraps the output in
  an outer array so `AAuthABACModelScope` can iterate it correctly
  (`foreach ($rules as $rule)` does not capture keys, so the doc-style
  top-level `['&&' => [...]]` format produced "Undefined array key
  'attribute'"). `toFormState()` accepts both wrapped and legacy
  shapes for backward compat.
- ABAC: removed `selectablePlaceholder(false)` from attribute /
  operator / value Select fields and added explicit placeholders
  (`Özellik seçiniz`, `Operatör seçiniz`, `Değer seçiniz`) so the
  fields don't appear pre-selected when state is null.

### Added

- **ABAC rule management UI**: New "ABAC Rules" tab on the role edit page
  with a repeater-based rule builder. Supports a top-level `AND/OR`
  operator, condition blocks, and one level of nested condition groups.
  Rules persist to aauth's `role_model_abac_rules` table and apply
  automatically through `AStartAbacModelScope` (wraps aauth's scope
  with a super-admin bypass).
  - Config-based registry under `astart-auth.abac.models`
    (`enabled` flag + per-model attribute whitelist with optional value
    options).
  - `AStartAbacModel` trait: drop-in replacement for `AAuthABACModel`
    that adds a super-admin bypass via `AStartAbacModelScope`. When
    `aauth-advanced.super_admin.enabled` is true and the current user
    matches the configured column, ABAC filtering is skipped and the
    query returns unfiltered results.
  - `AAuthUtil` ABAC helpers: `isAbacEnabled`, `getAbacModels`,
    `getAbacModelLabel`, `getAbacAttributes`, `getAbacAttributeOptions`.
  - `AbacRuleTransformer` for two-way conversion between form state and
    aauth `rules_json` shape.
  - `AbacRuleBuilder` form schema factory.
  - `HandlesAbacRules` trait shared by `CreateRole` / `EditRole`
    pages — load, validate (whitelist + ABACUtil), and save inside a
    transaction. Includes auto-heal: stale legacy-format rows are
    rewritten in the canonical wrapped shape on next load.
  - `filament-astart:abac:normalize` artisan command for one-shot
    repair of legacy-format rules (idempotent, `--dry-run` supported).
  - **LogiAudit integration** (opt-in): when the `LogiAudit` package is
    installed, `RoleModelAbacRule` create / update / delete events are
    forwarded to the audit log via `RoleModelAbacRuleObserver`. Each
    entry is tagged `abac` and carries `role_id`, `abac_model_type`,
    causer info (`user_id`, `user_name`, `user_class`), the client
    `ip_address`, and before/after `rules_json` snapshots in the
    context payload. The log message includes the user's name and id
    when authenticated. Updates that don't change `rules_json` are
    skipped. Without `LogiAudit` the observer is not registered
    (silent no-op).
  - **UI action logging** (`astart-auth.log.enabled`, default `true`)
    — every authorisation / organisation / user mutation triggered
    from the Filament UI emits a human-readable semantic entry to
    `logiaudit_logs`. Column-level history is intentionally NOT
    written by this plugin; entries are coarse "who did what when"
    summaries with details in the `context` JSON.

    Tag taxonomy:
    - `rbac.role` — Role create / update / delete (Create+EditRole, RoleResource delete).
    - `rbac.permissions` — Permission grant/revoke aggregated into a
      single entry per save (1 save = 1 log even when many permissions
      change). Context lists `added` and `removed` codes.
    - `rbac.assignment` — User-Role-Node assignments via
      `RoleAssignmentLogger` (ViewUser assign action,
      UserRolesRelationManager delete).
    - `abac` — ABAC rule CRUD via `RoleModelAbacRuleObserver`.
    - `auth.role_switch` — Active role change via `RoleSwitch::switchRole()`
      (skipped on initial single-role auto-select). Retention: 7 days.
    - `user.lifecycle` — User create / update (CreateUser/EditUser).
    - `user.status` — Activate / deactivate via `EditUser::toggleActive`.
    - `user.security` — Lock / unlock, force password change, terminate
      sessions, send password reset (the last gets 30-day retention).
    - `org.scope` — Organization Scope CRUD.
    - `org.node` — Organization Node CRUD.
    - `org.tree` — Organization Tree CRUD.

    Causer info (`user_id`, `user_name`, `user_class`) and IP are
    auto-added to every entry via the new `AStartLogger` utility.
    Shared diff/snapshot logic for the org resources lives in
    `LogsResourceMutations` trait. All loggers are silent no-ops
    when log is disabled or `addLog()` is unavailable.
  - **Type-aware validation (Level 2)**: `validateAbacCondition()` now
    checks the saved value against the attribute's declared `type` in
    the registry. Supported types: `numeric` / `integer` / `int` /
    `float` / `decimal` (`is_numeric`), `boolean` / `bool` (true / false
    / 0 / 1 / yes / no), `date` / `datetime` (`strtotime`-parseable),
    `string` / `text` (any scalar). Unknown types pass silently; the
    `like` operator skips type checks (always treated as string
    pattern). New translation key: `abac.errors.value_type_mismatch`.
  - **Unique index migration** for `role_model_abac_rules` on the
    `(role_id, model_type)` column pair. Prevents duplicate rule rows
    under concurrent writes. Idempotent: skips if the table doesn't
    exist yet, skips if the index is already present, and dedupes
    existing rows (keeping the highest id per pair) before adding the
    constraint so it cannot fail on legacy data.
  - TR + EN translations for all new strings.
  - Documentation: [`docs/ABAC_USAGE.md`](docs/ABAC_USAGE.md).

## 1.0.0 - 202X-XX-XX

- initial release
