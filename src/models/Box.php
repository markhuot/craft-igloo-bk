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
     * Append a child block
     *
     * @param Block $block
     * @return Box
     */
    function append(Block $block)
    {
        $this->children->append($block);
        return $this;
    }

}
