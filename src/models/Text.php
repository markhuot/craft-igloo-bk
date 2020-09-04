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
        if (is_array($content)) {
            $config = $content;
            $content = $config['content'] ?? '';
        }

        parent::__construct(array_merge(['content' => $content], $config));
    }

    function fields()
    {
        return ['content'];
    }

    public static function tableName()
    {
        return '{{%igloo_content_text}}';
    }


}
