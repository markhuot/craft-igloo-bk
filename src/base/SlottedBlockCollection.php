<?php

namespace markhuot\igloo\base;

class SlottedBlockCollection {

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

}