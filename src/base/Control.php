<?php

namespace markhuot\igloo\base;

use craft\helpers\StringHelper;

class Control {

    /** @var string */
    public $name;

    /** @var string */
    public $value;

    /** @var string */
    public $label;

    /** @var string */
    public $section;

    /** @var callable */
    public $display;

    function __construct($name, &$value, $config=[])
    {
        $this->name = $name;
        $this->value = &$value;

        $this->display = function () { return true; };

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
        $reflect = new \ReflectionClass($this);
        $shortName = $reflect->getShortName();
        $templateName = StringHelper::toKebabCase($shortName);
        return \Craft::$app->getView()->renderTemplate('igloo/controls/'.$templateName, [
            'control' => $this,
        ]);
    }

    function display()
    {
        $func = $this->display;
        return $func();
    }

}