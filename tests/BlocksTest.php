<?php

use craft\helpers\StringHelper;
use function Spatie\Snapshots\assertMatchesSnapshot;

it('prepares block for save', function () {
    $block = new \markhuot\igloo\models\Text('foo bar baz');
    assertMatchesSnapshot($block->serialize());
});

it('supports css classlist', function () {
    $block = new \markhuot\igloo\models\Text();
    $block->attributes->classlist->add('foo');
    expect($block->attributes->classlist)->toContain('foo');
    expect($block->attributes->classlist->contains('foo'))->toBeTrue();
    $block->attributes->classlist->remove('foo');
    expect($block->attributes->classlist)->not->toContain('foo');
    expect($block->attributes->classlist->contains('foo'))->toBeFalse();
    $block->attributes->classlist->toggle('foo');
    expect($block->attributes->classlist)->toContain('foo');
    $block->attributes->classlist->toggle('foo');
    expect($block->attributes->classlist)->not->toContain('foo');
    $block->attributes->classlist->add('foo');
    $block->attributes->classlist->replace('foo', 'bar');
    expect($block->attributes->classlist)->not->toContain('foo');
    expect($block->attributes->classlist)->toContain('bar');
    expect($block->attributes->className)->toBe('bar');
});

it('prepares styled block for save', function () {
    $block = new \markhuot\igloo\models\Text('foo bar', ['attributes' => ['style' => ['color' => 'red']]]);
    assertMatchesSnapshot($block->serialize());
});

it('prepares block children for save', function () {
    $box = new \markhuot\igloo\models\Box();
    $box->append(new \markhuot\igloo\models\Text('foo'));
    $box->append(new \markhuot\igloo\models\Text('bar'));
    assertMatchesSnapshot($box->flatten()->serialize());
});

it('flattens tree', function () {
    $box = new \markhuot\igloo\models\Box();
    $box->append(new \markhuot\igloo\models\Text('foo'));
    $box->append(new \markhuot\igloo\models\Text('bar'));
    assertMatchesSnapshot($box->flatten()->serialize());
});

it('flattens a tree with named children', function () {
    $blockquote = new \markhuot\igloo\models\Blockquote();
    $blockquote->content->append(new \markhuot\igloo\models\Text('foo'));
    $blockquote->author->append(new \markhuot\igloo\models\Text('bar'));
    $records = $blockquote->flatten()->serialize();
    assertMatchesSnapshot($records);
});

it('flattens deep tree', function () {
    $box = new \markhuot\igloo\models\Box();
    $box->append(new \markhuot\igloo\models\Text('foo'));
    $box->append((new \markhuot\igloo\models\Box())
        ->append(new \markhuot\igloo\models\Text('baz'))
        ->append(new \markhuot\igloo\models\Text('qux'))
        ->append(new \markhuot\igloo\models\Text('qid'))
    );
    $box->append(new \markhuot\igloo\models\Text('bar'));
    $records = $box->flatten()->serialize();
    assertMatchesSnapshot($records);
});

it('creates a tree', function () {
    $records = [
        ['{{%igloo_block_structure}}' => ['lft' => 0, 'rgt' => 9]],
        ['{{%igloo_block_structure}}' => ['lft' => 1, 'rgt' => 2]],
        ['{{%igloo_block_structure}}' => ['lft' => 3, 'rgt' => 6]],
        ['{{%igloo_block_structure}}' => ['lft' => 4, 'rgt' => 5]],
        ['{{%igloo_block_structure}}' => ['lft' => 7, 'rgt' => 8]],
    ];
    $tree = (new \markhuot\igloo\services\Blocks())->makeTree($records);
    assertMatchesSnapshot($records);
});

it('hydrates a record', function () {
    $record = [
        '{{%igloo_blocks}}' => [
            'type' => \markhuot\igloo\models\Text::class,
        ],
        '{{%igloo_content_text}}' => [
            'content' => 'foo bar baz'
        ],
    ];
    $block = (new \markhuot\igloo\services\Blocks())->hydrate($record);
    assertMatchesSnapshot($block);
});

it('hydrates traits', function () {
    $record = [
        '{{%igloo_blocks}}' => [
            'type' => \markhuot\igloo\models\Text::class,
        ],
        '{{%igloo_content_text}}' => [
            'content' => 'foo bar baz',
        ],
        '{{%igloo_block_attributes}}' => [
            'data' => '{"style":{"color":"red"}}',
        ],
    ];
    $block = (new \markhuot\igloo\services\Blocks())->hydrate($record);
    assertMatchesSnapshot($block);
});

it('hydrates record children', function () {
    $blockquote = new \markhuot\igloo\models\Blockquote;
    $blockquote->content->append(new \markhuot\igloo\models\Text('To be or not to be...'));
    $blockquote->author->append(new \markhuot\igloo\models\Text('Some Guy'));

    $box = new \markhuot\igloo\models\Box;
    $box->append(new \markhuot\igloo\models\Text);
    $box->append($blockquote);
    $box->append(new \markhuot\igloo\models\Text);
    $block = (new \markhuot\igloo\services\Blocks())->hydrateRecords($box->flatten()->serialize());
    expect($box->flatten()->serialize())->toEqual($block->flatten()->serialize());
});

it('saves a single record', function () {
    $box = new \markhuot\igloo\models\Text('foo bar');
    $records = $box->flatten()->serialize();
    $tree = uniqid();
    $records = (new \markhuot\igloo\services\Blocks())->saveRecords($records, $tree);
    $result = (new \craft\db\Query)
        ->from(['b' => '{{%igloo_blocks}}'])
        ->innerJoin('{{%igloo_block_structure}} s', 's.id=b.id')
        ->leftJoin('{{%igloo_content_text}} t', 't.id=b.id')
        ->where(['s.tree' => $tree])
        ->all();
    $result = collect($result)
        ->map(function ($row) {
            unset($row['id']);
            unset($row['uid']);
            unset($row['dateCreated']);
            unset($row['dateUpdated']);
            unset($row['tree']);
            return $row;
        })
        ->toArray();
    assertMatchesSnapshot($result);
});

it('saves a simple tree', function () {
    $box = new \markhuot\igloo\models\Box();
    $box->append(new \markhuot\igloo\models\Text('foo'));
    $box->append(new \markhuot\igloo\models\Text('bar'));
    $records = $box->flatten()->serialize();
    $tree = uniqid();
    (new \markhuot\igloo\services\Blocks())->saveRecords($records, $tree);
    $result = (new \craft\db\Query)
        ->from(['b' => '{{%igloo_blocks}}'])
        ->innerJoin('{{%igloo_block_structure}} s', 's.id=b.id')
        ->leftJoin('{{%igloo_content_text}} t', 't.id=b.id')
        ->where(['s.tree' => $tree])
        ->all();
    $result = collect($result)
        ->map(function ($row) {
            unset($row['id']);
            unset($row['uid']);
            unset($row['dateCreated']);
            unset($row['dateUpdated']);
            unset($row['tree']);
            return $row;
        })
        ->toArray();
        assertMatchesSnapshot($result);
});

it('resaves an existing block', function () {
    $text = new \markhuot\igloo\models\Text('foo');
    (new \markhuot\igloo\services\Blocks())->saveBlock($text);
    expect($text->id)->not->toBeEmpty();
    
    $text = (new \markhuot\igloo\services\Blocks())->getBlock($text->id);
    expect($text->content)->toBe('foo');
    
    $text->content = 'foo bar';
    (new \markhuot\igloo\services\Blocks())->saveBlock($text);
    expect($text->id)->not->toBeEmpty();
    
    $text = (new \markhuot\igloo\services\Blocks())->getBlock($text->id);
    expect($text->content)->toBe('foo bar');
});

it('resaves an existing block without affecting nested set', function () {
    $box = new \markhuot\igloo\models\Box();
    $box->append(new \markhuot\igloo\models\Box());
    $box->append($child = new \markhuot\igloo\models\Box());
    $box->append(new \markhuot\igloo\models\Box());
    (new \markhuot\igloo\services\Blocks())->saveBlock($box);

    expect($child->id)->not->toBeEmpty();
    expect($previousLft = $child->lft)->not->toBeEmpty();
    
    (new \markhuot\igloo\services\Blocks())->saveBlock($child);
    expect($child->lft)->toBe($previousLft);
    
});

it('retrieves a tree', function () {
    $tree = uniqid();
    $box = new \markhuot\igloo\models\Box();
    $box->append(new \markhuot\igloo\models\Text());
    $box->append(new \markhuot\igloo\models\Text());
    (new \markhuot\igloo\services\Blocks())->saveBlock($box, $tree);
    $tree = (new \markhuot\igloo\services\Blocks())->getTree($tree)->first();
    expect($box->flatten()->serialize())->toEqual($tree->flatten()->serialize());
});

it('retrieves a block', function () {
    $box = new \markhuot\igloo\models\Box();
    $box->append(new \markhuot\igloo\models\Text('foo bar'));
    (new \markhuot\igloo\services\Blocks())->saveBlock($box);
    expect($box->id)->not->toBeEmpty();
    $fetchedBox = (new \markhuot\igloo\services\Blocks())->getBlock($box->id);
    expect($fetchedBox->flatten()->serialize())->toEqual($box->flatten()->serialize());
});

it('fills data', function () {
    $text = new \markhuot\igloo\models\Text('foo');
    $text->fill(['content' => 'foo bar']);
    assertMatchesSnapshot($text);
});

it('saves styles', function () {
    $text = new \markhuot\igloo\models\Text('foo');
    $text->attributes->style->fontSize = '28px';
    (new \markhuot\igloo\services\Blocks())->saveBlock($text);

    $text = (new \markhuot\igloo\services\Blocks())->getBlock($text->id);
    expect($text->attributes->style->fontSize)->toBe('28px');
});

it('appends a block to a tree', function () {
    $tree = new \markhuot\igloo\base\BlockCollection;
    $tree->append(new \markhuot\igloo\models\Text('foo'));
    $tree->append(new \markhuot\igloo\models\Text('bar'));
    expect($tree[0]->content)->toBe('foo');
    expect($tree[0]->lft)->toBe(0);
    expect($tree[0]->rgt)->toBe(1);
    expect($tree[1]->content)->toBe('bar');
    expect($tree[1]->lft)->toBe(2);
    expect($tree[1]->rgt)->toBe(3);
    assertMatchesSnapshot($tree->anonymize()->flatten()->serialize());
});

it('prepends a block to a tree', function () {
    $tree = new \markhuot\igloo\base\BlockCollection;
    $tree->prepend(new \markhuot\igloo\models\Text('bar'));
    $tree->prepend(new \markhuot\igloo\models\Text('foo'));
    expect($tree[0]->content)->toBe('foo');
    expect($tree[0]->lft)->toBe(0);
    expect($tree[0]->rgt)->toBe(1);
    expect($tree[1]->content)->toBe('bar');
    expect($tree[1]->lft)->toBe(2);
    expect($tree[1]->rgt)->toBe(3);
    assertMatchesSnapshot($tree->anonymize()->flatten()->serialize());
});

it('inserts a block to a specific place', function () {
    $tree = new \markhuot\igloo\base\BlockCollection;
    $tree->append(new \markhuot\igloo\models\Text('foo'));
    $tree->append(new \markhuot\igloo\models\Text('baz'));
    $tree->insertAtIndex(new \markhuot\igloo\models\Text('bar'), 1);
    expect($tree[1]->content)->toBe('bar');
    assertMatchesSnapshot($tree->anonymize()->flatten()->serialize());
});

it('inserts a deeply nested block', function () {
    $greatGrandParent = new \markhuot\igloo\models\Text('greatGrandParent');
    $grandParent = new \markhuot\igloo\models\Text('grandParent');
    $parent = new \markhuot\igloo\models\Text('parent');
    $child = new \markhuot\igloo\models\Text('child');
    $grandChild = new \markhuot\igloo\models\Text('grandChild');
    $greatGrandParent->children->append(
        $grandParent->children->append(
            $parent->children->append(
                $child->children->append($grandChild)->block
            )->block
        )->block
    );
    $tree = new \markhuot\igloo\base\BlockCollection;
    $tree->append($greatGrandParent);
    $secondGrandParent = new \markhuot\igloo\models\Text('secondGreatGrandParent');
    $tree->append($secondGrandParent);
    $parent->children->append(new \markhuot\igloo\models\Text('second child'));
    expect($secondGrandParent->lft)->toBe(12);
});
