<?php

namespace markhuot\igloo\models;

use markhuot\igloo\valueobjects\Styles;

trait Styleable {

    /** @var Styles */
    public $styles;

    /**
     * Init the styles. If they're already set to an array, then they must
     * have come through the __constructor so convert them over to a true
     * Styles object
     */
    function initStyleable()
    {
        if ($this->styles === null) {
            $this->styles = new Styles();
        }

        if (is_array($this->styles) && !empty($this->styles)) {
            $this->styles = new Styles($this->styles);
        }
    }

    /**
     * Add styles to the fields list
     *
     * @return array
     */
    function serializeStyleable()
    {
        $data = ['{{%igloo_block_styles}}' => []];
        $styles = $this->styles->toArray();
        
        if (empty($styles)) {
            return $data;
        }

        $data['{{%igloo_block_styles}}']['styles'] = json_encode($styles);

        return $data;
    }

    /**
     * Unserialize raw data from the persistent storage in to the model
     * 
     * @var array $config
     */
    function unserializeStyleable($config)
    {
        $json = json_decode($config['{{%igloo_block_styles}}']['styles'] ?? "{}", true);
        $this->setStyles($json);
    }

    /**
     * Get a computed style attribute based on the raw styles
     */
    function getStyleAttribute()
    {
        return $this->styles->toAttributeString();
    }

    /**
     * Fluently set the styles
     *
     * @param array $styles
     * @return static
     */
    function setStyles($styles)
    {
        foreach ($styles as $key => $value) {
            $this->styles->{$key} = $value;
        }

        return $this;
    }

}
