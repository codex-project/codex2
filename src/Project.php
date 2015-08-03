<?php
/**
 * Part of the Caffeinated PHP packages.
 *
 * MIT License and copyright information bundled with this package in the LICENSE file
 */
namespace Codex\Codex;

use Caffeinated\Beverage\Path;
use Caffeinated\Beverage\Str;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Traits\Macroable;
use vierbergenlars\SemVer\version;

/**
 * This is the Project.
 *
 * @package        Codex\Codex
 * @author         Caffeinated Dev Team
 * @copyright      Copyright (c) 2015, Caffeinated
 * @license        https://tldrlegal.com/license/mit-license MIT License
 */
class Project
{
    use Macroable;

    const SHOW_MASTER_BRANCH = 0;
    const SHOW_LAST_VERSION = 1;
    const SHOW_LAST_VERSION_OTHERWISE_MASTER_BRANCH = 2;
    const SHOW_CUSTOM = 3;

    /**
     * @var string The project name (dir name)
     */
    protected $name;

    /**
     * @var string Path to the project directory
     */
    protected $path;

    /**
     * @var array The config.php for this project
     */
    protected $config;

    /**
     * @var \Codex\Codex\Factory The codex factory
     */
    protected $factory;

    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Branches are non-semver compatible folder names
     * @var array
     */
    protected $branches;

    /**
     * A ref is either a branch or version. The default ref is used for various things if $ref is not set
     * @var
     */
    protected $defaultRef;

    /**
     * A ref is either a branch or version.
     * @var string
     */
    protected $ref;

    /**
     * A ref is either a branch or version. This property contains all folder names for this project
     * @var array
     */
    protected $refs;

    /**
     * All folder names that had valid semver names are inside this collection as a Version instance.
     * @var array
     */
    protected $versions;

    /** Instantiates the class
     *
     * @param \Codex\Codex\Factory              $factory
     * @param \Illuminate\Filesystem\Filesystem $files
     * @param                                   $name
     * @param                                   $config
     */
    public function __construct(Factory $factory, Filesystem $files, $name, $config)
    {
        // assign simple properties
        $this->factory = $factory;
        $this->files   = $files;
        $this->name    = $name;
        $this->config  = $config;
        $this->path    = $path = Path::join($factory->getRootDir(), $name);
        Factory::run('project:ready', [$this]);

        $directories    = $this->files->directories($this->path);
        $branches       = [ ];
        $this->refs     = [ ];
        $this->versions = array_filter(array_map(function ($dirPath) use ($path, $name, &$branches)
        {
            $version      = Str::create(Str::ensureLeft($dirPath, '/'))->removeLeft($path)->removeLeft(DIRECTORY_SEPARATOR);
            $version      = (string)$version->removeLeft($name.'/');
            $this->refs[] = $version;
            try
            {
                return new version($version);
            }
            catch (\RuntimeException $e)
            {
                $branches[] = $version;
            }
        }, $directories), 'is_object');
        $this->branches = $branches;


        // check what version/branch to show by default
        $defaultRef = count($this->versions) > 0 ? head($this->versions) : head($branches);
        switch ( $this->config[ 'default' ] )
        {
            case Project::SHOW_LAST_VERSION:
                usort($this->versions, function (version $v1, version $v2)
                {
                    return version::gt($v1, $v2) ? -1 : 1;
                });
                $defaultRef = head($this->versions);
                break;
            case Project::SHOW_LAST_VERSION_OTHERWISE_MASTER_BRANCH:
                if ( count($this->versions) > 0 )
                {
                    usort($this->versions, function (version $v1, version $v2)
                    {
                        return version::gt($v1, $v2) ? -1 : 1;
                    });
                }
                $defaultRef = count($this->versions) > 0 ? head($this->versions) : head($branches);
                break;
            case Project::SHOW_MASTER_BRANCH:
                $defaultRef = 'master';
                break;
            case Project::SHOW_CUSTOM:
                $defaultRef = $this->config[ 'custom' ];
                break;
        }
        $this->ref = $this->defaultRef = (string)$defaultRef;

    }

    /**
     * Retreive this projects config using a dot notated key
     *
     * @param null $key
     * @param null $default
     * @return array|mixed
     */
    public function config($key = null, $default = null)
    {
        if ( is_null($key) )
        {
            return $this->config;
        }

        return array_get($this->config, $key, $default);
    }

    /**
     * setConfig for the project
     *
     * @param array $config
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    /**
     * Set the ref (version/branch) you want to use. getDocument will be getting stuff using the ref
     *
     * @param $name
     * @return $this
     */
    public function setRef($name)
    {
        $this->ref = $name;

        return $this;
    }

    /**
     * Get a document using the provided path. It will retreive it from the current $ref or otherwise the $defaultRef folder
     *
     * @param string $path
     * @return \Codex\Codex\Document
     */
    public function getDocument($path = '')
    {

        if ( strlen($path) === 0 )
        {
            $path = 'index';
        }

        $path = Path::join($this->path, $this->ref, $path . '.md');

        $document = new Document($this->factory, $this, $this->files, $path);
        Factory::run('project:document', [$document]);
        return $document;
    }


    public function getMenu()
    {
        $path = Path::join($this->getPath(), $this->ref, 'menu.yml');
        return new Menu($this, $this->files, $this->factory->getCache(), $path);
    }

    /**
     * get ref value
     *
     * @return string
     */
    public function getRef()
    {
        return $this->ref;
    }

    /**
     * get defaultRef value
     *
     * @return string
     */
    public function getDefaultRef()
    {
        return $this->defaultRef;
    }

    /**
     * get refs value
     *
     * @return array
     */
    public function getRefs()
    {
        return $this->refs;
    }

    /**
     * Sort all refs in such a way that
     *
     * @return array
     */
    public function getSortedRefs()
    {
        $versions = $this->versions;
        usort($versions, function (version $v1, version $v2)
        {
            return version::gt($v1, $v2) ? -1 : 1;
        });
        $versions = array_map(function (version $v)
        {
            return $v->getVersion();
        }, $versions);

        return array_merge($this->branches, $versions);
    }

    /**
     * get files value
     *
     * @return Filesystem
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Set the files value
     *
     * @param Filesystem $files
     * @return Project
     */
    public function setFiles($files)
    {
        $this->files = $files;

        return $this;
    }

    /**
     * get name value
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * get path value
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * get config value
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * get branches value
     *
     * @return array
     */
    public function getBranches()
    {
        return $this->branches;
    }

    /**
     * get versions value
     *
     * @return array
     */
    public function getVersions()
    {
        return $this->versions;
    }

    /**
     * Set the path value
     *
     * @param string $path
     * @return Project
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * get factory value
     *
     * @return Factory
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * Set the factory value
     *
     * @param Factory $factory
     * @return Project
     */
    public function setFactory($factory)
    {
        $this->factory = $factory;

        return $this;
    }




}
