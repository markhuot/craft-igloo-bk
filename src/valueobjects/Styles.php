<?php

namespace markhuot\igloo\valueobjects;

use craft\helpers\StringHelper;
use yii\base\BaseObject;

class Styles extends BaseObject {

    public $backgroundAttachment;
    public $backgroundColor;
    public $backgroundImage;
    public $backgroundPosition;
    public $backgroundRepeat;
    public $backgroundSize;
    public $border;
    public $borderLeft;
    public $borderRadius;
    public $color;
    public $display;
    public $fontSize;
    public $height;
    public $justifyContent;
    public $marginBottom;
    public $marginLeft;
    public $marginRight;
    public $marginTop;
    public $paddingBottom;
    public $paddingLeft;
    public $paddingRight;
    public $paddingTop;
    public $textAlign;
    public $textTransform;
    public $weight;
    public $width;

    function __construct($config = [])
    {
        if (empty($config)) {
            return parent::__construct();
        }

        $newConfig = [];

        foreach ($config as $k => $v) {
            $newConfig[StringHelper::toCamelCase($k)] = $v;
        }

        parent::__construct($newConfig);
    }

    function setAll($values)
    {
        if (empty($values)) {
            return $this;
        }

        foreach ($values as $key => $value) {
            // @todo warning when setting a value that doesn't exist
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }

        return $this;
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

    function __toString()
    {
        return $this->toAttributeString();
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
