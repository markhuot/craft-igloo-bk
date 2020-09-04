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
     * The slots this block exposes to be filled with other blocks
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
        $this->callTraits('init');
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
    function serialize()
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
        $traitData = $this->serializeTraits();
        if (!empty($traitData)) {
            $data = array_merge($data, $traitData);
        }

        $content = $this->toArray();
        if (!empty($content)) {
            $data['data'] = $content;
        }

        return $data;
    }

    /**
     * Serialize the trait data. Since there could be many traits the `callTraits`
     * method, here, actually returns an array of each traits response.
     * 
     * E.g., give two traits, "use Styleable" and "use Accessible" it would return
     * an array of,
     * 
     * [
     *     "Styleable" => ["styles" => [...]] // This is the response from the Styleable trait
     *     "Accessible" => ["aria-attr" => [...]] // This is the response from the Accessible trait
     * ]
     * 
     * We don't _really_ care about the per-trait groupings, though so we flatten it
     * by one level and then merge them all together.
     * 
     * @return array
     */
    function serializeTraits()
    {
        $traitData = $this->callTraits('serialize');

        $data = collect($traitData)
            ->reduce(function ($carry, $item) {
                return $carry->merge($item);
            }, collect([]))
            ->filter()
            ->toArray();

        return $data;
    }

    /**
     * Call a magic method on each trait, like initTrait or serializeTrait
     * 
     * @var string $prefix
     * @return array
     */
    function callTraits($prefix)
    {
        $result = [];

        $reflect = new \ReflectionClass($this);
        $traits = $reflect->getTraits();
        foreach ($traits as $trait) {
            $method = $prefix . $trait->getShortName();
            if ($reflect->hasMethod($method)) {
                $result[$trait->getShortName()] = $this->{$method}();
            }
        }

        return $result;
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
