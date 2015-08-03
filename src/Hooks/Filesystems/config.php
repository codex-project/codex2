<?php

return [
    'default_project_config' => [
        'use_filesystems'      => false, // a project can individually enable filesystems
        'filesystems_disk'       => false, // the disk is defined in the filesystems_settings config for the project
        'filesystems_settings' => [
            'disks' => [
                'local'     => [
                    'driver' => 'local',
                    'root'   => storage_path('app'),
                ],

                'dropbox' => [
                    'driver' => 'dropbox',
                    'folder' => 'laravel-codex'
                ],
                'ftp'       => [
                    'driver'   => 'ftp',
                    'host'     => 'ftp.example.com',
                    'username' => 'your-username',
                    'password' => 'your-password',
                ],
                's3'        => [
                    'driver' => 's3',
                    'key'    => 'your-key',
                    'secret' => 'your-secret',
                    'region' => 'your-region',
                    'bucket' => 'your-bucket',
                ],
                'rackspace' => [
                    'driver'    => 'rackspace',
                    'username'  => 'your-username',
                    'key'       => 'your-key',
                    'container' => 'your-container',
                    'endpoint'  => 'https://identity.api.rackspacecloud.com/v2.0/',
                    'region'    => 'IAD',
                    'url_type'  => 'publicURL',
                ],
            ],
        ]
    ]
];
