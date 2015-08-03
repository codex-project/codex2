<?php
/**
 * Part of the Caffeinated PHP packages.
 *
 * MIT License and copyright information bundled with this package in the LICENSE file
 */
namespace Codex\Codex\Hooks\Storage;

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
        $this->addCodexHook('factory:ready', FilesystemsFactoryHook::class);
        $this->addCodexHook('project:ready', FilesystemsProjectHook::class);
    }
}
