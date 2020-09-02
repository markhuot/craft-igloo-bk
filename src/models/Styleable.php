<?php

namespace markhuot\igloo\models;

use markhuot\igloo\valueobjects\Styles;

trait Styleable {

    /** @var Styles */
    public $styles;

    function initStyleable()
    {
        $this->styles = new Styles();
    }

    /**
     * Add styles to the fields list
     *
     * @return array
     */
    function serializeStyleable()
    {
        return [
            'styles' => function () {
                return $this->styles->toArray();
            }
        ];
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
    function setStyles($styles = [])
    {
        foreach ($styles as $key => $value) {
            $this->styles->{$key} = $value;
        }

        return $this;
    }

}
