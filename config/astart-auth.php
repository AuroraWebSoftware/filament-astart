<?php

// config for AuroraWebSoftware/AAuth
return [
    'permissions' => [
        'resource' => [
            'User' => [
                'view',
                'edit',
                'delete',
                'create',
                'update',
            ],
        ],
        'pages' => [
            'Settings' => [
                'view',
                'edit',
                'delete',
                'create',
                'update',
            ],
        ],
        'widget' => [
            'widget_test' => [
                'view',
                'edit',
                'delete',
                'create',
                'update',
            ],

        ],
        'custom_permission' => [
            'demo_custom_permission' => ['Demo Custom Permission'],
        ],
    ],
];
