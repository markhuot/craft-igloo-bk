<?php

namespace markhuot\igloo\services;

use craft\db\Query;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use markhuot\igloo\base\Block;
use markhuot\igloo\base\BlockCollection;

class Blocks {

    function saveTree(array $tree)
    {
        $treeId = $tree[0]->tree ?? uniqod();

        $records = $this->getRecordsFromTree(array_map(function ($block) {
            return $block->prepare()->serialize();
        }, $tree), 0);
        $records = $this->saveRecords($records, $treeId);
    }

    function saveBlock(Block $block, $tree=null)
    {
        if (empty($tree)) {
            $tree = $block->tree ?? uniqid();
        }

        $block->prepare();
        $records = $block->flatten()->serialize();
        $records = $this->saveRecords($records, $tree);
        $records = collect($records)->keyBy('{{%igloo_blocks}}.uid')->toArray();
        
        // Add the id to any new blocks so they're tracked against the persistent
        // storage correctly
        $block->walkChildren(function ($block) use ($records) {
            $block->id = $records[$block->uid]['{{%igloo_blocks}}']['id'] ?? null;
            $block->tree = $records[$block->uid]['{{%igloo_block_structure}}']['tree'] ?? null;
            $block->lft = $records[$block->uid]['{{%igloo_block_structure}}']['lft'] ?? null;
            $block->rgt = $records[$block->uid]['{{%igloo_block_structure}}']['rgt'] ?? null;
        });
        
        return $block;
    }

    function getRecordsFromBlock(Block $block)
    {
        $map = $block->serialize();
        return $this->getRecordsFromTree([$map], $block->lft ?? 0);
    }

    function getRecordsFromTree(array $tree, $left)
    {
        $records = [];

        foreach ($tree as $node) {
            $index = count($records);
            $records[] = $node;
            //$records[$index][Block::STRUCTURE_TABLE_NAME]['lft'] = $left;

            if (!empty($node['children'])) {
                $childRecords = $this->getRecordsFromTree($node['children'], $left+1);
                $records = array_merge($records, $childRecords);
                //$left += count($childRecords) * 2;
            }

            //$records[$index][Block::STRUCTURE_TABLE_NAME]['rgt'] = ++$left;
            unset($records[$index]['children']);
            //++$left;
        }

        return $records;
    }

    function saveRecords(array $records, $tree)
    {
        $recordIds = collect($records)->pluck(Block::TABLE_NAME . '.uid')->filter()->toArray();

        $transaction = \Craft::$app->db->beginTransaction();

        $existingRecordIds = (new Query())
            ->select('uid')
            ->from(Block::TABLE_NAME)
            ->where(['uid' => $recordIds])
            ->column();
        $existingRecords = collect($records)
            ->filter(function ($r) use ($existingRecordIds) {
                $uid = $r[Block::TABLE_NAME]['uid'] ?? null;
                return in_array($uid, $existingRecordIds);
            })
            ->toArray();
        foreach ($existingRecords as $record) {
            $id = $record[Block::TABLE_NAME]['id'];
            \Craft::$app->db->createCommand()->update(Block::TABLE_NAME, $record[Block::TABLE_NAME], ['id' => $id])->execute();
            \Craft::$app->db->createCommand()->update(Block::STRUCTURE_TABLE_NAME, $record[Block::STRUCTURE_TABLE_NAME], ['id' => $id])->execute();
            
            foreach ($record as $tableName => $data) {
                if (in_array($tableName, ['{{%igloo_blocks}}', '{{%igloo_block_structure}}'])) {
                    continue;
                }
                
                if (empty($data)) {
                    \Craft::$app->db->createCommand()->delete($tableName, ['id' => $id])->execute();
                }
                else {
                    $data['id'] = $id;
                    \Craft::$app->db->createCommand()->upsert($tableName, $data)->execute();
                }
            }
        }

        $newRecordIds = array_diff($recordIds, $existingRecordIds);
        $newRecords = collect($records)
            ->filter(function ($r) use ($newRecordIds) {
                $uid = $r[Block::TABLE_NAME]['uid'] ?? null;
                return empty($uid) || in_array($uid, $newRecordIds);
            })
            ->toArray();
        
        foreach ($newRecords as &$record) {
            \Craft::$app->db->createCommand()->insert(Block::TABLE_NAME, $record[Block::TABLE_NAME])->execute();
            $blockId = \Craft::$app->db->getLastInsertID();
            
            $record[Block::TABLE_NAME]['id'] = $blockId;
            $record[Block::STRUCTURE_TABLE_NAME]['id'] = $blockId;
            $record[Block::STRUCTURE_TABLE_NAME]['tree'] = $tree;
            \Craft::$app->db->createCommand()->insert(Block::STRUCTURE_TABLE_NAME, $record[Block::STRUCTURE_TABLE_NAME])->execute();
            
            foreach ($record as $tableName => &$data) {
                if (in_array($tableName, ['{{%igloo_blocks}}', '{{%igloo_block_structure}}'])) {
                    continue;
                }

                if (!empty($data)) {
                    $data['id'] = $blockId;
                    \Craft::$app->db->createCommand()->insert($tableName, $data)->execute();
                }
            }
        }

        // @TODO delete dead records
        // $deadRecordIds = array_diff($existingRecordIds, $recordIds);

        $transaction->commit();

        return collect($existingRecords)
            ->concat($newRecords)
            ->sortBy(Block::STRUCTURE_TABLE_NAME . '.lft')
            ->toArray();
    }

    /**
     * Get a block from the persiistent storage by its id or uid
     * 
     * @param int $id
     */
    function getBlock($id)
    {
        if (empty($id)) {
            return null;
        }

        $records = (new Query())
            ->select('s2.*, b.*')
            ->from(['s' => '{{%igloo_block_structure}}'])
            ->where(['s.id' => $id])
            ->innerJoin('{{%igloo_block_structure}} s2', 's2.tree=s.tree and s2.lft>=s.lft and s2.rgt<=s.rgt')
            ->innerJoin('{{%igloo_blocks}} b', 'b.id=s2.id')
            ->orderBy(['s2.lft' => SORT_ASC])
            ->all();

        $records = $this->getTreeContent($records);
        $foo = $this->hydrateRecords($records)->first();
        return $foo;
    }

    function getTree($tree)
    {
        $blockQuery = (new Query())
            ->from('{{%igloo_block_structure}}')
            ->innerJoin('{{%igloo_blocks}}', '{{%igloo_blocks}}.id={{%igloo_block_structure}}.id')
            ->where(['tree' => $tree])
            ->orderBy(['lft' => SORT_ASC]);

        $records = $this->getTreeContent($blockQuery->all());

        if (empty($records)) {
            return [];
        }

        return $this->hydrateRecords($records);
    }

    function getTreeContent($records)
    {
        return collect($records)
            ->map(function ($record) {
                return [
                    Block::TABLE_NAME => [
                        'id' => $record['id'],
                        'dateCreated' => $record['dateCreated'],
                        'dateUpdated' => $record['dateUpdated'],
                        'uid' => $record['uid'],
                        'type' => $record['type'],
                    ],
                    Block::STRUCTURE_TABLE_NAME => [
                        'id' => $record['id'],
                        'dateCreated' => $record['dateCreated'],
                        'dateUpdated' => $record['dateUpdated'],
                        'uid' => $record['uid'],
                        'tree' => $record['tree'],
                        'slot' => $record['slot'],
                        'lft' => $record['lft'],
                        'rgt' => $record['rgt'],
                    ],
                ];
            })
            ->groupBy(Block::TABLE_NAME . '.type')
            ->map(function ($records, $type) {
                $ids = collect($records)->pluck(Block::TABLE_NAME . '.id')->toArray();
                $contentTableName = $type::tableName();
                if (empty($contentTableName)) {
                    return $records;
                }

                $content = (new Query())
                    ->from($contentTableName)
                    ->where(['id' => $ids])
                    ->indexBy('id')
                    ->all();

                if (empty($content)) {
                    return $records;
                }

                return collect($records)
                    ->map(function ($record) use ($content, $contentTableName) {
                        $recordId = $record[Block::TABLE_NAME]['id'];

                        if (!empty($content[$recordId])) {
                            $record[$contentTableName] = $content[$recordId];
                        }

                        return $record;
                    })
                    ->toArray();
            })
            ->flatten(1)
            ->sortBy(Block::STRUCTURE_TABLE_NAME . '.lft')
            
            // @TODO refactor this to the Styleable trait
            ->pipe(function ($records) {
                $styles = (new Query)
                    ->from('{{%igloo_block_attributes}}')
                    ->where(['id' => $records->pluck(Block::TABLE_NAME . '.id')->toArray()])
                    ->indexBy('id')
                    ->all();

                return $records->map(function ($record) use ($styles) {
                    $recordId = $record[Block::TABLE_NAME]['id'];

                    if (!empty($styles[$recordId])){
                        $record['{{%igloo_block_attributes}}'] = $styles[$recordId];
                    }

                    return $record;
                });
            })
            // ->map(function ($record) {
            //     $meta = [
            //         'id' => $record['id'],
            //         'dateCreated' => $record['dateCreated'],
            //         'dateUpdated' => $record['dateUpdated'],
            //         'uid' => $record['uid'],
            //         'tree' => $record['tree'],
            //         'slot' => $record['slot'],
            //         'lft' => $record['lft'],
            //         'rgt' => $record['rgt'],
            //         'type' => $record['type'],
            //     ];
            //     $data = array_diff_key($record, $meta);
            //     if (!empty($data)) {
            //         $meta['data'] = $data;
            //     }
            //     return $meta;
            // })
            ->toArray();
        //dd($records);
    }

    function makeTree($records)
    {
        if (empty($records)) {
            return null;
        }

        $tree = [];
        $record = array_shift($records);
        $recordIndex = count($tree);
        $tree[] = $record;

        foreach ($records as $nextIndex => $next) {
            if ((int)$next[Block::STRUCTURE_TABLE_NAME]['lft'] === $record[Block::STRUCTURE_TABLE_NAME]['lft'] + 1) {
                // is child
                $tree[$recordIndex]['children'] = $this->makeTree(array_slice($records, $nextIndex));
            }
            if ((int)$next[Block::STRUCTURE_TABLE_NAME]['lft'] === $record[Block::STRUCTURE_TABLE_NAME]['rgt'] + 1) {
                // is sibling
                $recordIndex = count($tree);
                $tree[] = $next;
                $record = $next;
            }
        }

        return $tree;
    }

    function appendToTree($block)
    {
        // @TODO
    }

    function hydrate($record)
    {
        if (empty($record)) {
            return null;
        }

        $recordType = $record['{{%igloo_blocks}}']['type'];
        $model = new $recordType;
        $model->unserialize($record);
        //$model = $recordType::unserialize($record['data'] ?? []);
        // @TODO add ->parent in to each child so you can look back up the tree
        return $model;
    }

    function hydrateRecords($records)
    {
        $collection = new BlockCollection;

        $record = array_shift($records);
        $block = $this->hydrate($record);
        $collection->append($block);

        while (count($records)) {
            $next = array_shift($records);
            
            // next is child
            if ((int)$next[Block::STRUCTURE_TABLE_NAME]['lft'] === (int)$record[Block::STRUCTURE_TABLE_NAME]['lft'] + 1) {
                $block->children->concat($this->hydrateRecords(array_merge([$next], $records)));
            }
            
            // next is sibling
            if ((int)$next[Block::STRUCTURE_TABLE_NAME]['lft'] === (int)$record[Block::STRUCTURE_TABLE_NAME]['rgt'] + 1) {
                $block = $this->hydrate($next);
                $record = $next;
                $collection->append($block);
            }
        }

        return $collection;
    }

}
