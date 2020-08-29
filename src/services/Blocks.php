<?php

namespace markhuot\igloo\services;

use markhuot\igloo\base\Block;

class Blocks {

    function saveBlock(Block $block)
    {
        $map = $block->prepareSave();
        $records = $this->getRecordsFromTree([$map]);
        var_dump($records);
        die;
    }

    function getRecordsFromTree(array $tree, $left = 0)
    {
        $records = [];

        foreach ($tree as $node) {
            $index = count($records);
            $records[] = [
                'id' => $node['id'],
                'type' => $node['type'],
                'data' => $node['data'] ?? null,
                'lft' => $left,
            ];

            if (!empty($node['children'])) {
                $childRecords = $this->getRecordsFromTree($node['children'], ++$left);
                $records = array_merge($records, $childRecords);
                $left += count($childRecords) + 1;
            }

            $records[$index]['right'] = ++$left;
            ++$left;
        }

        return $records;
    }

}
