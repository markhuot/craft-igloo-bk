<?php

namespace markhuot\igloo\valueobjects;

class ClassList implements \Iterator {

    public $className;
    protected $index = 0;

    function __construct(&$className) {
        $this->className = &$className;
    }

    function remove($needle)
    {
        $this->className = implode(' ', array_filter($this->toArray(), function ($haystack) use ($needle) {
            return $needle !== $haystack;
        }));
    }

    function add($className)
    {
        $this->className = trim("{$this->className} {$className}");
    }

    function contains($needle)
    {
        return array_search($needle, $this->toArray()) !== false;
    }

    function toggle($needle)
    {
        if ($this->contains($needle)) {
            $this->remove($needle);
        }
        else {
            $this->add($needle);
        }
    }

    function replace($old, $new)
    {
        if ($this->contains($old)) {
            $this->remove($old);
        }

        $this->add($new);
    }

    function toArray()
    {
        return preg_split('/\s+/', $this->className);
    }

    function rewind()
    {
        $this->index = 0;
    }

    function current()
    {
        return $this->toArray()[$this->index];
    }

    function key()
    {
        return $this->index;
    }

    function next()
    {
        return ++$this->index;
    }

    function valid()
    {
        return isset($this->toArray()[$this->index]);
    }

}