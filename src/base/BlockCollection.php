<?php

namespace markhuot\igloo\base;

use Tightenco\Collect\Support\Collection;

class BlockCollection implements \Iterator, \ArrayAccess, \Countable {

    /** @var Block */
    public $block;

    /** @var Block[] */
    protected $blocks = [];

    /** 
     * Blocks that have been removed from the collection
     * 
     * @var Block[] 
     */
    protected $tombstones = [];

    /**
     * The iterable index
     * 
     * @var int
     */
    protected $index = 0;

    /**
     * When a parent block is not set use this to
     * track lft/rgt of the collection
     */
    protected $lft = 0;
    protected $rgt = 0;

    /**
     * The tree id
     * 
     * @var string
     */
    public $id;

    /**
     * Construct the collection
     * 
     * @param Block $block
     * @param Block[] $blocks
     */
    function __construct(string $id=null, Block $block=null, array $blocks=[])
    {
        $this->id = $id;
        $this->block = $block;
        $this->blocks = $blocks;
    }

    // function getLft()
    // {
    //     return $this->block->lft ?? $this->lft;
    // }

    // function setLft($value)
    // {
    //     if (isset($this->block->lft)) {
    //         return $this->block->lft = $value;
    //     }
    //
    //     return $this->lft = $value;
    // }

    // function getRgt()
    // {
    //     return $this->block->rgt ?? $this->rgt;
    // }

    // function setRgt($value)
    // {
    //     if (isset($this->block->rgt)) {
    //         return $this->block->rgt = $value;
    //     }
    //
    //     return $this->rgt = $value;
    // }

    // function getMaxLft()
    // {
    //     if (count($this->blocks) === 0) {
    //         return 0;
    //     }
    //
    //     return $this->last()->lft;
    // }

    // function getMaxRgt()
    // {
    //     if (count($this->blocks) === 0) {
    //         return 0;
    //     }
    //
    //     return $this->last()->rgt;
    // }

    /**
     * Get the lft of a block to be inserted at the specified index
     *
     * @param $index
     * @return int
     */
    function getLftAtIndex($index)
    {
        // Determine the lft of the new block, take the existing lft if it's inserted in front of
        // an existing block
        if (isset($this->blocks[$index]->lft)) {
            $lft = $this->blocks[$index]->lft;
        }

        // If the block is being inserted at the beginning of the collection the lft will be 0 or
        // one more than the parent block's lft
        else if ($index === 0) {
            $lft = isset($this->block->lft) ? $this->block->lft + 1 : 0;
        }

        // If the block is being inserted at the end of the collection the lft will be one more
        // than the right of the last block
        else if ($index === count($this->blocks)) {
            $lft = $this->last()->rgt + 1;
        }

        // An invalid index was passed, we can't insert here
        else {
            throw new \Exception("Can not add a block at index `${index}` there are only ".count($this->blocks)." indexes");
        }

        return $lft;
    }

    function deleteAtIndex($index)
    {
        return $this->insertAtIndex(null, $index);
    }

    /**
     * Insert a block at a specified index
     *
     * @param Block $block
     * @param $index
     * @return $this
     * @throws \Exception
     */
    function insertAtIndex(Block $block=null, $index)
    {
        // Get the new lft of our block
        $lft = $this->getLftAtIndex($index);

        // Make sure we're not trying to delete a block that doesn't exist
        if ($block === null && !isset($this->blocks[$index])) {
            throw new \Exception('You can not delete a block whose index does not exist.');
        }

        // Reset the lft/rgt of the block to be inserted and grab the size of the block
        // after insertion. All subsequent blocks will be adjusted by $size to make room
        // for the new block
        if ($block === null) {
            // @todo make this a function to getBlockAtIndex that throws a index not found error
            $rgt = $this->blocks[$index]->rgt;
            $size = -($rgt - $lft +1);
        }
        else {
            $rgt = $block->setLftRgt($lft);
            $size = $rgt - $lft + 1;
            
            // Store metadata from the collection on the block
            $block->tree = $this->id;
            $block->collection = $this;
        }

        for ($i=$index; $i<count($this->blocks); $i++) {
            $this->blocks[$i]->setLftRgt($this->blocks[$i]->lft + $size);
        }

        // Now that all the lft/rgt are set we can insert the block in to the array
        if ($block === null) {
            $this->tombstones[] = $this->blocks[$index];
            array_splice($this->blocks, $index, 1);
        }
        else {
            array_splice($this->blocks, $index, 0, [$block]);
        }

        // Finally, update the parent block's lft/rgt and allow that to continue bubbling
        // up until all the necessary parents are updated
        if (isset($this->block->rgt)) {
            $this->block->walkParents(function ($parent) use ($size) {
                $parent->rgt += $size;

                $parent->nextAll()->walkChildren(function ($sibling) use ($size) {
                    $sibling->lft += $size;
                    $sibling->rgt += $size;
                });
            });
        }

        // Make everything fluent
        return $this;
    }

    function getIndexOfBlock(Block $needle)
    {
	foreach ($this->blocks as $index => $block) {
	    if ($block === $needle) {
                return $index;
            }
        }

	return false;
    }

    function getBlocksAfterIndex($index)
    {
        return new static($this->id, $this->block, array_slice($this->blocks, $index));
    }

    function getTombstones()
    {
        $childTombstones = collect($this->blocks)
            ->map(function (Block $block) {
                return $block->getTombstones();
            })
            ->filter()
            ->flatten(1)
            ->toArray();

        return array_merge($this->tombstones, $childTombstones);
    }

    function walkChildren($callback)
    {
	    foreach ($this->blocks as $block) {
            $block->walkChildren($callback);
	    }

    	return $this;
    }

    /**
     * Prepend a block in to the collection
     *
     * @param Block $block
     * @return $this
     * @throws \Exception
     */
    function prepend(Block $block)
    {
        return $this->insertAtIndex($block, 0);
    }

    /**
     * Add a block on to the end of the collection
     *
     * @param Block $block
     * @return self
     * @throws \Exception
     */
    function append(Block $block)
    {
        return $this->insertAtIndex($block, count($this->blocks));
    }

    function push(...$blocks)
    {
        foreach ($blocks as $block) {
            $block->collection = $this;
        }

        $this->blocks = array_merge($this->blocks, $blocks);

        return $this;
    }

    /**
     * Recursively add the blocks on to the end of the collection
     *
     * @param Block[] $blocks
     * @return $this
     */
    function concat($blocks)
    {
        foreach ($blocks as $block) {
            $this->append($block);
        }

        return $this;
    }

    /**
     * Reduce the children down to a scalar
     */
    function reduce(callable $callback, $initial=null)
    {
        return array_reduce($this->blocks, $callback, $initial);
    }

    function map(callable $callback)
    {
        return array_map($callback, $this->blocks);
    }

    function forEach(callable $callback)
    {
        foreach ($this->blocks as $block) {
            $callback($block);
        }
        
        return $this;
    }

    function flatten()
    {
        return new static($this->id, $this->block, array_merge(...array_map(function ($block) {
            return $block->flatten()->toArray();
        }, $this->blocks)));
    }

    function first()
    {
        return $this->blocks[0] ?? null;
    }

    function last()
    {
        return $this->blocks[count($this->blocks) - 1];
    }

    function toArray()
    {
        return $this->blocks;
    }

    /**
     * Serialize our block objects down to dumb arrays for the persistent store
     * 
     * @return array
     */
    function serialize()
    {
        return array_map(function ($block) {
            return $block->serialize();
        }, $this->blocks);
    }

    function anonymize()
    {
        return new BlockCollection($this->id, $this->block, array_map(function ($block) {
            return $block->anonymize();
        }, $this->blocks));
    }

    function getInputHtml()
    {
        return \Craft::$app->view->renderTemplate('igloo/base/block-collection', ['tree' => $this]);
    }

    function count()
    {
        return count($this->blocks);
    }

    function current()
    {
        return $this->blocks[$this->index];
    }

    function key()
    {
        return $this->index;
    }

    function next()
    {
        ++$this->index;
    }

    function rewind ()
    {
        $this->index = 0;
    }

    function valid()
    {
        return isset($this->blocks[$this->index]);
    }

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->blocks[] = $value;
        } else {
            $this->blocks[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->blocks[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->blocks[$offset]);
    }

    public function offsetGet($offset) {
        return isset($this->blocks[$offset]) ? $this->blocks[$offset] : null;
    }

}
