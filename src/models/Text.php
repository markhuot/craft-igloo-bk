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
    function __construct($content=null, $config=[])
    {
        parent::__construct(array_merge(['content' => $content], $config));
    }

    /**
     * Serialize the data for the persistent storage
     *
     * @return string[]|null
     */
    function serialize()
    {
        return ['content' => $this->content];
    }

    /**
     * Unserialize the data coming out of the persistent storage
     *
     * @param string[] $config
     * @return static
     */
    static function unserialize($config=[])
    {
        return new static($config);
    }



}
