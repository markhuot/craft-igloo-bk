<?php

namespace markhuot\igloo\models;

use markhuot\igloo\base\Block;
use markhuot\igloo\valueobjects\Styles;

class Box extends Block {

    /**
     * @inheritdoc
     */
    public $slots = [
        'children',
    ];

    /**
     * Any blocks that are nested inside this box
     *
     * @var Block[]
     */
    public $children = [];

    /**
     * Append a child block
     *
     * @param Block $block
     * @return Box
     */
    function append(Block $block)
    {
        $this->children[] = $block;
        return $this;
    }

}
