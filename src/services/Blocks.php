<?php

namespace markhuot\igloo\services;

use craft\db\Query;
use craft\helpers\Db;
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
        $recordIds = collect($records)->pluck('uid')->toArray();

        $transaction = \Craft::$app->db->beginTransaction();

        $existingRecordIds = (new Query())
            ->select('uid')
            ->from('{{%igloo_blocks}}')
            ->where(['uid' => $recordIds])
            ->column();
        // @TODO update records

        $newRecordIds = array_diff($recordIds, $existingRecordIds);
        $newRecords = collect($records)
            ->filter(function ($r) use ($newRecordIds) { return in_array($r['uid'], $newRecordIds); })
            ->map(function ($r) use ($tree) {
                unset($r['data']);
                $r['tree'] = $tree;
                $r['dateCreated'] = Db::prepareDateForDb(new \DateTime());
                $r['dateUpdated'] = Db::prepareDateForDb(new \DateTime());
                return $r;
            })
            ->toArray();
        Db::batchInsert('{{%igloo_blocks}}', array_keys($newRecords[0]), $newRecords, false);

        $deadRecordIds = array_diff($existingRecordIds, $recordIds);
        // @TODO delete dead records

        $transaction->commit();
    }

    function getTree($tree)
    {
        $records = (new Query())
            ->from('{{%igloo_blocks}}')
            ->where(['tree' => $tree])
            ->orderBy(['lft' => SORT_ASC])
            ->all();

        $tree = $this->makeTree($records);
        $this->hydrate($tree);
    }

    function makeTree($records)
    {
        $tree = [];
        $record = array_shift($records);
        $recordIndex = count($tree);
        $tree[] = $record;

        foreach ($records as $nextIndex => $next) {
            if ($next['lft'] === $record['lft'] + 1) {
                // is child
                $tree[$recordIndex]['children'] = $this->makeTree(array_slice($records, $nextIndex));
            }
            if ($next['lft'] === $record['rgt'] + 1) {
                // is sibling
                $tree[] = $next;
                $record = $next;
            }
        }

        return $tree;
    }

    function hydrate($record)
    {
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
