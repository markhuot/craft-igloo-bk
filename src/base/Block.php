<?php

namespace markhuot\igloo\base;

use Craft;
use craft\base\Model;

class Block extends Model {

    /**
     * Init the block and any traits attached to the block
     */
    public function init()
    {
        parent::init();

        $reflect = new \ReflectionClass($this);
        $traits = $reflect->getTraits();
        foreach ($traits as $trait) {
            $method = 'init'.$trait->getShortName();
            if ($reflect->hasMethod($method)) {
                $this->{$method}();
            }
        }
    }

    /**
     * Prepare the block for saving finding it's children and normalizing
     * its data model
     *
     * @return array
     */
    function prepareSave()
    {
        $data = [
            'id' => uniqid(),
            'type' => get_class($this),
        ];

        if ($this->hasChildren()) {
            $data['children'] = $this->prepareSaveChildren();
        }

        if ($serializedData = $this->serialize()) {
            $data['data'] = $serializedData;
        }

        return $data;
    }

    /**
     * Whether the block has child blocks
     *
     * @return bool
     */
    function hasChildren()
    {
        return !empty($this->getChildren());
    }

    /**
     * Get any nested children of this block. This is typically implemented
     * by a subclassed block since each block has a different manner of storing
     * child blocks. E.g., a Box contains a flat array of children while a
     * Blockquote may contain fixed "content" and "author" child blocks.
     *
     * @return Block[]
     */
    function getChildren()
    {
        return [];
    }

    /**
     * Get a child by index
     *
     * @param $index
     * @return Block
     */
    function getChildAtIndex($index)
    {
        return $this->getChildren()[$index];
    }

    /**
     * Run prepareSave over all of the children of this block
     *
     * @return array[]
     */
    function prepareSaveChildren()
    {
        return array_map(function (Block $block) {
            return $block->prepareSave();
        }, $this->getChildren());
    }

    /**
     * Serialize the data for the persistent storage
     *
     * @return array
     */
    public function serialize()
    {
        return null;
    }

    /**
     * Fields that should be output when converted to an array
     *
     * @return array
     */
    function fields()
    {
        $traitFields = [];
        $reflect = new \ReflectionClass($this);
        $traits = $reflect->getTraits();
        foreach ($traits as $trait) {
            $method = lcfirst($trait->getShortName()).'Fields';
            if ($reflect->hasMethod($method)) {
                $traitFields = array_merge($traitFields, $this->{$method}());
            }
        }

        return array_merge(parent::fields(), [
            '__type' => function  () {
                return get_class($this);
            },
        ], $traitFields);
    }

    /**
     * Get the template that should render
     */
    function getInputHtml()
    {
        $reflect = new \ReflectionClass($this);
        $template = strtolower($reflect->getShortName());
        return Craft::$app->view->renderTemplate('igloo/blocks/'.$template, ['block' => $this]);
    }

}
