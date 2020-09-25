<?php

namespace markhuot\igloo\base;

use Tightenco\Collect\Support\Collection;

class BlockCollection implements \Iterator {

    /** @var Block */
    public $block;

    /** @var Block[] */
    protected $blocks = [];

    /**
     * The iterable index
     * 
     * @var int
     */
    protected $index = 0;

    /**
     * When a parent block is not set use this to
     * track lft/rgt of the collection
     */
    protected $rgt = 0;

    /**
     * Construct the collection
     * 
     * @param Block $block
     * @param Block[] $blocks
     */
    function __construct(Block $block=null, array $blocks=[])
    {
        $this->block = $block;
        $this->blocks = $blocks;
    }

    function getRgt()
    {
	return $this->block->rgt ?? $this->rgt;
    }

    function setRgt($value)
    {
        if (!empty($this->block->rgt)) {
	    return $this->block->rgt = $value;
	}

	return $this->rgt = $value;

    }

    /**
     * Add a block on to the end of the collection
     * 
     * @return self
     */
    function append($block) {
        $rgt = $block->setLftRgt($this->getRgt());
        
	    $this->setRgt($rgt + 1);
        $this->blocks[] = $block;
        
        return $this;
    }

    function concat($blocks)
    {
        foreach ($blocks as $block) {
            $this->append($block);
        }

        return $this;
    }

    /**
     * Reduce the children down to a scalar
     */
    function reduce(callable $callback, $initial=null)
    {
        return array_reduce($this->blocks, $callback, $initial);
    }

    function flatten()
    {
        return new static(null, array_merge(...array_map(function ($block) {
            return $block->flatten()->toArray();
        }, $this->blocks)));
    }

    function count()
    {
        return count($this->blocks);
    }

    function first()
    {
        return $this->blocks[0] ?? null;
    }

    function toArray()
    {
        return $this->blocks;
    }

    /**
     * Serialize our block objects down to dumb arrays for the persistent store
     * 
     * @return array
     */
    function serialize()
    {
        return array_map(function ($block) {
            return $block->serialize();
        }, $this->blocks);
    }

    function current()
    {
        return $this->blocks[$this->index];
    }

    function key()
    {
        return $this->index;
    }

    function next()
    {
        ++$this->index;
    }

    function rewind ()
    {
        $this->index = 0;
    }

    function valid()
    {
        return isset($this->blocks[$this->index]);
    }

}
