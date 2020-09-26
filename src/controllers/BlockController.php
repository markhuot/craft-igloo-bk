<?php

namespace markhuot\igloo\controllers;

use craft\web\Controller;

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
                "[data-block-layer][data-block-id=\"{$block->id}\"]" => $this->getView()->renderPageTemplate('igloo/components/layer', ['block' => $block]),
            ]
        ]);
    }

    function actionDelete($id)
    {
        $block = (new \markhuot\igloo\services\Blocks)->getBlock($id);
        (new \markhuot\igloo\services\Blocks)->delete($block);

        return $this->asJson([]);
    }

    function actionStyles($id)
    {
        $block = (new \markhuot\igloo\services\Blocks())->getBlock($id);
        
        return $this->renderTemplate('igloo/styles', [
            'block' => $block,
        ]);
    }
        
    function actionStore($id)
    {
        $props = \Craft::$app->request->post();
        unset($props['CRAFT_CSRF_TOKEN']);
        
        $block = (new \markhuot\igloo\services\Blocks())->getBlock($id);
        $block->attributes->setAll($props);
        
        (new \markhuot\igloo\services\Blocks())->saveBlock($block);

        $resp = $this->asJson([
            'components' => [
                "[data-block-input][data-block-id=\"{$block->id}\"]" => $this->getView()->renderPageTemplate('igloo/blocks/text', ['block' => $block]),
            ]
        ]);

        return $resp;
    }

}