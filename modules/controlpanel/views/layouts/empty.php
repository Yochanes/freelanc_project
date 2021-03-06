<?php

use yii\helpers\Html;
use app\modules\controlpanel\assets\AdminAsset;

AdminAsset::register($this);



?>
<?php $this->beginPage() ?>
  <!DOCTYPE html>
  <html lang="<?= Yii::$app->language ?>">
  <head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
  </head>
  <body>
  <?php $this->beginBody() ?>
  <div class="wrap">
    <?= $content ?>
  </div>
  <?php $this->endBody() ?>
  </body>
  </html>
<?php $this->endPage() ?>