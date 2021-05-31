<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\imagine\Image;
use app\models\Images;

class ImageController extends Controller
{

  private static $DEBUG = false;
  public static $quality = 90;

  public function actionGet($url = '')
  {
    return Yii::$app->response->sendFile(Yii::$app->basePath . $this->getImagePath($url));
  }

  public function actionLink($url)
  {
    return $this->getImagePath($url);
  }

  public function actionUrl()
  {
    $source = Yii::$app->request->get('source');
    $tmppath = Yii::$app->request->get('tmppath');
    if (!is_file(Yii::$app->basePath . $source)) $source = '';
    if (!$tmppath) $tmppath = 'images';
    return Yii::$app->response->sendFile(Yii::$app->basePath . $this->getImagePathByUrl($source, $tmppath));
  }

  public function actionVurl()
  {
    if (!Yii::$app->request->get('w')) {
      return Yii::$app->runAction('site/image', ['url' => 'products']);
    }

    $url = basename(Yii::$app->request->pathInfo);
    self::writeLog('-----------');
    self::writeLog('url=' . $url);

    $exp = explode('.' , $url);
    $ext = '.' . end($exp);

    $filename = $exp[0];
    $source = '/web/gallery/products/' . $url;
    self::writeLog('source=' . $source);

    $width = Yii::$app->request->get('w');
    $height = Yii::$app->request->get('h');

    if (!is_file(Yii::$app->basePath . $source)) {
      $source = '/web/gallery/img/noimg.png';

      if ($width && $height) {
        $tmppath = '/web/gallery/img/noimg_' . $width . 'x' . $height . '.png';

        if (!is_file(Yii::$app->basePath . $tmppath)) {
          self::saveImage($source, $tmppath, $width, $height);
        }

        return Yii::$app->response->sendFile(Yii::$app->basePath . $tmppath);
      } else {
        return Yii::$app->response->sendFile(Yii::$app->basePath . $source);
      }
    } else {
      if ($width && $height) {
        $tmppath = '/web/gallery/tmp/products/' . $filename . '_' . $width . 'x' . $height . $ext;

        if (!is_file(Yii::$app->basePath . $tmppath)) {
          self::saveImage($source, $tmppath, $width, $height);
        }

        return Yii::$app->response->sendFile(Yii::$app->basePath . $tmppath);
      } else {
        return Yii::$app->response->sendFile(Yii::$app->basePath . $source);
      }
    }
  }

  private function getImagePathByUrl($url = '', $tmpdir)
  {
    $url = !empty($url) ? $url : '/web/gallery/img/noimg.png';
    $img = $url;

    $width = (int)Yii::$app->request->get('width');
    $height = (int)Yii::$app->request->get('height');

    if ($tmpdir && $width && $height) {
      $path = $url;
      $split = explode('.', basename($path));

      if (!file_exists(Yii::$app->basePath . '/web/gallery/tmp/' . $tmpdir)) {
        mkdir(Yii::$app->basePath . '/web/gallery/tmp/' . $tmpdir, 0755);
      }

      $tmppath = '/web/gallery/tmp/' . $tmpdir . '/' . $split[0] . '_' . $width . 'x' . $height . '.' . $split[1];

      self::writeLog('saving image to ' . $tmppath);
      self::saveImage($path, $tmppath, $width, $height);
      $img = $tmppath;
    }

    return $img;
  }

  private function getImagePath($url)
  {
    $id = $url;
    $img = '/web/gallery/img/noimg.png';

    if (!empty($id)) {
      $img = Images::findOne(['id' => $id]);
      $img = $img ? $img->url : '/web/gallery/img/noimg.png';
    }

    $id = !empty($id) ? $id : 'noimg';

    $width = Yii::$app->request->get('width');
    $height = Yii::$app->request->get('height');

    if ($width && $height) {
      $path = $img;
      $split = explode('.', basename($path));

      if (!file_exists(Yii::$app->basePath . '/web/gallery/tmp/' . $id)) {
        mkdir(Yii::$app->basePath . '/web/gallery/tmp/' . $id, 0755);
      }

      $split[0] = explode('/', $split[0]);
      $tmppath = '/web/gallery/tmp/' . $id . '/' . end($split[0]) . '_' . $width . 'x' . $height . '.' . $split[1];

      self::saveImage($path, $tmppath, $width, $height);
      $img = $tmppath;
    }

    return $img;
  }

  private static function saveImage($path, $tmppath, $width, $height)
  {
    self::writeLog('path=' . $path);
    self::writeLog('tmppath=' . $tmppath);

    if (!file_exists(Yii::$app->basePath . $tmppath)) {
      self::writeLog('saving to temporary=' . Yii::$app->basePath . $tmppath);

      try {
        Image::resize(Yii::$app->basePath . $path, $width, $height)
          ->save(Yii::$app->basePath . $tmppath, ['quality' => self::$quality]);
      } catch (\Exception $ex) {
        self::writeLog('saving error:' . PHP_EOL . $ex->getMessage());
        self::writeLog('saving error trace:' . PHP_EOL . $ex->getTraceAsString());
      }
    }
  }

  private static function writeLog($str) {
    if (self::$DEBUG) {
      file_put_contents(__DIR__ . '/debug.log', $str . PHP_EOL, FILE_APPEND);
    }
  }
}
