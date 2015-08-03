<?php
/**
 * Part of the Caffeinated PHP packages.
 *
 * MIT License and copyright information bundled with this package in the LICENSE file
 */
namespace Codex\Codex\Hooks\Filesystems;

use Caffeinated\Beverage\Str;
use Codex\Codex\Document;
use Codex\Codex\Hook;

/**
 * This is the StorageFactoryHook.
 *
 * @package        Codex\Codex
 * @author         Caffeinated Dev Team
 * @copyright      Copyright (c) 2015, Caffeinated
 * @license        https://tldrlegal.com/license/mit-license MIT License
 */
class FilesystemsDocumentHook implements Hook
{

    public function handle(Document $document)
    {
        $path  = $document->getPath();
        $files = $document->getFiles();
        if ( $files instanceof \Illuminate\Filesystem\FilesystemAdapter )
        {
            /** @var \League\Flysystem\AdapterInterface $adapter */
            $adapter = $files->getDriver()->getAdapter();
            if ( ! $adapter instanceof \League\Flysystem\Adapter\Local )
            {
                // Not a local adapter. We are using some file system. We need to fix the paths now
                $newPath = (string)Str::create($path)->removeLeft($document->getProject()->getPath())->removeLeft(DIRECTORY_SEPARATOR);
                $document->setPath($newPath);
            }
        }
    }
}
