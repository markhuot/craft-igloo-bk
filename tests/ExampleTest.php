<?php

use function Spatie\Snapshots\assertMatchesSnapshot;

it('prepares block for save', function () {
    $block = new \markhuot\igloo\models\Text();
    $block->id = 1;
    $block->content = 'foo bar baz';
    assertMatchesSnapshot($block->prepareSave());
});

it('prepares block children for save', function () {
    $box = new \markhuot\igloo\models\Box(['id' => 1]);
    $box->append(new \markhuot\igloo\models\Text('foo', ['id' => 2]));
    $box->append(new \markhuot\igloo\models\Text('bar', ['id' => 3]));
    assertMatchesSnapshot($box->prepareSave());
});

it('flattens tree', function () {
    $box = new \markhuot\igloo\models\Box(['id' => 1]);
    $box->append(new \markhuot\igloo\models\Text('foo', ['id' => 2]));
    $box->append(new \markhuot\igloo\models\Text('bar', ['id' => 3]));
    $records = (new \markhuot\igloo\services\Blocks())->getRecordsFromBlock($box);
    assertMatchesSnapshot($records);
});

it('flattens deep tree', function () {
    $box = new \markhuot\igloo\models\Box(['id' => 1]);
    $box->append(new \markhuot\igloo\models\Text('foo', ['id' => 2]));
    $box->append((new \markhuot\igloo\models\Box(['id' => 4]))
        ->append(new \markhuot\igloo\models\Text('baz', ['id' => 5]))
        ->append(new \markhuot\igloo\models\Text('qux', ['id' => 6]))
        ->append(new \markhuot\igloo\models\Text('qid', ['id' => 7]))
    );
    $box->append(new \markhuot\igloo\models\Text('bar', ['id' => 3]));
    $records = (new \markhuot\igloo\services\Blocks())->getRecordsFromBlock($box);
    assertMatchesSnapshot($records);
});
