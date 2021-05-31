<?php
namespace app\modules\controlpanel\assets;

use yii\web\AssetBundle;

class AdminAsset extends AssetBundle
{
    public $sourcePath = '@dashboard-assets';

    public $css = [
        'css/main.css',
    ];

    public $js = [];

    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
    ];

    public $publishOptions = [
      'appendTimestamp' => true,
      'forceCopy' => YII_DEBUG
    ];
}
