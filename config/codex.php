<?php

use Codex\Codex\Project;

return [
    'display_name'           => 'Codex',
    'root_dir'               => base_path('resources/docs'),
    'base_route'             => 'codex',
    'default_project'        => 'themes',
    'default_project_config' => [
        'default'         => Project::SHOW_LAST_VERSION_OTHERWISE_MASTER_BRANCH,
        'custom'          => null,
        'use_phpdoc'          => false,
        'phpdoc_settings' => [
            'xml_location' => 'structure.xml',
            'route_param_name'     => 'phpdoc' // if this == route param {doc?} then show phpdoc
        ],
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
                'docs'   => 'resources/docs',
                'phpdoc' => 'resources/build/logs/structure.xml',
                'menu'   => 'resources/docs/menu.yml'
            ]
        ]
    ],
    'markdown'               => array(
        /* Tags can be added in the markdown file like:
         * <!---+ col-md-6 +-->
         * <!---+ /col-md-6 +-->
         * Add anything you'd like
         */
        'tags' => array(
            '(?<!\/)bs-material'             => '<div class="bs-material">',
            '\/bs-material'                  => '</div>',
            '(?<!\/)contextual:(.*?)'        => '<div style="padding:10px 10px 5px;" class="bg-$1">',
            '\/contextual'                   => '</div>',
            '(?<!\/)hide'                    => '<div class="hide">',
            '\/hide'                         => '</div>',
            '(?<!\/)col-md-(\d*)'            => '<div class="col-md-$1">',
            '\/col-md-(\d*)'                 => '</div>',
            '(?<!\/)row'                     => '<div class="row">',
            '\/row'                          => '</div>',
            'table(.*?)'                     => '<div class="table table-markdoc $1">',
            '\/table'                        => '</div>',
            'bar:(.*?):(\d*?):(\d*?):(\d*?)' => '<div class="progress"><div role="progressbar" aria-valuenow="$4" data-bar-value="$4" aria-valuemin="$2" aria-valuemax="$3"' .
                '  class="progress-bar progress-bar-$1"></div></div>'
        )
    )
];
