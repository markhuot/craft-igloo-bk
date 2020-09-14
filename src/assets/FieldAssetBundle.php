<?php

namespace markhuot\igloo\assets;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class FieldAssetBundle extends AssetBundle {

    public function init()
    {
        // define the path that your publishable resources live
        $this->sourcePath = '@markhuot/igloo/assets';

        // define the dependencies
        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'scripts.js',
        ];

        $this->css = [
        ];

        parent::init();
    }

}