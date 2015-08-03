<?php
/**
 * Part of the Robin Radic's PHP packages.
 *
 * MIT License and copyright information bundled with this package
 * in the LICENSE file or visit http://radic.mit-license.com
 */
namespace Codex\Codex\Hooks\Github;

use Github\Client;

/**
 * This is the GitsyncContent.
 *
 * @package        Docit\Core
 * @version        1.0.0
 * @author         Robin Radic
 * @license        MIT License
 * @copyright      2015, Robin Radic
 * @link           https://github.com/robinradic
 * @method boolean exists($path, $ref)
 * @method string show($path, $ref)
 */
class GitSyncContent
{

    /**
     * @var string
     */
    protected $owner;

    /** @var string */
    protected $repository;

    /** @var \GrahamCampbell\GitHub\GitHubManager */
    protected $github;

    /**
     * Instanciates the class
     *
     * @param                                      $owner
     * @param                                      $repository
     * @param \GrahamCampbell\GitHub\GitHubManager $github
     */
    public function __construct($owner, $repository, Client $github)
    {
        $this->owner      = $owner;
        $this->repository = $repository;
        $this->github     = $github;
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([ $this->github->repo()->contents(), $name ], array_merge([ $this->owner, $this->repository ], $arguments));
    }
}
