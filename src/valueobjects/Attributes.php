<?php

namespace markhuot\igloo\valueobjects;

use yii\base\BaseObject;

class Attributes extends BaseObject {

    /** @var string */
    public $id;

    /** @var string */
    public $className = null;

    /** @var ClassList */
    public $classlist;

    /** @var Styles */
    public $style;

    function init() {
        $this->setStyle($this->style);
        $this->classlist = new ClassList($this->className);
    }

    function setAll($attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->set($key, $value);
        }

        return $this;
    }

    function set($key, $value)
    {
        $method = 'set' . ucfirst($key);

        if (method_exists($this, $method)) {
            $this->{$method}($value);
        }
        else {
            $this->{$key} = $value;
        }

        return $this;
    }

    function setStyle($value)
    {
        if (!is_a($this->style, Styles::class)) {
            $this->style = new Styles;
        }
        
        $this->style->setAll($value);

        return $this;
    }

    // function __construct($config = [])
    // {
    //     $newConfig = [];

    //     foreach ($config as $k => $v) {
    //         $newConfig[StringHelper::toCamelCase($k)] = $v;
    //     }

    //     parent::__construct($newConfig);
    // }

    function toArray()
    {
        $attributes = [];

        $reflect = new \ReflectionClass($this);
        $properties = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $prop) {
            if (in_array($prop->getName(), ['classlist'])) {
                continue;
            }

            $value = $this->{$prop->getName()};
            if (is_object($value) && \method_exists($value, 'toArray')) {
                $value = $value->toArray();
            }
            if (!empty($value)) {
                $attributes[$prop->getName()] = $value;
            }
        }

        return $attributes;
    }

    // function toAttributeString()
    // {
    //     $attr = [];

    //     $reflect = new \ReflectionClass($this);
    //     $properties = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
    //     foreach ($properties as $prop) {
    //         $key = StringHelper::toKebabCase($prop->getName());
    //         $value = $this->{$prop->getName()};
    //         $attr[] = "{$key}: {$value}";
    //     }

    //     return implode(';', $attr);
    // }    

}