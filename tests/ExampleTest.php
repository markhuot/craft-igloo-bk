<?php

use craft\helpers\StringHelper;
use function Spatie\Snapshots\assertMatchesSnapshot;

it('prepares block for save', function () {
    $block = new \markhuot\igloo\models\Text('foo bar baz');
    assertMatchesSnapshot($block->serialize());
});

it('prepares block children for save', function () {
    $box = new \markhuot\igloo\models\Box();
    $box->append(new \markhuot\igloo\models\Text('foo'));
    $box->append(new \markhuot\igloo\models\Text('bar'));
    assertMatchesSnapshot($box->serialize());
});

it('flattens tree', function () {
    $box = new \markhuot\igloo\models\Box();
    $box->append(new \markhuot\igloo\models\Text('foo'));
    $box->append(new \markhuot\igloo\models\Text('bar'));
    $records = (new \markhuot\igloo\services\Blocks())->getRecordsFromBlock($box);
    assertMatchesSnapshot($records);
});

it('flattens a tree with named children', function () {
    $blockquote = new \markhuot\igloo\models\Blockquote();
    $blockquote->content = new \markhuot\igloo\models\Text('foo');
    $blockquote->author = new \markhuot\igloo\models\Text('bar');
    $records = (new \markhuot\igloo\services\Blocks())->getRecordsFromBlock($blockquote);
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
    $records = (new \markhuot\igloo\services\Blocks())->getRecordsFromBlock($box);
    assertMatchesSnapshot($records);
});

it('creates a tree', function () {
    $records = [
        ['lft' => 0, 'rgt' => 7],
        ['lft' => 1, 'rgt' => 4],
        ['lft' => 2, 'rgt' => 3],
        ['lft' => 5, 'rgt' => 6],
    ];
    $tree = (new \markhuot\igloo\services\Blocks())->makeTree($records);
    assertMatchesSnapshot($records);
});

it('hydrates a record', function () {
    $record = [
        'type' => \markhuot\igloo\models\Text::class,
        'data' => ['content' => 'foo bar baz'],
    ];
    $block = (new \markhuot\igloo\services\Blocks())->hydrate($record);
    assertMatchesSnapshot($block);
});

it('hydrates record children', function () {
    $box = [
        'type' => \markhuot\igloo\models\Box::class,
        'children' => [
            ['type' => \markhuot\igloo\models\Text::class, 'slot' => 'children'],
            [
                'type' => \markhuot\igloo\models\Blockquote::class,
                'slot' => 'children',
                'children' => [
                    ['type' => \markhuot\igloo\models\Text::class, 'slot' => 'content', 'data' => ['content' => 'To be or not to be...']],
                    ['type' => \markhuot\igloo\models\Text::class, 'slot' => 'author', 'data' => ['content' => 'Some Guy']],
                ],
            ],
            ['type' => \markhuot\igloo\models\Text::class, 'slot' => 'children'],
        ],
    ];
    $block = (new \markhuot\igloo\services\Blocks())->hydrate($box);
    assertMatchesSnapshot($block);
});