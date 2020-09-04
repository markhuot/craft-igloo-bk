<?php

namespace markhuot\igloo\base;

use Craft;
use craft\base\Model;
use craft\helpers\StringHelper;

class Block extends Model {

    /** @var string */
    public $id;

    /** @var string */
    public $uid;

    /**
     * The slots this block exposes to fill with other blocks
     * 
     * @var string[]
     */
    public $slots = [];

    /**
     * When rendered as a child of another block this will
     * contain the attribute of the parent that is holding
     * the child. E.g., when a text block is nested inside
     * a split "column" the slot would be "column."
     * 
     * @var string
     */
    public $slot;

    /**
     * Init the block and any traits attached to the block
     */
    function init()
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
     * @return string the name of the table associated with this model
     */
    public static function tableName()
    {
        return null;
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
        $children = [];

        foreach ($this->getSlotNames() as $slotName) {
            if (!empty($this->{$slotName})) {
                $child = $this->{$slotName};
                if (is_array($child)) {
                    foreach ($child as $c) {
                        // @TODO shouldn't have to set this when pulling data out
                        // it should already be set when the slots are hydrated from
                        // the database
                        $c->slot = $slotName;
                    }
                    $children = array_merge($children, $child);
                }
                else {
                    // @TODO shouldn't have to set this when pulling data out
                    // it should already be set when the slots are hydrated from
                    // the database
                    $child->slot = $slotName;
                    $children[] = $child;
                }
            }
        }

        return $children;
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
     * Get the plain string names of each slot this block exposes
     */
    function getSlotNames()
    {
        $slots = [];

        foreach ($this->slots as $k => $v) {
            if (is_numeric($k)) {
                $slots[] = $v;
            }
            else {
                $slots[] = $k;
            }
        }

        return $slots;
    }

    /**
     * Get the slot configuration
     * 
     * @var string $name
     */
    function getSlotInfo($name)
    {
        $defaultInfo = [
            'limit' => null,
        ];

        if (isset($this->slots[$name])) {
            return array_merge($defaultInfo, $this->slots[$name]);
        }

        if (array_search($name, $this->slots) !== false) {
            return $defaultInfo;
        }

        return false;
    }

    /**
     * Serialize the data for the persistent storage
     *
     * @return array
     */
    public function serialize()
    {
        $data = array_filter([
            'id' => $this->id,
            'uid' => $this->uid,
            'type' => get_class($this),
            'tableName' => $this->tableName(),
            'slot' => $this->slot,
        ]);

        if ($this->hasChildren()) {
            $data['children'] = array_map(function (Block $block) {
                return $block->serialize();
            }, $this->getChildren());;
        }

        // @todo serialize traits, like "Styleable" so that we
        // get a new top-level key called styleable with the styles
        // stored inside

        $content = $this->toArray();
        if (!empty($content)) {
            $data['data'] = $content;
        }

        return $data;
    }

    /**
     * Unserialize the data coming out of the persistent storage
     *
     * @param string[] $config
     * @return static
     */
    function unserialize($config=[])
    {
        foreach (($config['children'] ?? []) as $child) {
            $slot = $child['slot'];
            $this->{$slot}[] = (new \markhuot\igloo\services\Blocks())->hydrate($child);
        }

        foreach (($config['data'] ?? []) as $k => $v) {
            $this->{$k} = $v;
        }

        return $this;
    }

    /**
     * Fields that should be output when converted to an array
     *
     * @return array
     */
    function fields()
    {
        return [];

        // $traitFields = [];
        // $reflect = new \ReflectionClass($this);
        // $traits = $reflect->getTraits();
        // foreach ($traits as $trait) {
        //     $method = lcfirst($trait->getShortName()).'Fields';
        //     if ($reflect->hasMethod($method)) {
        //         $traitFields = array_merge($traitFields, $this->{$method}());
        //     }
        // }

        // return array_merge(parent::fields(), [
        //     '__type' => function  () {
        //         return get_class($this);
        //     },
        // ], $traitFields);
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
