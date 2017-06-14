<?php

namespace paulosales\extensions\assets;

use yii\web\AssetBundle;

class w2uiAsset extends AssetBundle
{

    public $sourcePath = '@vendor/bower/paulosales-w2ui';

    public $depends = [
        'yii\web\JqueryAsset'
    ];

    public function init()
    {
        $compressed = YII_DEBUG ? '' : '.min';
        $this->js = [
            "w2ui$compressed.js",
            "w2ui-fields$compressed.js",
        ];
        $this->css = [
            "w2ui$compressed.css",
            "w2ui-fields$compressed.css",
        ];
        parent::init();
    }

}