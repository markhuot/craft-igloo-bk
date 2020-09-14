<?php

namespace markhuot\igloo\base;

use Craft;
use craft\base\Model;
use craft\helpers\StringHelper;

class Block extends Model {

    /**
     * The table name
     */
    const TABLE_NAME = '{{%igloo_blocks}}';

    /**
     * The table name to store the hierachy
     */
    const STRUCTURE_TABLE_NAME = '{{%igloo_block_structure}}';

    /** @var string */
    public $id;

    /** @var string */
    public $uid;

    /** @var Carbon */
    public $dateCreated;
    
    /** @var Carbon */
    public $dateUpdated;

    /**
     * The tree ID this block belongs to. Warning blocks can be a part of
     * multiple trees so this is just the tree that this block is curerntly
     * being rendered within. A single block ID could have multiple treeIDs
     * 
     * @var string
     */
    public $tree;

    /**
     * The nested set storage
     */
    public $lft;
    public $rgt;

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
    static function tableName()
    {
        return null;
    }

    /**
     * Returns a string represrntation of the type of block
     * 
     * @return string
     */
    function getType()
    {
        return get_class($this);
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
     * Walk over each child node and call a callback on each child
     * 
     * @param closure $callback
     */
    function walkChildren($callback)
    {
        $callback($this);

        foreach ($this->getChildren() as $child) {
            $callback($child);
            $child->walkChildren($callback);
        }

        return $this;
    }

    /**
     * Get the plain string names of each slot this block exposes
     * 
     * @return string[]
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
     * Prepare a block for saving to the persistent storage by assigning a UUID. The UUID
     * can be used to remap anonymous/newly created blocks from memory to the persistent
     * storage.
     */
    function prepare()
    {
        if ($this->uid === null) {
            $this->uid = StringHelper::UUID();
        }

        if ($this->hasChildren()) {
            foreach ($this->getChildren() as $child) {
                $child->prepare();
            }
        }
        
        return $this;
    }
    
    /**
     * Remove all identifying info about this block so it could be used in a repeated test
     * or re-inserted in to the database as a new block/clone.
     */
    function anonymize()
    {
        $this->id = null;
        $this->dateCreated = null;
        $this->dateUpdated = null;
        $this->uid = null;
        $this->tree = null;
        
        if ($this->hasChildren()) {
            foreach ($this->getChildren() as $child) {
                $child->anonymize();
            }
        }

        return $this;
    }

    /**
     * Serialize the data for the persistent storage
     *
     * @return array
     */
    function serialize()
    {
        $meta = array_filter([
            'id' => $this->id,
            'uid' => $this->uid,

        ]);

        $data = array_filter([
            static::TABLE_NAME => array_merge($meta, [
                'type' => $this->getType(),
            ]),
            static::STRUCTURE_TABLE_NAME => array_merge($meta, array_filter([
                'tree' => $this->tree,
                'slot' => $this->slot,
            ])),
        ]);

        // $content = $this->toArray();
        // if (!empty($content)) {
        //     $data[$this->tableName()] = array_merge($meta, $content);
        // }

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
            ->toArray();

        return $data;
    }

    /**
     * Call a magic method on each trait, like initTrait or serializeTrait
     * 
     * @var string $prefix
     * @return array
     */
    function callTraits($prefix, ...$args)
    {
        $result = [];

        $reflect = new \ReflectionClass($this);
        $traits = $reflect->getTraits();
        foreach ($traits as $trait) {
            $method = $prefix . $trait->getShortName();
            if ($reflect->hasMethod($method)) {
                $result[$trait->getShortName()] = $this->{$method}(...$args);
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
        $this->id = $config[static::TABLE_NAME]['id'] ?? null;
        $this->uid = $config[static::TABLE_NAME]['uid'] ?? null;
        $this->tree = $config[static::STRUCTURE_TABLE_NAME]['tree'] ?? null;
        $this->lft = $config[static::STRUCTURE_TABLE_NAME]['lft'] ?? null;
        $this->rgt = $config[static::STRUCTURE_TABLE_NAME]['rgt'] ?? null;

        foreach (($config['children'] ?? []) as $child) {
            $slot = $child[static::STRUCTURE_TABLE_NAME]['slot'];
            $block = (new \markhuot\igloo\services\Blocks())->hydrate($child);
            $block->slot = $slot;
            $this->{$slot}[] = $block;
        }

        $this->callTraits('unserialize', $config);

        return $this;
    }

    function set($key, $value)
    {
        $method = 'set' . ucfirst($key);
        if (method_exists($this, $method)) {
            $this->{$method}($value);
            return $this;
        }

        $this->{$key} = $value;
        return $this;
    }

    function fill(array $values)
    {
        foreach ($values as $k => $v) {
            $this->set($k, $v);
        }

        return $this;
    }

    /**
     * Fields that should be output when converted to an array
     *
     * @return array
     */
    // function fields()
    // {
    //     return [];

    //     // $traitFields = [];
    //     // $reflect = new \ReflectionClass($this);
    //     // $traits = $reflect->getTraits();
    //     // foreach ($traits as $trait) {
    //     //     $method = lcfirst($trait->getShortName()).'Fields';
    //     //     if ($reflect->hasMethod($method)) {
    //     //         $traitFields = array_merge($traitFields, $this->{$method}());
    //     //     }
    //     // }

    //     // return array_merge(parent::fields(), [
    //     //     '__type' => function  () {
    //     //         return get_class($this);
    //     //     },
    //     // ], $traitFields);
    // }

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
