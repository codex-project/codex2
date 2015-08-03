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
        'use_phpdoc'      => false,
        'phpdoc_settings' => [
            'xml_location'     => 'structure.xml',
            'route_param_name' => 'phpdoc' // if this == route param {doc?} then show phpdoc
        ],
    ]
];
