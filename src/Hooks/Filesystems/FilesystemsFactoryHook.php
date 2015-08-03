<?php
/**
 * Part of the Caffeinated PHP packages.
 *
 * MIT License and copyright information bundled with this package in the LICENSE file
 */
namespace Codex\Codex\Hooks\Filesystems;

use Codex\Codex\Factory;
use Codex\Codex\Hook;
use Illuminate\Filesystem\Filesystem;


/**
 * This is the StorageFactoryHook.
 *
 * @package        Codex\Codex
 * @author         Caffeinated Dev Team
 * @copyright      Copyright (c) 2015, Caffeinated
 * @license        https://tldrlegal.com/license/mit-license MIT License
 */
class FilesystemsFactoryHook implements Hook
{
    protected $files;
    /** Instantiates the class */
    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }

    public function handle(Factory $codex){

        // Add the github hook specific config to the codex config. It will add some default_project_config stuff
        $config = $this->files->getRequire(__DIR__ . '/config.php');
        $codex->setConfig(array_merge_recursive($codex->config(), $config));
    }
}
