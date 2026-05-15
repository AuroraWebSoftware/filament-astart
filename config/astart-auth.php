<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Log (LogiAudit integration)
    |--------------------------------------------------------------------------
    | Master switch for all UI action logging performed by filament-astart.
    | When true *and* the LogiAudit package is installed, the plugin emits
    | human-readable semantic event entries to `logiaudit_logs` whenever
    | an authorisation / organisation / user action is taken from the
    | Filament UI:
    |
    |   - RBAC: role CRUD, permission grant/revoke (aggregated per save),
    |     user-role-organisation-node assignment / revocation.
    |   - ABAC: rule CRUD.
    |   - Auth: active role switch.
    |   - User: lifecycle (CRUD), status (activate/deactivate),
    |     security (lock, force password change, terminate sessions,
    |     send password reset).
    |   - Organisation: scope, node and tree CRUD.
    |
    | The plugin only writes high-level "who did what when" logs —
    | column-level Eloquent history is left to LogiAudit consumers.
    |
    | When false, no logs are written by this plugin (other LogiAudit
    | consumers are unaffected).
    */
    'log' => [
        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | ABAC (Attribute-Based Access Control)
    |--------------------------------------------------------------------------
    | Registers ABAC-enabled Eloquent models for the role rule editor UI.
    |
    | A model is ABAC-enabled when it implements
    | AuroraWebSoftware\AAuth\Interfaces\AAuthABACModelInterface and uses the
    | AuroraWebSoftware\AAuth\Traits\AAuthABACModel trait. The 'model_type'
    | key below MUST match the value returned by the model's static
    | getModelType() method.
    |
    | Each registered model declares an `attributes` whitelist: only listed
    | columns can be referenced in ABAC rules through the UI. This protects
    | sensitive columns (password, remember_token, etc.) from being filtered.
    |
    | enabled:    Master switch — when false, the ABAC tab is hidden in
    |             RoleResource regardless of registered models.
    | models:     Map of model_type => definition.
    |   class:        Fully-qualified Eloquent model class.
    |   label:        Human-readable label shown in the UI.
    |   attributes:   Whitelist map of attribute => metadata.
    |     type:    'string' | 'numeric' | 'boolean' | 'date' (used by v2
    |              tip-aware validation; today only enforced via UI input).
    |     options: Optional array of allowed values; renders a Select
    |              instead of a free-form TextInput in the rule editor.
    */
    'abac' => [
        'enabled' => true,
        'models' => [
            // 'order' => [
            //     'class' => \App\Models\Order::class,
            //     'label' => 'Sipariş',
            //     'attributes' => [
            //         'status' => [
            //             'type' => 'string',
            //             'options' => ['active', 'passive', 'draft'],
            //         ],
            //         'amount' => [
            //             'type' => 'numeric',
            //         ],
            //         'created_at' => [
            //             'type' => 'date',
            //         ],
            //     ],
            // ],
        ],
    ],

    'permissions' => [
        'resource' => [
            'User' => [
                'view',
                'edit',
                'delete',
                'create',
                'update',
                'view_any',
            ],
            'OrganizationScope' => [
                'view',
                'edit',
                'delete',
                'create',
                'update',
                'view_any',
            ],
            'OrganizationNode' => [
                'view',
                'edit',
                'delete',
                'create',
                'update',
                'view_any',
            ],
            'Role' => [
                'view',
                'edit',
                'delete',
                'create',
                'update',
                'view_any',
            ],
            'OrganizationTree' => [
                'view',
                'edit',
                'delete',
                'create',
                'update',
                'view_any',
            ],
            'LogiAuditLog' => [
                'view',
                'view_any',
            ],
            'LogiAuditHistory' => [
                'view',
                'view_any',
            ],
        ],
        'pages' => [
            //            'Settings' => [
            //                'view',
            //                'edit',
            //                'delete',
            //                'create',
            //                'update',
            //            ],
        ],
        'widget' => [
            //            'widget_test' => [
            //                'view',
            //                'edit',
            //                'delete',
            //                'create',
            //                'update',
            //            ],

        ],
        'custom_permission' => [
            //            'demo_custom_permission' => ['Demo Custom Permission'],
        ],
    ],
];
