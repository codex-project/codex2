<?php
/**
 * Part of the Caffeinated PHP packages.
 *
 * MIT License and copyright information bundled with this package in the LICENSE file
 */
namespace Codex\Codex;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Traits\Macroable;
use Symfony\Component\Finder\Finder;

/**
 * This is the Factory.
 *
 * @package        Codex\Codex
 * @author         Caffeinated Dev Team
 * @copyright      Copyright (c) 2015, Caffeinated
 * @license        https://tldrlegal.com/license/mit-license MIT License
 */
class Factory
{
    use Macroable;

    /**
     * Path to the directory containing all docs
     *
     * @var string
     */
    protected $rootDir;

    /**
     * @var Project[]
     */
    protected $projects;

    /**
     * @var
     */
    protected $files;

    /**
     * @var array The codex configuration
     */
    protected $config;

    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;


    /**
     * @param \Illuminate\Contracts\Filesystem\Filesystem $files
     * @param \Illuminate\Contracts\Config\Repository     $config
     * @param \Illuminate\Contracts\Cache\Repository      $cache
     */
    public function __construct(Filesystem $files, Repository $config, Cache $cache)
    {
        $this->rootDir = base_path('resources/docs');
        $this->files   = $files;
        $this->config  = $config->get('codex');
        $this->cache   = $cache;

        static::run('factory:ready', [ $this ]); // ready called after parameters have been set as class properties


        if ( ! isset($this->projects) )
        {
            $this->findAll();
        }

        static::run('factory:done', [ $this ]); // ready called after parameters have been set as class properties
    }

    /**
     * Finds all projects in the rootDir
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function findAll()
    {
        $finder = new Finder();

        /**
         * @var \SplFileObject[] $found
         */
        $found          = $finder->in($this->rootDir)->files()->name('config.php')->depth('<= 1')->followLinks();
        $this->projects = [ ];
        foreach ( $found as $file )
        {
            $name                    = last(explode(DIRECTORY_SEPARATOR, $file->getPath()));
            $config                  = with(new \Illuminate\Filesystem\Filesystem)->getRequire($file->getRealPath());
            $this->projects[ $name ] = array_replace_recursive($this->config('default_project_config'), $config);
        }
    }

    /**
     * make a project object, will represent a project based on directory name
     *
     * @param $name
     * @return \Codex\Codex\Project
     */
    public function make($name)
    {
        if ( ! $this->has($name) )
        {
            throw new \InvalidArgumentException("Project [$name] could not be found in [{$this->rootDir}]");
        }

        $project = new Project($this, $this->files, $name, $this->projects[ $name ]);
        static::run('project:make', [ $this, $project ]);

        return $project;
    }

    /**
     * Check if a project exists
     *
     * @param $name
     * @return bool
     */
    public function has($name)
    {
        return array_key_exists($name, $this->projects);
    }

    /**
     * Generate an url to a project default page, project version default page, project default version document, project default version default document.
     * Lots of options. Can leave any parameter nulled
     *
     * @param null $project
     * @param null $ref
     * @param null $doc
     * @return string
     */
    public function url($project = null, $ref = null, $doc = null)
    {
        $uri = $this->config('base_route');
        if ( ! is_null($project) )
        {
            if ( $project instanceof Project )
            {
                $uri .= '/' . $project->getName();
            }
            else
            {
                $uri .= '/' . $project;
            }
            if ( $ref )
            {
                $uri .= '/' . $ref;
                if ( $doc )
                {
                    $uri .= '/' . $doc;
                }
            }
        }

        return url($uri);
    }

    /**
     * all projects config
     *
     * @return array And array containing projectname -> projectconfigarray
     */
    public function all()
    {
        return $this->projects;
    }

    /**
     * get rootDir value
     *
     * @return string
     */
    public function getRootDir()
    {
        return $this->rootDir;
    }

    /**
     * Retreive codex config using a dot notated key
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
     * get config value
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Set the codex config
     *
     * @param array $config
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    /**
     * get files value
     *
     * @return mixed
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Set the files value
     *
     * @param mixed $files
     * @return Factory
     */
    public function setFiles($files)
    {
        $this->files = $files;

        return $this;
    }

    /**
     * get cache value
     *
     * @return \Illuminate\Cache\CacheManager
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Set the cache value
     *
     * @param \Illuminate\Cache\CacheManager $cache
     * @return Factory
     */
    public function setCache($cache)
    {
        $this->cache = $cache;

        return $this;
    }





    /**
     * @var array
     */
    protected static $hooks = [ ];

    /**
     * ensurePoint
     *
     * @param $name
     */
    protected static function ensurePoint($name)
    {
        if ( ! isset(static::$hooks[ $name ]) )
        {
            static::$hooks[ $name ] = [ ];
        }
    }

    /**
     * hook
     *
     * @param string          $point
     * @param string|\Closure $handler
     */
    public static function hook($point, $handler)
    {

        if ( ! $handler instanceof \Closure && ! in_array(Hook::class, class_implements($handler), false) )
        {
            throw new \InvalidArgumentException("Failed adding hook. Provided handler for [{$point}] is not valid. Either provider a \\Closure or classpath that impelments \\Codex\\Codex\\Hook");
        }
        static::ensurePoint($point);
        static::$hooks[ $point ][] = $handler;
    }

    /**
     * run a hook
     *
     * @param       $name
     * @param array $params
     */
    public static function run($name, array $params = [ ])
    {
        static::ensurePoint($name);
        foreach ( static::$hooks[ $name ] as $handler )
        {
            if ( $handler instanceof \Closure )
            {
                call_user_func_array($handler, $params);
            }
            elseif ( class_exists($handler) )
            {
                $instance = app()->make($handler);

                call_user_func_array([ $instance, 'handle' ], $params);
            }
        }
    }

}
