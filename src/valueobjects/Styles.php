<?php

namespace markhuot\igloo\valueobjects;

use craft\helpers\StringHelper;
use yii\base\BaseObject;

class Styles extends BaseObject {

    public $borderLeft = null;
    public $paddingLeft = null;
    public $color = null;
    public $fontSize = null;

    function __construct($config = [])
    {
        $newConfig = [];

        foreach ($config as $k => $v) {
            $newConfig[StringHelper::toCamelCase($k)] = $v;
        }

        parent::__construct($newConfig);
    }

    function toArray()
    {
        $reflect = new \ReflectionClass($this);
        $properties = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);

        $styles = [];
        foreach ($properties as $prop) {
            $value = $this->{$prop->getName()};
            if (!empty($value)) {
                $styles[$prop->getName()] = $value;
            }
        }
        return $styles;
    }

    function toAttributeString()
    {
        $attr = [];

        $reflect = new \ReflectionClass($this);
        $properties = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($properties as $prop) {
            $key = StringHelper::toKebabCase($prop->getName());
            $value = $this->{$prop->getName()};
            $attr[] = "{$key}: {$value}";
        }

        return implode(';', $attr);
    }

}
