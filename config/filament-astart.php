<?php

// Config for AuroraWebSoftware/FilamentAstart

return [
    /*
    |--------------------------------------------------------------------------
    | Features (Third-party Integrations)
    |--------------------------------------------------------------------------
    | These features are only active when FilamentAstartPlugin is registered
    | to a panel. They won't affect panels without the plugin.
    */
    'features' => [
        'language_switch' => [
            'enabled' => true,
            'locales' => ['en', 'tr'],
            'flags' => false,      // Show country flags
            'circular' => false,   // Circular flag style
        ],

        'panel_switch' => [
            'enabled' => false,
            'modal_heading' => 'Available Panels',
            'visible' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Menu Style
    |--------------------------------------------------------------------------
    | 'classic' = Default Filament user menu
    | 'modern'  = Enhanced menu with avatar, role badge, org node, divider
    */
    'user_menu_style' => env('ASTART_USER_MENU_STYLE', 'classic'),

    /*
    |--------------------------------------------------------------------------
    | Resources
    |--------------------------------------------------------------------------
    | active: Determines if resource is registered (true/false)
    | navigation_group_key: Translation key for navigation group
    |   - null = uses default 'navigation_group' from lang file
    |   - string = uses 'navigation_groups.{key}' from lang file
    */
    'resources' => [
        'user' => [
            'active' => true,
            'navigation_group_key' => null,
        ],
        'role' => [
            'active' => true,
            'navigation_group_key' => null,
        ],
        'organization_scope' => [
            'active' => true,
            'navigation_group_key' => null,
        ],
        'organization_node' => [
            'active' => true,
            'navigation_group_key' => null,
        ],
        'organization_tree' => [
            'active' => true,
            'navigation_group_key' => null,
        ],
        'logiaudit_log' => [
            'active' => true,
            'navigation_group_key' => null,
        ],
        'logiaudit_history' => [
            'active' => true,
            'navigation_group_key' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Avatar
    |--------------------------------------------------------------------------
    | enabled: Show user avatars in select components, user menu, and user form
    |   - Uses Filament's built-in avatar system (HasAvatar, avatar_url, or ui-avatars.com fallback)
    |   - Storage disk is determined by Filament/Laravel default (FILESYSTEM_DISK or filament config)
    |   - false = plain text labels, initials in menu, no upload field (default)
    |   - true  = avatar images everywhere + upload field in user form
    */
    'avatar' => [
        'enabled' => env('ASTART_AVATAR_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | User Creation Options
    |--------------------------------------------------------------------------
    | Controls user creation form features
    |
    | allow_random_password: Allow generating random passwords
    | allow_send_credentials_email: Allow sending login credentials via email
    | random_password_length: Length of generated random password
    | force_random_password: Always use random password (hide manual password input)
    | force_send_credentials_email: Always send credentials email (no checkbox)
    | force_password_reset: Force user to reset password on first login (requires FiloLogin)
    */
    'user_creation' => [
        'allow_random_password' => true,
        'allow_send_credentials_email' => true,
        'random_password_length' => 16,
        'force_random_password' => false,
        'force_send_credentials_email' => false,
        'force_password_reset' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | User Custom Actions (Dynamic Links)
    |--------------------------------------------------------------------------
    | Extra, host-defined links attached to the User resource. Each entry points
    | to a route declared in YOUR application (assignment, matching, etc.) and
    | decides where it shows up: the table row actions, the detail (view) page,
    | or both. If the route is not registered, the link is not rendered.
    |
    | Each definition supports:
    |   key        Unique key (used for the action name & translation fallback)
    |   placement  Where to render: ['table'], ['view'] or ['table', 'view']
    |   enabled    Toggle the link on/off (default: true)
    |   route      Host application route name (required, must exist)
    |   params     route_param => record attribute, e.g. ['user' => 'id']
    |   query      Extra static query parameters (optional)
    |   label      Explicit label; null falls back to translation, then key
    |   icon       Heroicon name (default: heroicon-o-link)
    |   color      Filament color (default: gray)
    |   new_tab    Open in a new browser tab (default: false)
    |   sort       Order among the custom actions (default: 0)
    |   permission Optional AAuth permission slug or callable; null = everyone
    |
    | Example:
    | 'user_actions' => [
    |     [
    |         'key'        => 'assignment',
    |         'placement'  => ['table', 'view'],
    |         'route'      => 'admin.user-assignments',
    |         'params'     => ['user' => 'id'],
    |         'icon'       => 'heroicon-o-link',
    |         'color'      => 'info',
    |         'permission' => null,
    |     ],
    | ],
    */
    'user_actions' => [],
];
