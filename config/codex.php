<?php

use Codex\Codex\Project;

return [
    'display_name'           => 'Codex',
    'root_dir'               => base_path('resources/docs'),
    'base_route'             => 'codex',
    'default_project'        => 'themes',
    'default_document_attributes' => [ // Will be merged with the frontmatter yaml stuff
        'author' => 'me'
    ],
    'default_project_config' => [
        'default'         => Project::SHOW_LAST_VERSION_OTHERWISE_MASTER_BRANCH,
        'custom'          => null
    ]
];
