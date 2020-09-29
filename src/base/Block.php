<?php

namespace markhuot\igloo\base;

use Craft;
use craft\base\Model;
use craft\helpers\StringHelper;
use markhuot\igloo\base\BlockCollection;

class Block extends Model {

    use Attributes;
    use Controls;

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
    public $lft = 0;
    public $rgt = 1;

    /**
     * The slots this block exposes to be filled with other blocks
     * 
     * @var string[]
     */
    public $slots = ['children'];

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
     * Any children that are a child of this block
     * 
     * @var BlockCollection
     */
    public $children;

    /**
     * The parent collection of this block
     *
     * @todo rename this to `parent`
     * @var BlockCollection
     */
    public $collection;

    /**
     * Init the block and any traits attached to the block
     */
    function init()
    {
        parent::init();
        $this->children = new BlockCollection($this->tree, $this);
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
     * Get a property or a slot
     * 
     * @return mixed
     */
    function __get($key)
    {
        if (in_array($key, $this->getSlotNames())) {
            return new SlottedBlockCollection($this->children, $key);
        }

        return parent::__get($key);
    }

    function __isset($key)
    {
        if (\in_array($key, $this->getSlotNames())) {
            return true;
        }

        return parent::__isset($key);
    }

    /**
     * Get the icon for the block
     * 
     * @return string
     */
    function getIcon()
    {
        return '📦';
        //return '<svg width="1em" height="1em" viewBox="0 0 16 16" class="bi bi-box-seam" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
        //    <path fill-rule="evenodd" d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5l2.404.961L10.404 2l-2.218-.887zm3.564 1.426L5.596 5 8 5.961 14.154 3.5l-2.404-.961zm3.25 1.7l-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923l6.5 2.6zM7.443.184a1.5 1.5 0 0 1 1.114 0l7.129 2.852A.5.5 0 0 1 16 3.5v8.662a1 1 0 0 1-.629.928l-7.185 2.874a.5.5 0 0 1-.372 0L.63 13.09a1 1 0 0 1-.63-.928V3.5a.5.5 0 0 1 .314-.464L7.443.184z"/>
        //</svg>';
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
     * Gets a readable label based on the type
     * 
     * @return string
     */
    function getTypeLabel()
    {
        $reflect = new \ReflectionClass($this);
        return $reflect->getShortName();
    }

    /**
     * Get a human readable label for the block. This is commonly overridden by
     * subclasses to provide a more detalied label
     * 
     * @return string
     */
    function getLabel()
    {
        return $this->getTypeLabel();
    }

    /**
     * Whether the block has child blocks
     *
     * @return bool
     */
    function hasChildren()
    {
        return $this->children->count() > 0;
    }

    /**
     * Get any nested children of this block. This is typically implemented
     * by a subclassed block since each block has a different manner of storing
     * child blocks. E.g., a Box contains a flat array of children while a
     * Blockquote may contain fixed "content" and "author" child blocks.
     *
     * @return \markhuot\igloo\base\BlockCollection
     */
    function getChildren()
    {
        return $this->children;
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
     * Walk over the node and each child node by calling a callback
     *
     * @param callable $callback
     * @return Block
     */
    function walkChildren(callable $callback)
    {
        $callback($this);

        foreach ($this->getChildren() as $child) {
            $child->walkChildren($callback);
        }

        return $this;
    }

    /**
     * Walk over the parents moving up the tree
     *
     * @param callable $callback
     * @return Block
     */
    function walkParents(callable $callback)
    {
        $pointer = $this;
        while ($pointer) {
            $callback($pointer);
            $pointer = $pointer->collection->block ?? null;
        }

        return $this;
    }

    function getTombstones()
    {
        return $this->children->getTombstones();
    }

    function flatten()
    {
        $nodes = [];

        $this->walkChildren(function ($node) use (&$nodes) {
            $nodes[] = $node;
        });

        return new BlockCollection($this->tree, $this, $nodes);
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
     * Set the lft/rgt based on the passed initial lft
     *
     * @param int $initial
     * @return mixed|null
     */
    function setLftRgt($initial = 0)
    {
        $this->lft = $initial;
        
        return $this->rgt = $this->getChildren()->reduce(function ($carry, $child) {
            return $child->setLftRgt($carry) + 1;
        }, $this->lft + 1);
    }

    function next()
    {
	    if (!$this->collection) {
		    return false;
	    }

	    $index = $this->collection->getIndexOfBlock($this);
	    return $this->collection->getBlocksAfterIndex($index + 1)->first();
    }

    function nextAll()
    {
	    if (!$this->collection) {
		    return new BlockCollection($this->tree, $this);
	    }

	    $index = $this->collection->getIndexOfBlock($this);
	    return $this->collection->getBlocksAfterIndex($index + 1);
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
                'lft' => $this->lft,
                'rgt' => $this->rgt,
            ], function ($value) {
                return $value !== null;
            })),
        ]);

        // $content = $this->toArray();
        // if (!empty($content)) {
        //     $data[$this->tableName()] = array_merge($meta, $content);
        // }

        //if ($this->hasChildren()) {
        //    $data['children'] = array_map(function (Block $block) {
        //        return $block->serialize();
        //    }, $this->getChildren());;
        //}

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
        
        // Traits are not currently reflected from parent classes. This is a
        // known feature of PHP. You must manually loop over parent classes
        // to see applied traits, https://www.php.net/manual/en/reflectionclass.gettraits.php
        $parent = $reflect->getParentClass();
        while ($parent) {
            $traits = $parent->getTraits();
            foreach ($traits as $trait) {
                $method = $prefix . $trait->getShortName();
                if ($parent->hasMethod($method)) {
                    $result[$trait->getShortName()] = $this->{$method}(...$args);
                }
            }
            $parent = $parent->getParentClass();
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
        $this->slot = $config[static::STRUCTURE_TABLE_NAME]['slot'] ?? null;
        $this->lft = isset($config[static::STRUCTURE_TABLE_NAME]['lft']) ? (int)$config[static::STRUCTURE_TABLE_NAME]['lft'] : null;
        $this->rgt = isset($config[static::STRUCTURE_TABLE_NAME]['rgt']) ? (int)$config[static::STRUCTURE_TABLE_NAME]['rgt'] : null;

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
     * Get the template that should render
     */
    function getInputHtml()
    {
        $reflect = new \ReflectionClass($this);
        $template = strtolower($reflect->getShortName());
        return Craft::$app->view->renderTemplate('igloo/blocks/'.$template, ['block' => $this]);
    }

}
