<?php

namespace app\models;

use Yii;
use yii\imagine\Image;
use Imagine\Image\Box;

class Images extends \yii\db\ActiveRecord
{

  public static function tableName()
  {
    return 'images';
  }

  public function getResized($width = false, $height = false)
  {
    if (!$width || !$height) {
      if (file_exists(Yii::$app->basePath . $this->url)) {
        return $this->url;
      } else {
        return '/web/gallery/img/noimg.jpg';
      }
    } else {
      $path = file_exists(Yii::$app->basePath . $this->url) ? $this->url : '/web/gallery/img/noimg.jpg';
      $split = explode('.', $path);
      $tmppath = str_replace('/uploads/', '/tmp/', $split[0]) . '_' . $width . 'x' . $height . '.' . $split[1];

      if (!file_exists(Yii::$app->basePath . $tmppath)) {
        Image::thumbnail(Yii::$app->basePath . $path, $width, $height)
          ->resize(new Box($width, $height))
          ->save(Yii::$app->basePath . $tmppath, ['quality' => 70]);

        return $tmppath;
      } else {
        return $tmppath;
      }
    }
  }
}
