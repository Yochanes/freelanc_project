<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;

use app\models\products\ProductGroups;
use app\models\makes\MakeGroups;
use app\models\site\Pages;

class SitemapController extends Controller
{

  public function actionIndex()
  {
    $hostname = Yii::$app->request->hostInfo;
    $this->layout = false;
    Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
    Yii::$app->response->headers->add('Content-Type', 'text/xml');

    return $this->render('xml/siteindex', array(
      'prefix' => '',
      'host' => $hostname,
      'pages' => array(
        ['url' => 'main'],
        ['url' => 'products'],
        ['url' => 'makes'],
        ['url' => 'info'],
        ['url' => 'info_products'],
      )
    ));
  }

  public function actionXml($url, $id = '')
  {
    $host = Yii::$app->request->hostInfo;
    $this->layout = false;
    Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
    Yii::$app->response->headers->add('Content-Type', 'text/xml');

    $view_file = 'xml/sitemap';
    $pages = [];
    $prefix = '';

    if ($url == 'main') {
      $pages = Pages::find()
        ->select('url')
        ->where('informational=' . 0 . ' AND relative=' . 0 . ' AND meta_robots LIKE "index%"')
        ->all();
    } else if ($url == 'info') {
      $pages = Pages::find()
        ->select('url')
        ->where('informational=' . 1 .
          ' AND relative=' . 0 .
          ' AND meta_robots LIKE "index%"')
        ->all();

      $prefix = '/site/page/';
    } else if ($url == 'info_products') {
      $pages = Pages::find()
        ->select('url')
        ->where('informational=' . 0 .
          ' AND relative=' . 1 .
          ' AND meta_robots LIKE "index%"' .
          ' AND url LIKE "kak_kupit_%"')
        ->all();

      $prefix = '/site/page';
    } else if ($url == 'makes') {
      if (!$id) {
        $pages = Pages::find()
          ->select( 'url')
          ->where('informational=' . 0 . ' AND relative=' . 1 . ' AND url LIKE "makes/%"' . ' AND meta_robots LIKE "index%"')
          ->all();

        $view_file = 'xml/siteindex';
        $prefix = '/sitemap/xml/';
      } else {
        $group = MakeGroups::find()
          ->where(['make_group_id' => $id])
          ->with('makes')
          ->one();

        $vals = '';

        if ($group) {
          foreach ($group->makes as $make) {
            $vals .= ',"' . $make->url . '"';
          }
        }

        if ($vals) {
          $pages = Pages::find()
            ->select('url')
            ->where('url IN (' . substr($vals, 1) . ')')
            ->all();
        }

        $prefix = '/site/models';
      }
    } else if ($url == 'products') {
      $pages = Pages::find()
        ->select('url')
        ->where('informational=' . 0 .
          ' AND relative=' . 1 .
          ' AND meta_robots LIKE "index%"' .
          ' AND url LIKE "category_%"')
        ->all();

      $prefix = '/products';
    }

    return $this->render($view_file, array(
      'prefix' => $prefix,
      'host' => $host,
      'pages' => $pages
    ));
  }
}
