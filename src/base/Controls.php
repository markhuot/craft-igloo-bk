<?php

namespace markhuot\igloo\base;

use markhuot\igloo\controls\Box as BoxControl;
use markhuot\igloo\controls\Text as TextControl;
use markhuot\igloo\controls\TextAlign as TextAlignControl;
use markhuot\igloo\controls\Select as SelectControl;

trait Controls {

    protected $controls = [];

    function initControls()
    {
        $this->controls = [
            new TextControl('id', $this->attributes->id),
            new TextControl('className', $this->attributes->className),
            new BoxControl(null, $this->attributes->style, ['section' => 'Margin & Padding']),
            new SelectControl('style[display]', $this->attributes->style->display, ['section' => 'Size & Position', 'options' => [
                'block' => 'Block',
                'inline' => 'Inline',
                'flex' => 'Flex',
            ]]),
            new TextControl('style[width]', $this->attributes->style->width, ['section' => 'Size & Position']),
            new TextControl('style[height]', $this->attributes->style->height, ['section' => 'Size & Position']),
            new SelectControl('style[justifyContent]', $this->attributes->style->justifyContent, [
                'section' => 'Flex',
                'options' => [
                    'space-between' => 'Space Between',
                    'flex-start' => 'Flex Start',
                    'flex-end' => 'Flex End',
                ],
                'display' => function () {
                    return $this->attributes->style->display === 'flex';
                },
            ]),
            new TextControl('style[border]', $this->attributes->style->border, ['section' => 'Border']),
            new TextControl('style[borderRadius]', $this->attributes->style->borderRadius, ['section' => 'Border']),
            new TextControl('style[backgroundAttachment]', $this->attributes->style->backgroundAttachment, ['section' => 'Background']),
            new TextControl('style[backgroundColor]', $this->attributes->style->backgroundColor, ['section' => 'Background']),
            new TextControl('style[backgroundImage]', $this->attributes->style->backgroundImage, ['section' => 'Background']),
            new TextControl('style[backgroundPosition]', $this->attributes->style->backgroundPosition, ['section' => 'Background']),
            new TextControl('style[backgroundRepeat]', $this->attributes->style->backgroundRepeat, ['section' => 'Background']),
            new TextControl('style[backgroundSize]', $this->attributes->style->backgroundSize, ['section' => 'Background']),
            new TextControl('style[color]', $this->attributes->style->color, ['section' => 'Typography']),
            new TextControl('style[weight]', $this->attributes->style->weight, ['section' => 'Typography']),
            new TextControl('style[fontSize]', $this->attributes->style->fontSize, ['section' => 'Typography']),
            new TextAlignControl('style[textAlign]', $this->attributes->style->textAlign, ['section' => 'Typography']),
            new TextControl('style[textTransform]', $this->attributes->style->textTransform, ['section' => 'Typography']),
        ];
    }

    // function unserializeControls($config)
    // {
    //     // When a block is unserialized re-set the controls with the current values of the attributes
    //     $this->initControls();
    // }

    function getControls()
    {
        return collect($this->controls)
            ->filter->display();
            //->filter(function ($c) { return $c->display(); });
    }

    function getControlHtml()
    {
        return collect($this->getControls())
            //->filter(function ($c) { return $c->display(); })
            ->map->getInputHtml()
            ->implode('');

        // return implode('', array_map(function ($control) {
        //     return $control->getInputHtml();
        // }, $this->getControls()));
    }

}