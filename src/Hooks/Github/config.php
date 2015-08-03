<?php

return [
    'default_project_config' => [
        'use_github'      => false,
        'github_settings' => [
            'owner'          => '',
            'repository'     => '',
            'webhook_secret' => env('DOCIT_PROJECT_GITHUB_WEBHOOK_SECRET', null),
            'phpdoc'         => false,
            'sync'           => [
                'branches' => [ 'master' ],
                /**
                 * Version range expression
                 *
                 * @var string
                 * @see  \vierbergenlars\SemVer\expression
                 * @link https://github.com/vierbergenlars/php-semver
                 */
                'versions' => '1.x || >=2.5.0 || 5.0.0 - 7.2.3'
            ],
            'paths'          => [
                'docs'   => 'docs',
                'phpdoc' => 'docs/structure.xml',
                'menu'   => 'docs/menu.yml'
            ]
        ]
    ]
];
