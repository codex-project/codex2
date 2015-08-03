<?php
/**
 * Part of the Robin Radic's PHP packages.
 *
 * MIT License and copyright information bundled with this package
 * in the LICENSE file or visit http://radic.mit-license.com
 */
namespace Docit\Core\Parsers;

use Laradic\Support\Arrays;

/**
 * This is the Parser class.
 *
 * @package        Laradic\Docit
 * @version        1.0.0
 * @author         Robin Radic
 * @license        MIT License
 * @copyright      2015, Robin Radic
 * @link           https://github.com/robinradic
 */
class PhpdocParser
{
    protected $tree;

    protected $data = [ ];

    public function parse($xmlString)
    {
        $xml       = simplexml_load_string($xmlString);
        $data      = json_decode(json_encode($xml), true);

        foreach ( $data[ 'file' ] as $iFile => $file )
        {
            $f = [ ];
            if ( isset($file[ 'trait' ]) )
            {
                $f[ 'type' ] = 'trait';
                $f           = array_merge($f, $this->getClass($file[ 'trait' ]));
            }
            elseif ( isset($file[ 'class' ]) )
            {
                $f[ 'type' ] = 'class';
                $f           = array_merge($f, $this->getClass($file[ 'class' ]));
            }
            elseif ( isset($file[ 'interface' ]) )
            {
                $f[ 'type' ] = 'interface';
                $f           = array_merge($f, $this->getClass($file[ 'interface' ]));
            }
            else
            {
                continue;
            }
            $this->data[] = $f;
        }

        $this->makeEmptyTree($this->data);
        foreach ( $this->data as $f )
        {
            $this->putIntoTree($f);
        }

        return $this;
    }

    public function getTree()
    {
        return $this->tree;
    }

    public function getData()
    {
        return $this->data;
    }

    protected function makeEmptyTree($filesData)
    {
        $this->tree = [ ];
        foreach ( $filesData as $file )
        {
            $current =& $this->tree;
            foreach ( explode('\\', $file[ 'namespace' ]) as $part )
            {
                if ( ! isset($part) )
                {
                    $a = 'a';
                }
                if ( ! isset($current[ $part ]) )
                {
                    $current[ $part ] = array();
                }
                $current =& $current[ $part ];
            }
        }
    }

    protected function putIntoTree($file)
    {
        $tree  =& $this->tree;
        $parts = array_merge(explode('\\', $file[ 'namespace' ]), [ $file[ 'name' ] ]);
        foreach ( $parts as $part )
        {
            if ( isset($tree[ $part ]) )
            {
                $tree =& $tree[ $part ];
            }
            else
            {
                $tree[] = $file;
            }
        }
    }

    protected function getClass(array $class)
    {
        $new = Arrays::only($class, [ 'extends', 'name', 'full_name' ]);
        $new = array_merge($new, Arrays::only($class[ '@attributes' ], [ 'final', 'abstract', 'namespace' ]));
        $new = array_merge($new, Arrays::only($class[ 'docblock' ], [ 'description', 'long-description' ]));
        $new = array_merge($new, $this->getMethods($class));
        $new = array_merge($new, $this->getProperties($class));

        return $new;
    }

    protected function getProperties(array $type)
    {
        $new = [ ];

        if ( isset($type[ 'property' ][ 'docblock' ]) )
        {
            $new[ 'properties' ] = [ $this->getProperty($type[ 'property' ]) ];
        }
        elseif ( isset($type[ 'property' ]) )
        {
            $new[ 'properties' ] = [ ];
            foreach ( $type[ 'property' ] as $property )
            {
                $new[ 'properties' ][] = $this->getProperty($property);
            }
        }

        return $new;
    }

    protected function getMethods(array $type)
    {
        $new = [ ];
        if ( isset($type[ 'method' ][ 'docblock' ]) )
        {
            $new[ 'methods' ] = [ $this->getMethod($type[ 'method' ]) ];
        }
        elseif ( isset($type[ 'method' ]) )
        {
            $new[ 'methods' ] = [ ];
            foreach ( $type[ 'method' ] as $method )
            {
                $new[ 'methods' ][] = $this->getMethod($method);
            }
        }

        return $new;
    }

    protected function getProperty(array $property)
    {
        $new = [ ];
        $new = array_merge($new, Arrays::only($property, [ 'name', 'full_name' ]));
        $new = array_merge($new, Arrays::only($property[ '@attributes' ], [ 'static', 'visibility' ]));
        $new = array_merge($new, Arrays::only($property[ 'docblock' ], [ 'description', 'long-description' ]));

        if ( isset($property[ 'docblock' ][ 'tag' ][ 0 ]) )
        {
            foreach ( $property[ 'docblock' ][ 'tag' ] as $t )
            {
                $new = array_merge($new, $this->getPropertyTag($t));
            }
        }
        elseif ( isset($property[ 'docblock' ][ 'tag' ]) )
        {
            $new = array_merge($new, $this->getPropertyTag($property[ 'docblock' ][ 'tag' ]));
        }

        return $new;
    }

    protected function getPropertyTag(array $t)
    {
        $new = [
            'type' => isset($t[ 'type' ]) ? $t['type'] : 'mixed'
        ];

        $tag = $t[ '@attributes' ];
        if ( $tag[ 'name' ] === 'var' )
        {
            $new = array_replace_recursive($new, [
                'type'        => $tag[ 'type' ]
            ]);
        }

        return $new;
    }

    protected function getMethod(array $method)
    {
        $new = [
            'parameters' => [ ],
            'returns'    => 'void'
        ];
        $new = array_merge($new, Arrays::only($method, [ 'name', 'full_name' ]));
        $new = array_merge($new, Arrays::only($method[ '@attributes' ], [ 'final', 'abstract', 'static', 'visibility' ]));
        $new = array_merge($new, Arrays::only($method[ 'docblock' ], [ 'description', 'long-description' ]));
        if ( isset($method[ 'docblock' ][ 'tag' ][ 0 ]) )
        {
            foreach ( $method[ 'docblock' ][ 'tag' ] as $t )
            {
                $new = array_merge($new, $this->getMethodTag($t));
            }
        }
        elseif ( isset($method[ 'docblock' ][ 'tag' ]) )
        {
            $new = array_merge($new, $this->getMethodTag($method[ 'docblock' ][ 'tag' ]));
        }

        return $new;
    }

    protected function getMethodTag(array $t)
    {
        $new = [
            'parameters' => [ ],
            'returns'    => 'void'
        ];

        $tag = $t[ '@attributes' ];
        if ( $tag[ 'name' ] === 'param' )
        {
            $new[ 'parameters' ][] = [
                'name'        => $tag[ 'variable' ],
                'description' => $tag[ 'description' ],
                'type'        => $tag[ 'type' ]
            ];
        }
        elseif ( $tag[ 'name' ] === 'return' )
        {
            $new[ 'returns' ] = $tag[ 'type' ];
        }

        return $new;
    }
}
