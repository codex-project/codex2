<?php
/**
 * Part of the Caffeinated PHP packages.
 *
 * MIT License and copyright information bundled with this package in the LICENSE file
 */
namespace Codex\Codex\Hooks\Github;

use Codex\Codex\Hooks\Github\Console\CodexSyncGithubCommand;
use Codex\Codex\Traits\CodexHookProvider;
use Illuminate\Support\ServiceProvider;

/**
 * This is the GithubServiceProvider.
 *
 * @package        Codex\Codex
 * @author         Caffeinated Dev Team
 * @copyright      Copyright (c) 2015, Caffeinated
 * @license        https://tldrlegal.com/license/mit-license MIT License
 */
class GithubHookServiceProvider extends ServiceProvider
{
    use CodexHookProvider;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Register the routes for the webhook
        require_once(__DIR__.'/Http/routes.php');

        // Register and configure github connection
        $this->app->register(\GrahamCampbell\GitHub\GitHubServiceProvider::class);

        $this->app->instance('github.connection', $this->app->make('github.factory')->make([
            'token'  => env('GITHUB_TOKEN', null),
            'method' => 'token'
        ]));

        // Add the hook which merges the codex config.
        $this->addCodexHook('factory:ready', GithubFactoryHook::class);

        // And add the hook providing the  `github` method for projects to retreive a gitsync instance for that specific project
        $this->addCodexHook('project:ready', GithubProjectHook::class);

        // And register the sync command for console
        $this->commands(CodexSyncGithubCommand::class);
    }
}
