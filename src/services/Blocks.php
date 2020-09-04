<?php

namespace markhuot\igloo\services;

use craft\db\Query;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use markhuot\igloo\base\Block;

class Blocks {

    function saveBlock(Block $block, $tree=null)
    {
        $records = $this->getRecordsFromBlock($block);
        if (empty($tree)) {
            $tree = uniqid();
        }
        $this->saveRecords($records, $tree);
    }

    function getRecordsFromBlock(Block $block)
    {
        $map = $block->serialize();
        return $this->getRecordsFromTree([$map]);
    }

    function getRecordsFromTree(array $tree, $left = 0)
    {
        $records = [];

        foreach ($tree as $node) {
            $index = count($records);
            $records[] = array_filter([
                'id' => $node['id'] ?? null,
                'uid' => $node['uid'] ?? null,
                'type' => $node['type'],
                'tableName' => $node['tableName'] ?? null,
                'slot' => $node['slot'] ?? null,
                'lft' => $left,
                'rgt' => null,
            ], function ($value) {
                return $value !== null;
            });

            if (!empty($node['data'])) {
                $records[$index]['data'] = $node['data'];
            }

            if (!empty($node['children'])) {
                $childRecords = $this->getRecordsFromTree($node['children'], $left+1);
                $records = array_merge($records, $childRecords);
                $left += count($childRecords) * 2;
            }

            $records[$index]['rgt'] = ++$left;
            ++$left;
        }

        return $records;
    }

    function saveRecords(array $records, $tree)
    {
        $recordIds = collect($records)->pluck('uid')->filter()->toArray();

        $transaction = \Craft::$app->db->beginTransaction();

        $existingRecordIds = (new Query())
            ->select('uid')
            ->from('{{%igloo_blocks}}')
            ->where(['uid' => $recordIds])
            ->column();

        // @TODO update existing records

        $newRecordIds = array_diff($recordIds, $existingRecordIds);
        $newRecords = collect($records)
            ->filter(function ($r) use ($newRecordIds) { return empty($r['uid']) || in_array($r['uid'], $newRecordIds); })
            ->map(function ($r) use ($tree) {
                $id = $r['id'] ?? null;
                $uid = $r['uid'] ?? StringHelper::UUID();

                $records = [
                    'igloo_blocks' => [
                        'id' => $id,
                        'dateCreated' => Db::prepareDateForDb(new \DateTime()),
                        'dateUpdated' => Db::prepareDateForDb(new \DateTime()),
                        'uid' => $uid,
                        'type' => $r['type'],
                    ],
                    'igloo_structure' => [
                        'id' => $id,
                        'dateCreated' => Db::prepareDateForDb(new \DateTime()),
                        'dateUpdated' => Db::prepareDateForDb(new \DateTime()),
                        'uid' => $uid,
                        'tree' => $tree,
                        'slot' => $r['slot'] ?? null,
                        'lft' => $r['lft'] ?? null,
                        'rgt' => $r['rgt'] ?? null,
                    ],
                ];
                
                if (!empty($r['data']) && !empty($r['tableName'])) {
                    $records[$r['tableName']] = array_merge([
                        'id' => $r['id'] ?? null,
                        'dateCreated' => Db::prepareDateForDb(new \DateTime()),
                        'dateUpdated' => Db::prepareDateForDb(new \DateTime()),
                        'uid' => $uid,
                    ], $r['data']);
                }

                return $records;
            })
            ->toArray();
            // var_dump($newRecords);
            // die;
        
        foreach ($newRecords as $record) {
            \Craft::$app->db->createCommand()->insert('{{%igloo_blocks}}', $record['igloo_blocks'])->execute();
            $blockId = \Craft::$app->db->getLastInsertID();
            
            $record['igloo_structure']['id'] = $blockId;
            \Craft::$app->db->createCommand()->insert('{{%igloo_block_structure}}', $record['igloo_structure'])->execute();

            unset($record['igloo_blocks']);
            unset($record['igloo_structure']);
            
            foreach ($record as $tableName => $data) {
                $data['id'] = $blockId;
                \Craft::$app->db->createCommand()->insert($tableName, $data)->execute();
            }
        }

        // @TODO delete dead records
        // $deadRecordIds = array_diff($existingRecordIds, $recordIds);

        $transaction->commit();
    }

    function getTree($tree)
    {
        $blockQuery = (new Query())
            ->from('{{%igloo_block_structure}}')
            ->innerJoin('{{%igloo_blocks}}', '{{%igloo_blocks}}.id={{%igloo_block_structure}}.id')
            ->where(['tree' => $tree])
            ->orderBy(['lft' => SORT_ASC]);

        $records = collect($blockQuery->all())
            ->groupBy('type')
            ->map(function ($records, $type) use ($blockQuery) {
                $ids = collect($records)->pluck('id')->toArray();
                $tableName = $type::tableName();
                if (empty($tableName)) {
                    return $records;
                }

                return $blockQuery
                    ->leftJoin($tableName, "{$tableName}.id={{%igloo_blocks}}.id")
                    ->where(['{{%igloo_blocks}}.id' => $ids])
                    ->all();
            })
            ->flatten(1)
            ->toArray();
        //dd($records);

        $tree = $this->makeTree($records);
        dd($tree);
        
        return array_map(function ($node) {
            return $this->hydrate($node);
        }, $tree);
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
            if ((int)$next['lft'] === $record['lft'] + 1) {
                // is child
                $tree[$recordIndex]['children'] = $this->makeTree(array_slice($records, $nextIndex));
            }
            if ((int)$next['lft'] === $record['rgt'] + 1) {
                // is sibling
                $recordIndex = count($tree);
                $tree[] = $next;
                $record = $next;
            }
        }

        return $tree;
    }

    function hydrate($record)
    {
        if (empty($record)) {
            return null;
        }

        $recordType = $record['type'];
        // if (!empty($record['children'])) {
        //     $record['children'] = array_map(function ($child) {
        //         return $this->hydrate($child);
        //     }, $record['children']);
        //     $record['data']['children'] = $record['children'];
        // }
        // $record['data'] = $record['data'] ?? [];
        $model = new $recordType;
        $model->unserialize($record);
        //$model = $recordType::unserialize($record['data'] ?? []);
        // @TODO add ->parent in to each child so you can look back up the tree
        return $model;
    }

}
