<?php

namespace markhuot\igloo\base;

class SlottedBlockCollection implements \Iterator, \ArrayAccess, \Countable {

    /**
     * @var BlockCollection
     */
    public $collection;

    /**
     * The slot name this collection is filtered by
     * 
     * @var string
     */
    public $slot;

    protected $index = 0;

    function __construct($collection, $slot)
    {
        $this->collection = $collection;
        $this->slot = $slot;
    }

    function append($block)
    {
        $block->slot = $this->slot;
        $this->collection->append($block);
        return $this;
    }

    private function _fetch()
    {
        $blocks = [];

        foreach ($this->collection as $block)
        {
            if ($block->slot === $this->slot) {
                $blocks[] = $block;
            }
        }

        return $blocks;
    }

    function count()
    {
        $blocks = $this->_fetch();
        return count($blocks);
    }

    function current()
    {
        $blocks = $this->_fetch();
        return $blocks[$this->index];
    }
    
    function key()
    {
        return $this->index;
    }

    function next()
    {
        ++$this->index;
    }
    
    function rewind()
    {
        $this->index = 0;
    }
    
    function valid()
    {
        $blocks = $this->_fetch();
        return isset($blocks[$this->index]);
    }

    function offsetSet($offset, $value)
    {
        $blocks = $this->_fetch();
        
        if (is_null($offset)) {
            $blocks[] = $value;
        }
        else {
            $blocks[$offset] = $value;
        }
    }
    
    function offsetExists($offset)
    {
        $blocks = $this->_fetch();
        return isset($blocks[$offset]);
    }
    
    function offsetUnset($offset)
    {
        $blocks = $this->_fetch();
        unset($blocks[$offset]);
    }
    
    function offsetGet($offset)
    {
        $blocks = $this->_fetch();
        return isset($blocks[$offset]) ? $blocks[$offset] : null;
    }

}