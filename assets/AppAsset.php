<?php

namespace app\assets;

use yii\web\AssetBundle;

/**
 * Main application asset bundle.
 */
class AppAsset extends AssetBundle
{
  public $basePath = '@webroot';
  public $baseUrl = '@web';
  public $css = [
    '/css/common.css',
    '/css/media/1215.css',
    '/css/media/900.css',
    '/css/media/768.css',
    '/css/media/675.css',
    '/css/media/510.css',
    '/css/media/411.css'
  ];
  public $js = [];
  public $depends = [
    'yii\web\YiiAsset',
    //'yii\bootstrap\BootstrapAsset',
  ];
}
