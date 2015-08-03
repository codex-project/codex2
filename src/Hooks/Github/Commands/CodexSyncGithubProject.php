<?php
/**
* Part of the Caffeinated PHP packages.
*
* MIT License and copyright information bundled with this package in the LICENSE file
 */

namespace Codex\Codex\Hooks\Github\Commands;

use Codex\Codex\Factory;


/**
 * This is the DocitSyncGithubProject.
 *
 * @package        Codex\Codex
 * @author         Caffeinated Dev Team
 * @copyright      Copyright (c) 2015, Caffeinated
 * @license        https://tldrlegal.com/license/mit-license MIT License
 */
class CodexSyncGithubProject
{

    protected $factory;

    /**
     * DocitSyncGithubProject constructor.
     */
    public function __construct(Factory $factory)
    {
        $this->factory = $factory;
    }

    public function fire($job, $data)
    {
        $this->factory->make($data['project'])->github()->syncAll();
    }
}
