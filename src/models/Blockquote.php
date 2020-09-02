<?php

namespace markhuot\igloo\models;

use Craft;

class Blockquote extends Box {

    /**
     * Allow the quote to be styleable
     */
    use Styleable;

    /** 
     * The quote content
     * 
     * @child
     * @var Text
     */
    public $content;

    /** @var Text */
    public $author;

    /**
     * @inheritdoc
     */
    public $slots = [
        'content',
        'author',
    ];

}
