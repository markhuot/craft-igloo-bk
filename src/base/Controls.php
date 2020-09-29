<?php

namespace markhuot\igloo\base;

use markhuot\igloo\controls\Text as TextControl;

trait Controls {

    protected $controls = [];

    function initControls()
    {
        $this->controls = [
            new TextControl('id', $this->attributes->id),
            new TextControl('className', $this->attributes->className),
            new TextControl('style[color]', $this->attributes->style->color, ['section' => 'Typography']),
            new TextControl('style[weight]', $this->attributes->style->weight, ['section' => 'Typography']),
            new TextControl('style[fontSize]', $this->attributes->style->fontSize, ['section' => 'Typography']),
            new TextControl('style[textTransform]', $this->attributes->style->textTransform, ['section' => 'Typography']),
        ];
    }

    function getControls()
    {
        return collect($this->controls);
    }

    function getControlHtml()
    {
        return implode('', array_map(function ($control) {
            return $control->getInputHtml();
        }, $this->getControls()));
    }

}