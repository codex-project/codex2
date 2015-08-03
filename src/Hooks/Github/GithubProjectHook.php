<?php
/**
 * Part of the Caffeinated PHP packages.
 *
 * MIT License and copyright information bundled with this package in the LICENSE file
 */
namespace Codex\Codex\Hooks\Github;

use Codex\Codex\Hook;
use Codex\Codex\Project;
use Github\Client;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Filesystem\Filesystem;

/**
 * This is the Hook.
 *
 * @package        Codex\Codex
 * @author         Caffeinated Dev Team
 * @copyright      Copyright (c) 2015, Caffeinated
 * @license        https://tldrlegal.com/license/mit-license MIT License
 */
class GithubProjectHook implements Hook
{

    protected $files;

    protected $github;

    protected $cache;

    public function __construct(Filesystem $files, Client $github, Cache $cache)
    {
        $this->files  = $files;
        $this->github = $github;
        $this->cache  = $cache;
    }

    public function handle(Project $project)
    {
        $that = $this;
        // Add a method on the project class that creates a new GitSync for that specific project
        Project::macro('github', function () use ($that, $project)
        {
            return new GitSync($project, $that->github, $that->files, $that->cache);
        });
    }

}
