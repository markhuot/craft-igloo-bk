<?php

namespace markhuot\igloo\controllers;

use craft\web\Controller;

class TreeController extends Controller {

    function actionAddLayer($tree)
    {
        return $this->asJson([
            'panel' => \Craft::$app->getView()->renderTemplate('igloo/add-layer', [
                'tree' => $tree,
                'placement' => \Craft::$app->request->getParam('placement'),
                'path' => \Craft::$app->request->getParam('path'),
                'blocks' => [
                    new \markhuot\igloo\models\Blockquote,
                    new \markhuot\igloo\models\Box,
                    new \markhuot\igloo\models\Text,
                ]
            ])
        ]);
    }

    function actionStoreNewLayer($tree)
    {
        $type = \Craft::$app->request->getParam('block.type');

        $tree = (new \markhuot\igloo\services\Blocks)->getTree($tree);
        if ($placement = \Craft::$app->request->getParam('placement')) {
            $path = \Craft::$app->request->getParam('path');
            $target = $tree->getAtPath($path);
            $collection = $target->collection;
            $index = $target->getIndex();
            $collection->insertAtIndex(new $type, $index + ($placement === 'before' ? 0 : 1));
        }
        else {
            $tree->append(new $type);
        }
        $tree = (new \markhuot\igloo\services\Blocks)->saveTree($tree);
        
        return $this->asJson([
            'components' => [
                '[data-tree-id="'.$tree->id.'"]' => \Craft::$app->getView()->namespaceInputs($tree->getInputHtml(), 'fields', false),
                '[data-layers]' => \Craft::$app->getView()->renderTemplate('igloo/components/layers', ['tree' => $tree])
            ],
            'action' => 'back',
        ]);
    }
    
    function actionMoveLayer($tree, $blockId)
    {
        $sourcePath = \Craft::$app->request->getParam('sourcePath');
        $destinationPath = \Craft::$app->request->getParam('destinationPath');

        // return $this->asJson(['sourcePath' => $sourcePath, 'destinationPath' => $destinationPath]);

        $tree = (new \markhuot\igloo\services\Blocks)->getTree($tree);
        $tree->moveBlock($sourcePath, $destinationPath);
        $tree = (new \markhuot\igloo\services\Blocks)->saveTree($tree);
        
        return $this->asJson([
            'sourcePath' => $sourcePath,
            'destinationPath' => $destinationPath,
            'components' => [
                '[data-layers]' => \Craft::$app->getView()->renderTemplate('igloo/components/layers', ['tree' => $tree]),
                '[data-tree-id="'.$tree->id.'"]' => \Craft::$app->getView()->namespaceInputs($tree->getInputHtml(), 'fields', false),
            ],  
        ]);
    }

}