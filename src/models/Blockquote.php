<?php

namespace markhuot\igloo\models;

use Craft;

class Blockquote extends Box {

    use Styleable;

    /** @var Text */
    public $content;

    /** @var Text */
    public $author;

    /**
     * @inheritdoc
     */
    function getChildren()
    {
        return array_filter([
            'content' => $this->content,
            'author' => $this->author,
        ]);
    }

}
