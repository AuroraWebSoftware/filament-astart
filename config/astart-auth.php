<?php

return [
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
