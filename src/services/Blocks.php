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
        $map = $block->prepareSave();
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

        // foreach ($records as $record) {
        //     $class = $record['type'];
        //     $record = $class::unserialize($record);
        // }
    }

    function nestRecordsInTree(array $records, $lft=null, $rgt=null) {
        foreach ($records as $record) {
            if ($record['lft'] > $lft && $record['lft'] < $rgt) {

            }
        }
    }

}
