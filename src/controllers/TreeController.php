<?php

namespace markhuot\igloo\controllers;

use craft\web\Controller;

class TreeController extends Controller {

    function actionAddLayer($tree)
    {
        return $this->renderTemplate('igloo/add-layer', [
            'tree' => $tree,
            'blocks' => [
                new \markhuot\igloo\models\Blockquote,
                new \markhuot\igloo\models\Box,
                new \markhuot\igloo\models\Text,
            ]
        ]);
    }

    function actionStoreNewLayer($tree)
    {
        $type = \Craft::$app->request->getParam('block.type');

        $tree = (new \markhuot\igloo\services\Blocks)->getTree($tree);
        $tree->append(new $type);
        $tree = (new \markhuot\igloo\services\Blocks)->saveTree($tree);

        return $this->asJson([
            'components' => [
                '' => '',
            ],
            'action' => 'closePanel',
        ]);
    }

}