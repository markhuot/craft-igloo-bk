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
        '{{%igloo_block_styles}}' => [
            'styles' => '{"color":"red"}',
        ],
    ];
    $block = (new \markhuot\igloo\services\Blocks())->hydrate($record);
    assertMatchesSnapshot($block);
});

it('hydrates record children', function () {
    $box = [
        '{{%igloo_blocks}}' => [
            'type' => \markhuot\igloo\models\Box::class,
        ],
        'children' => [
            [
                '{{%igloo_blocks}}' => [
                    'type' => \markhuot\igloo\models\Text::class,
                ],
                '{{%igloo_structure}}' => [
                    'slot' => 'children'
                ],
            ],
            [
                '{{%igloo_blocks}}' => [
                    'type' => \markhuot\igloo\models\Blockquote::class,
                ],
                '{{%igloo_structure}}' => [
                    'slot' => 'children',
                ],
                'children' => [
                    [
                        '{{%igloo_blocks}}' => [
                            'type' => \markhuot\igloo\models\Text::class,
                        ],
                        '{{%igloo_structure}}' => [
                            'slot' => 'content'
                        ],
                        '{{%igloo_content_text}}' => [
                            'content' => 'To be or not to be...'
                        ]
                    ],
                    [
                        '{{%igloo_blocks}}' => [
                            'type' => \markhuot\igloo\models\Text::class,
                        ],
                        '{{%igloo_structure}}' => [
                            'slot' => 'author',
                        ],
                        '{{%igloo_content_text}}' => [
                            'content' => 'Some Guy'
                        ]
                    ],
                ],
            ],
            [
                '{{%igloo_blocks}}' => [
                    'type' => \markhuot\igloo\models\Text::class,
                ],
                '{{%igloo_structure}}' => [
                    'slot' => 'children'
                ],
            ],
        ],
    ];
    $box = new \markhuot\igloo\models\Box;
    $box->append(new \markhuot\igloo\models\Text);
    $box->append(new \markhuot\igloo\models\Blockquote([
        'content' => [new \markhuot\igloo\models\Text('To be or not to be...')],
        'author' => [new \markhuot\igloo\models\Text('Some Guy')],
    ]));
    $box->append(new \markhuot\igloo\models\Text);
    //$box = (new \markhuot\igloo\services\Blocks())->getRecordsFromBlock($box);
    //dump((new \markhuot\igloo\services\Blocks())->hydrate($box->serialize()));
    $block = (new \markhuot\igloo\services\Blocks())->hydrate($box->serialize());
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
    $tree = (new \markhuot\igloo\services\Blocks())->getTree($tree)[0];
    assertMatchesSnapshot($tree->anonymize());
});

it('retrieves a block', function () {
    $box = new \markhuot\igloo\models\Box();
    $box->append(new \markhuot\igloo\models\Text('foo bar'));
    (new \markhuot\igloo\services\Blocks())->saveBlock($box);
    expect($box->id)->not->toBeEmpty();
    $fetchedBox = (new \markhuot\igloo\services\Blocks())->getBlock($box->id);
    expect($fetchedBox)->toEqual($box);
});

it('fills data', function () {
    $text = new \markhuot\igloo\models\Text('foo');
    $text->fill(['content' => 'foo bar']);
    assertMatchesSnapshot($text);
});

it('saves styles', function () {
    $text = new \markhuot\igloo\models\Text('foo');
    $text->styles->fontSize = '28px';
    (new \markhuot\igloo\services\Blocks())->saveBlock($text);

    $text = (new \markhuot\igloo\services\Blocks())->getBlock($text->id);
    expect($text->styles->fontSize)->toBe('28px');
});