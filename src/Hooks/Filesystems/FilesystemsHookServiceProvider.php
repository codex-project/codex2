<?php
/**
 * Part of the Caffeinated PHP packages.
 *
 * MIT License and copyright information bundled with this package in the LICENSE file
 */
namespace Codex\Codex\Hooks\Filesystems;

use Codex\Codex\Traits\CodexHookProvider;
use Dropbox\Client;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Dropbox\DropboxAdapter;
use League\Flysystem\Filesystem;

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

    public function boot()
    {
        \Storage::extend('dropbox', function($app, $config){
            $client = new Client(env('DROPBOX_ACCESS_TOKEN'), env('DROPBOX_EMAIL'));
            $adapter = new DropboxAdapter($client);
            $adapter->setPathPrefix($config['folder']);
            $adapter->applyPathPrefix($config['folder']);
            return new Filesystem($adapter);
        });
    }
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

        // The document hook needs to fix the path if we use something else then a local disk
        $this->addCodexHook('document:ready', FilesystemsDocumentHook::class);
    }
}
