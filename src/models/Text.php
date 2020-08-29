<?php

namespace markhuot\igloo\models;

use markhuot\igloo\base\Block;

class Text extends Block {

    /**
     * Allow styles on Text
     */
    use Styleable;

    /** @var string */
    public $content = '';

    /**
     * Text constructor.
     *
     * @param $content
     */
    public function __construct($content)
    {
        parent::__construct(['content' => $content]);
    }

    public function serialize()
    {
        return ['content' => $this->content];
    }

}
