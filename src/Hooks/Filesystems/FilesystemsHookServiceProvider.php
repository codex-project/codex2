<?php
/**
 * Part of the Caffeinated PHP packages.
 *
 * MIT License and copyright information bundled with this package in the LICENSE file
 */
namespace Codex\Codex\Hooks\Filesystems;

use Codex\Codex\Traits\CodexHookProvider;
use Illuminate\Support\ServiceProvider;

/**
 * This is the StorageHookServiceProvider.
 *
 * @package        Codex\Codex
 * @author         Caffeinated Dev Team
 * @copyright      Copyright (c) 2015, Caffeinated
 * @license        https://tldrlegal.com/license/mit-license MIT License
 */
class FilesystemsHookServiceProvider extends ServiceProvider
{
    use CodexHookProvider;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // The factory hook will merge config, adds several default_project_settings
        $this->addCodexHook('factory:ready', FilesystemsFactoryHook::class);

        // The project hook will do the magic. It will read the projects configuration and
        // if filesystems are properly configured, it will replace its `files` instance with the
        // adapter specified in the project
        $this->addCodexHook('project:ready', FilesystemsProjectHook::class);
    }
}
