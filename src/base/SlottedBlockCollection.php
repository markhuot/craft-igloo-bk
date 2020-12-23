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

    function __get($key)
    {
        if (in_array($key, ['block'])) {
            return $this->collection->{$key};
        }

        // @todo add exception for non-existent property access
    }

    function append($block)
    {
        $block->slot = $this->slot;
        $this->collection->append($block);
        return $this;
    }

    function appendRaw(...$blocks)
    {
        foreach ($blocks as $block) {
            $block->tree = $this->id;
            $block->collection = $this;
            $block->slot = $this->slot;
        }

        $this->collection->appendRaw(...$blocks);

        return $this;
    }

    function insertAtIndex(Block $block, $index)
    {
        $blocks = $this->_fetch();
        $indexes = array_keys($blocks);
        $block->slot = $this->slot;
        
        if (isset($indexes[$index])) {
            $actualIndex = $indexes[$index];
            $this->collection->insertAtIndex($block, $actualIndex);
        }
        else if ($index === count($blocks)) {
            $this->collection->append($block);
        }
        else {
            throw \Exception("Could not insert a block at index {$index}.");
        }
        
        return $this;
    }

    function getIndexOfBlock(Block $block)
    {
        // _fetch gives back raw indexes, we want normalized index
        $blocks = array_values($this->_fetch());

        foreach ($blocks as $index => $b) {
            if ($block === $b) {
                return $index;
            }
        }

        return false;
    }

    function getAtIndex($index)
    {
        return $this->offsetGet($index);
    }

    function deleteAtIndex($index)
    {
        $this->offsetUnset($index);
        return $this;
    }

    private function _fetch()
    {
        $blocks = [];

        foreach ($this->collection as $index => $block)
        {
            if ($block->slot === $this->slot) {
                $blocks[$index] = $block;
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
        // because _fetch returns the actual collection indexes not 0-based slot indexes
        // we have to re-normalize the indexes to 0-based for proper iteration. The reason
        // _fetch returns non-0-based indexes is so ArrayAccess can set/unset on the
        // base collection properly. See offsetSet for related code.
        $blocks = array_values($this->_fetch());
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
        // because _fetch returns the actual collection indexes not 0-based slot indexes
        // we have to re-normalize the indexes to 0-based for proper iteration. The reason
        // _fetch returns non-0-based indexes is so ArrayAccess can set/unset on the
        // base collection properly. See offsetSet for related code.
        $blocks = array_values($this->_fetch());
        return isset($blocks[$this->index]);
    }

    function offsetSet($offset, $value)
    {
        $blocks = $this->_fetch();
        $indexes = array_keys($blocks);
        $value->slot = $this->slot;
        
        if (is_null($offset)) {
            $this->collection->append($value);
        }
        else if (isset($indexes[$offset])) {
            $actualIndex = $indexes[$offset];
            
            // Since this is a native PHP set we are technically overwriting what's in the
            // specified offset. E.g., if you call `$array[3] = "foo"` you're replacing
            // index 3 with "foo". In terms of blocks that means we need to remove anything that's
            // already there and replace it with our new block.
            $this->collection->insertAtIndex(null, $actualIndex);
            $this->collection->insertAtIndex($value, $actualIndex);
        }
        else {
            $this->collection->append($value);
        }
    }
    
    function offsetExists($offset)
    {
        $blocks = array_values($this->_fetch());
        return isset($blocks[$offset]);
    }
    
    function offsetUnset($offset)
    {
        $blocks = $this->_fetch();
        $indexes = array_keys($blocks);
        $actualIndex = $indexes[$offset];
        $this->collection->insertAtIndex(null, $actualIndex);
    }
    
    function offsetGet($offset)
    {
        $blocks = array_values($this->_fetch());
        return isset($blocks[$offset]) ? $blocks[$offset] : null;
    }

}