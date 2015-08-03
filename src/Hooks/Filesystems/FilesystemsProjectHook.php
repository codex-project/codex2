<?php
/**
 * Part of the Caffeinated PHP packages.
 *
 * MIT License and copyright information bundled with this package in the LICENSE file
 */
namespace Codex\Codex\Hooks\Filesystems;

use Codex\Codex\Hook;
use Codex\Codex\Project;
use Illuminate\Filesystem\FilesystemManager;

/**
 * This is the StorageFactoryHook.
 *
 * @package        Codex\Codex
 * @author         Caffeinated Dev Team
 * @copyright      Copyright (c) 2015, Caffeinated
 * @license        https://tldrlegal.com/license/mit-license MIT License
 */
class FilesystemsProjectHook implements Hook
{
    protected $fsm;

    /** Instantiates the class
     *
     * @param \Illuminate\Filesystem\FilesystemManager $fsm
     */
    public function __construct(FilesystemManager $fsm)
    {
        $this->fsm = $fsm;
    }

    public function handle(Project $project)
    {
        if ( ! $project->config('use_filesystems') )
        {
            return;
        }
        $disk     = $project->config('filesystems_disk');
        $settings = $project->config('filesystems_settings');
        if ( ! isset($settins[ $disk ]) )
        {
            return;
        }

        $fsconfig = $settings[ $disk ];
        if ( $disk === 's3' )
        {
            $files = $this->fsm->createS3Driver($fsconfig);
        }
        elseif ( $disk === 'ftp' )
        {
            $files = $this->fsm->createFtpDriver($fsconfig);
        }
        elseif ( $disk === 'rackspace' )
        {
            $files = $this->fsm->createRackspaceDriver($fsconfig);
        }

        if ( isset($files) )
        {
            $project->setFiles($files);
        }
    }
}
