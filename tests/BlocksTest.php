<?php

use craft\helpers\StringHelper;
use function Spatie\Snapshots\assertMatchesSnapshot;

it('prepares block for save', function () {
    $block = new \markhuot\igloo\models\Text('foo bar baz');
    assertMatchesSnapshot($block->serialize());
});

it('prepares styled block for save', function () {
    $block = new \markhuot\igloo\models\Text('foo bar', ['styles' => ['color' => 'red']]);
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
        ['lft' => 0, 'rgt' => 9],
        ['lft' => 1, 'rgt' => 2],
        ['lft' => 3, 'rgt' => 6],
        ['lft' => 4, 'rgt' => 5],
        ['lft' => 7, 'rgt' => 8],
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

it('saves a simple tree', function () {
    $box = new \markhuot\igloo\models\Box();
    $box->append(new \markhuot\igloo\models\Text('foo'));
    $box->append(new \markhuot\igloo\models\Text('bar'));
    $records = (new \markhuot\igloo\services\Blocks())->getRecordsFromBlock($box);
    $tree = uniqid();
    (new \markhuot\igloo\services\Blocks())->saveRecords($records, $tree);
    $result = (new \craft\db\Query)
        ->from(['b' => '{{%igloo_blocks}}'])
        ->innerJoin('{{%igloo_block_structure}} s', 's.id=b.id')
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
})->skip();