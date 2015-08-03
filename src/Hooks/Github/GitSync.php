<?php
/**
 * Part of the Robin Radic's PHP packages.
 *
 * MIT License and copyright information bundled with this package
 * in the LICENSE file or visit http://radic.mit-license.com
 */
namespace Codex\Codex\Hooks\Github;

use Caffeinated\Beverage\Path;
use Caffeinated\Beverage\Str;
use Codex\Codex\Project;
use Github\Client;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use vierbergenlars\SemVer\expression;
use vierbergenlars\SemVer\version;

/**
 * This is the Gitsync.
 *
 * @package        Docit\Core
 * @version        1.0.0
 * @author         Robin Radic
 * @license        MIT License
 * @copyright      2015, Robin Radic
 * @link           https://github.com/robinradic
 */
class GitSync
{

    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * @var \Github\Client
     */
    protected $github;

    /**
     * @var \Codex\Codex\Project
     */
    protected $project;

    /**
     * @var array|mixed
     */
    protected $projectConfig;

    /**
     * @var
     */
    protected $settings;

    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * @param \Codex\Codex\Project                   $project
     * @param \Github\Client                         $github
     * @param \Illuminate\Filesystem\Filesystem      $files
     * @param \Illuminate\Contracts\Cache\Repository $cache
     */
    public function __construct(Project $project, Client $github, Filesystem $files, Cache $cache)
    {
        $this->project       = $project;
        $this->github        = $github;
        $this->files         = $files;
        $this->cache         = $cache;
        $this->projectConfig = $project->config();
    }

    protected function setting($key)
    {
        return array_get($this->projectConfig[ 'github_settings' ], $key);
    }

    public function syncWithProgress(\Closure $tick)
    {
        $current = 0;
        foreach ( $this->getBranchesToSync() as $branch )
        {
            $this->syncRef($branch, 'branch');
            $current++;
            $tick($current);
        }
        foreach ( $this->getVersionsToSync() as $version )
        {
            $this->syncRef($version, 'tag');
            $current++;
            $tick($current);
        }
    }

    public function syncAll()
    {
        $this->syncBranches();
        $this->syncVersions();
    }

    public function syncBranches()
    {
        foreach ( $this->getBranchesToSync() as $branch )
        {
            $this->syncRef($branch, 'branch');
        }
    }

    public function syncVersions()
    {
        foreach ( $this->getVersionsToSync() as $version )
        {
            $this->syncRef($version, 'tag');
        }
    }

    public function syncRef($ref, $type)
    {
        $content = new GitSyncContent($this->setting('owner'), $this->setting('repository'), $this->github);
        $hasDocs = $content->exists($this->setting('paths.docs'), $ref);

        if ( ! $hasDocs )
        {
            return;
        }

        $destinationDir  = Path::join($this->project->getPath(), $ref);
        $menu            = $content->show($this->setting('paths.menu'), $ref);
        $menuContent     = base64_decode($menu[ 'content' ]);
        $menuArray       = Yaml::parse($menuContent);
        $unfilteredPages = [ ];
        $this->extractPagesFromMenu($menuArray[ 'menu' ], $unfilteredPages);
        $filteredPages = [ ];

        # filter out pages that link to external sites
        foreach ( $unfilteredPages as $page )
        {
            if ( Str::startsWith($page, 'http', true) || Str::startsWith($page, '//', true) || Str::startsWith($page, 'git', true) )
            {
                continue;
            }
            if ( ! in_array($page, $filteredPages, true) )
            {
                $filteredPages[] = $page;
            }
        }


        # get all pages their content and save to local
        foreach ( $filteredPages as $pagePath )
        {
            $path = Path::join($this->setting('paths.docs'), $pagePath . '.md');

            # check if page exists on remote
            $exists = $content->exists($path, $ref);
            if ( ! $exists )
            {
                continue;
            }

            # the raw github page content response
            $pageRaw = $content->show('/' . $path, $ref);

            # transform remote directory path to local directory path
            $dir = Str::remove($pageRaw[ 'path' ], $this->setting('paths.docs'));
            $dir = Str::remove($dir, $pageRaw[ 'name' ]);
            $dir = Path::canonicalize(Path::join($destinationDir, $dir));

            if ( ! $this->files->isDirectory($dir) )
            {
                $this->files->makeDirectory($dir, 0777, true);
            }

            # raw github page to utf8 and save it to local
            $this->files->put(Path::join($dir, $pageRaw[ 'name' ]), base64_decode($pageRaw[ 'content' ]));
        }

        # save the menu to local
        $this->files->put(Path::join($destinationDir, 'menu.yml'), $menuContent);

        # if enabled, Get phpdoc structure and save it
        if ( $this->setting('phpdoc') )
        {
            $hasStructure = $content->exists($this->setting('paths.phpdoc'), $ref);
            if ( $hasStructure )
            {
                $structure    = $content->show($this->setting('paths.phpdoc'), $ref);
                $structureXml = base64_decode($structure[ 'content' ]);

                $destination    = Path::join($destinationDir, 'structure.xml');
                $destinationDir = Path::getDirectory($destination);

                if ( ! $this->files->isDirectory($destinationDir) )
                {

                    $this->files->makeDirectory($destinationDir, 0755, true);
                }
                $this->files->put($destination, $structureXml);
            }
        }
        # set cache sha for branches, not for tags (obviously)
        if ( $type === 'branch' )
        {
            $branchData = $this->github->repositories()->branches($this->setting('owner'), $this->setting('repository'), $ref);
            $this->cache->forever(md5($this->project->getName() . $branchData[ 'name' ]), $branchData[ 'commit' ][ 'sha' ]);
        }
    }

    public function getBranchesToSync()
    {
        $allowedBranches = $this->setting('sync.branches');
        if ( count($allowedBranches) === 0 )
        {
            return [ ];
        }

        $branchesToSync = [ ];
        $branches       = $this->github->repositories()->branches($this->setting('owner'), $this->setting('repository'));

        foreach ( $branches as $branch )
        {
            $branchName = $branch[ 'name' ];
            if ( ! in_array('*', $allowedBranches, true) and ! in_array($branchName, $allowedBranches, true) )
            {
                continue;
            }
            $sha             = $branch[ 'commit' ][ 'sha' ];
            $cacheKey        = md5($this->project->getName() . $branchName);
            $branch          = $this->cache->get($cacheKey, false);
            $destinationPath = Path::join($this->project->getPath(), $branchName);

            if ( $branch !== $sha or $branch === false or ! $this->files->isDirectory($destinationPath) )
            {
                $branchesToSync[] = $branchName;
            }
        }

        return $branchesToSync;
    }

    public function getVersionsToSync()
    {
        $versionsToSync      = [ ];
        $currentVersions     = $this->project->getRefs();
        $allowedVersionRange = new expression($this->setting('sync.versions'));
        $tags                = $this->github->repositories()->tags($this->setting('owner'), $this->setting('repository'));

        foreach ( $tags as $tag )
        {
            $version = new version($tag[ 'name' ]);
            if ( $version->satisfies($allowedVersionRange) === false or in_array($version->getVersion(), $currentVersions, true) )
            {
                continue;
            }
            $versionsToSync[] = $version;
        }

        return $versionsToSync;
    }

    public function extractPagesFromMenu($menuArray, &$pages = [ ])
    {
        foreach ( $menuArray as $key => $val )
        {
            if ( is_string($key) && is_string($val) )
            {
                $pages[] = $val;
            }
            elseif ( is_string($key) && $key === 'children' && is_array($val) )
            {
                $this->extractPagesFromMenu($val, $pages);
            }
            elseif ( isset($val[ 'name' ]) )
            {
                if ( isset($val[ 'page' ]) )
                {
                    $pages[] = $val[ 'page' ];
                }
                if ( isset($val[ 'href' ]) )
                {
                    //$item['href'] = $this->resolveLink($val['href']);
                }
                if ( isset($val[ 'icon' ]) )
                {
                    //$item['icon'] = $val['icon'];
                }
                if ( isset($val[ 'children' ]) && is_array($val[ 'children' ]) )
                {
                    $this->extractPagesFromMenu($val[ 'children' ], $pages);
                }
            }
        }
    }
}
