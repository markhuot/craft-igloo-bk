<?php

namespace markhuot\igloo\controllers;

use craft\web\Controller;
use markhuot\igloo\base\Block;

class BlockController extends Controller {

    function actionUpsert()
    {
        $blockId = \Craft::$app->request->getParam('fields.blockId');
        $data = \Craft::$app->request->getParam('fields.block');
        
        $block = (new \markhuot\igloo\services\Blocks())->getBlock($blockId);
        $block->fill($data);
        
        (new \markhuot\igloo\services\Blocks())->saveBlock($block);
        
        return $this->asJson([
            'components' => [
                "[data-block-layer][data-block-id=\"{$block->id}\"]" => $this->getView()->namespaceInputs($this->getView()->renderPageTemplate('igloo/components/layer', ['block' => $block]), 'fields', false),
            ]
        ]);
    }

    function actionActions($id)
    {
        $block = (new \markhuot\igloo\services\Blocks())->getBlock($id);

        return $this->asJson([
            'panel' => \Craft::$app->getView()->renderTemplate('igloo/block-actions', [
                'block' => $block,
                'path' => \Craft::$app->request->getParam('path'),
            ])
        ]);
    }

    function actionDelete($id)
    {
        $block = (new \markhuot\igloo\services\Blocks)->getBlock($id);
        $tree = (new \markhuot\igloo\services\Blocks)->getTree($block->tree);
        $tree->walkChildren(function (Block $child) use ($id) {
            if ($child->id === $id && $child->collection) {
                $index = $child->collection->getIndexOfBlock($child);
                $child->collection->deleteAtIndex($index);
            }
        });
        (new \markhuot\igloo\services\Blocks())->saveTree($tree);

        return $this->asJson([
            'components' => [
                '[data-layers]' => \Craft::$app->getView()->renderTemplate('igloo/components/layers', ['tree' => $tree]),
                '[data-tree-id="'.$tree->id.'"]' => \Craft::$app->getView()->namespaceInputs($tree->getInputHtml(), 'fields', false),
            ],
            'action' => 'back'
        ]);
    }

    function actionStyles($id)
    {
        $block = (new \markhuot\igloo\services\Blocks())->getBlock($id);
        
        return $this->asJson([
            'panel' => \Craft::$app->getView()->renderTemplate('igloo/styles', [
                'block' => $block,
            ])
        ]);
    }
        
    function actionStore($id)
    {
        $props = \Craft::$app->request->post();
        unset($props['CRAFT_CSRF_TOKEN']);
        
        $block = (new \markhuot\igloo\services\Blocks())->getBlock($id);
        $block->attributes->setAll($props);
        
        (new \markhuot\igloo\services\Blocks())->saveBlock($block);
        //$block = (new \markhuot\igloo\services\Blocks)->getBlock($block->id);

        $resp = $this->asJson([
            'components' => [
                "[data-style-panel=\"{$block->id}\"]" => $this->getView()->renderTemplate('igloo/styles', ['block' => $block]),
                "[data-block-input][data-block-id=\"{$block->id}\"]" => $this->getView()->namespaceInputs($block->getInputHtml(), 'fields', false),

            ]
        ]);

        return $resp;
    }

}
