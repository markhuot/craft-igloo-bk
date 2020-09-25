<?php

namespace markhuot\igloo\controls;

class Text {

    /** @var string */
    public $name;

    /** @var string */
    public $value;

    /** @var string */
    public $label;

    /** @var string */
    public $section;

    function __construct($name, &$value, $config=[])
    {
        $this->name = $name;
        $this->value = &$value;

        foreach ($config as $k => $v) {
            $this->{$k} = $v;
        }
    }

    function getName()
    {
        return $this->name;
    }

    function getValue()
    {
        return $this->value;
    }

    function getLabel()
    {
        return !empty($this->label) ? $this->label : $this->name;
    }

    function getInputHtml()
    {
        return \Craft::$app->getView()->renderTemplate('igloo/controls/text', [
            'control' => $this,
        ]);
    }

}