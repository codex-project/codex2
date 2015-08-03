<?php
/**
 * Part of the Caffeinated PHP packages.
 *
 * MIT License and copyright information bundled with this package in the LICENSE file
 */
namespace Codex\Codex\Traits;

use Codex\Codex\Factory;


/**
 * Part of the Radic packages.
 */
trait CodexHookProvider
{
    /**
     * Add a hook handler to codex
     *
     * @param string $hookPoint
     * @param \Closure|\Codex\Codex\Hook $handler
     */
    protected function addCodexHook($hookPoint, $handler)
    {
        Factory::hook($hookPoint, $handler);
    }
}
