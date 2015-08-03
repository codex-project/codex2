<?php
/**
 * Part of the Caffeinated PHP packages.
 *
 * MIT License and copyright information bundled with this package in the LICENSE file
 */
namespace Codex\Codex;

use Illuminate\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
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

    /** Instantiates the class
     *
     * @param \Illuminate\Filesystem\Filesystem       $files
     * @param \Illuminate\Config\Repository           $config
     * @param \Illuminate\Contracts\Events\Dispatcher $dispatcher
     */
    public function __construct(Filesystem $files, Repository $config, Dispatcher $dispatcher)
    {
        $this->rootDir = base_path('resources/docs');
        $this->files   = $files;
        $this->config  = $config->get('codex');

        if ( ! isset($this->projects) )
        {
            $this->findAll();
        }

        static::run('factory:ready', [ $this ]);
    }

    /**
     * Finds all projects in the rootDir
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function findAll()
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
            $config                  = $this->files->getRequire($file->getRealPath());
            $this->projects[ $name ] = array_merge_recursive($this->config('default_project_config'), $config);
        }
    }

    /**
     * make
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
     * Set the codex config
     *
     * @param array $config
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
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
     * @param string $point
     * @param string|\Closure $handler
     */
    public static function hook($point, $handler)
    {

        if(! $handler instanceof \Closure && ! in_array(Hook::class, class_implements($handler), false)){
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
