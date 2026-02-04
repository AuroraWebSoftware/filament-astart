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
];
