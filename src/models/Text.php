<?php

namespace markhuot\igloo\models;

use markhuot\igloo\base\Block;

class Text extends Block {

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

    function getIcon()
    {
        return '📝';
        //return '<svg width="1em" height="1em" viewBox="0 0 16 16" class="bi bi-text-left" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
        //    <path fill-rule="evenodd" d="M2 12.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm0-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5zm0-3a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm0-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5z"/>
        //</svg>';
    }

    function getLabel()
    {
        return substr($this->content, 0, 100);
    }

    // function fields()
    // {
    //     return ['content'];
    // }

    /**
     * Serialize the data to the persistent storage
     */
    function serialize()
    {
        $record = parent::serialize();

        if (!empty($this->content)) {
            $record['{{%igloo_content_text}}'] = [
                'content' => $this->content,
            ];
        }

        return $record;
    }

    /**
     * Unserialize the data from the persistent storage
     */
    function unserialize($config=[])
    {
        parent::unserialize($config);

        $this->content = $config['{{%igloo_content_text}}']['content'] ?? null;
        
        return $this;
    }

    public static function tableName()
    {
        return '{{%igloo_content_text}}';
    }


}
