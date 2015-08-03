<?php
/**
 * Part of the Robin Radic's PHP packages.
 *
 * MIT License and copyright information bundled with this package
 * in the LICENSE file or visit http://radic.mit-license.com
 */
namespace Codex\Codex;

use Caffeinated\Beverage\Str;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * This is the Menu.
 *
 * @package        Docit\Core
 * @author         Caffeinated Dev Team
 * @copyright      Copyright (c) 2015, Caffeinated
 * @license        https://tldrlegal.com/license/mit-license MIT License
 */
class Menu implements Jsonable, Arrayable
{

    /**
     * @var \Codex\Codex\Project
     */
    protected $project;

    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * path to meny.yml
     *
     * @var string
     */
    protected $path;

    /**
     * the raw menu.yml content
     *
     * @var string
     */
    protected $raw;

    /**
     * the parsed menu.yml as php array
     *
     * @var array
     */
    protected $menu;

    /**
     * @param \Codex\Codex\Project                   $project
     * @param \Illuminate\Filesystem\Filesystem      $files
     * @param \Illuminate\Contracts\Cache\Repository $cache
     * @param                                        $path
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function __construct(Project $project, Filesystem $files, Cache $cache, $path)
    {
        $this->project = $project;
        $this->files   = $files;
        $this->cache   = $cache;
        $this->path    = $path;
        Factory::run('menu:ready', [ $this ]);

        $this->raw  = $files->get($path);
        $this->menu = $this->parse($this->raw);


        Factory::run('menu:done', [ $this ]);
    }

    /**
     * parseConfig
     *
     * @param $str
     * @return mixed
     */
    protected function parseConfig($str)
    {
        foreach ( array_dot($this->project->getConfig()) as $key => $value )
        {
            $str = str_replace('${project.' . $key . '}', $value, $str);
        }

        return $str;
    }

    /**
     * parse
     *
     * @param $yaml
     * @return array
     */
    protected function parse($yaml)
    {
        $array = Yaml::parse($yaml);

        return $this->resolveMenu($array[ 'menu' ]);
    }

    /**
     * resolveMenu
     *
     * @param $items
     * @return array
     */
    protected function resolveMenu($items)
    {
        $menu = [ ];
        foreach ( $items as $key => $val )
        {
            $key = $this->parseConfig($key);
            $val = $this->parseConfig($val);
            # Key = title, val = relative page path
            if ( is_string($key) && is_string($val) )
            {
                $menu[] = [
                    'name' => $key,
                    'href' => $this->resolveLink($val)
                ];
            }
            elseif ( is_string($key) && $key === 'children' && is_array($val) )
            {
                $menu[] = $this->resolveMenu($val);
            }
            elseif ( isset($val[ 'name' ]) )
            {
                $item = [
                    'name' => $val[ 'name' ]
                ];
                if ( isset($val[ 'href' ]) )
                {
                    $item[ 'href' ] = $this->resolveLink($val[ 'href' ]);
                }
                elseif ( isset($val[ 'page' ]) )
                {
                    $item[ 'href' ] = $this->resolveLink($val[ 'page' ]);
                }
                if ( isset($val[ 'icon' ]) )
                {
                    $item[ 'icon' ] = $val[ 'icon' ];
                }
                if ( isset($val[ 'children' ]) && is_array($val[ 'children' ]) )
                {
                    $item[ 'children' ] = $this->resolveMenu($val[ 'children' ]);
                }
                $menu[] = $item;
            }
        }

        return $menu;
    }

    /**
     * resolveLink
     *
     * @param $val
     * @return mixed
     */
    protected function resolveLink($val)
    {
        if ( Str::startsWith('http', $val, false) )
        {
            return $val;
        }
        else
        {
            $path = Str::endsWith($val, '.md', false) ? Str::remove($val, '.md') : $val;

            return $this->project->getFactory()->url($this->project, $this->project->getRef(), $path);
        }
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->menu;
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->menu, $options);
    }
}
