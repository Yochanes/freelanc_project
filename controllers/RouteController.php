<?php

namespace app\controllers;

use Yii;
use app\models\site\URLs;
use yii\web\Controller;


class RouteController extends Controller
{

  public function actionIndex($url)
  {

    if (!$url) {
      throw new \yii\web\HttpException('Страница не найдена');
    }

    $base = explode('?', basename($url));
    $base = $base[0];
    $exp = explode('.', $base);

    if ($exp && count($exp) > 1) {
      $ext = end($exp);

      if (in_array($ext, ['jpg', 'png', 'gif', 'jpeg'])) {
        return Yii::$app->runAction('image/vurl', [
          'url' => $base,
        ]);
      }
    }

    $parts = explode('/', $url);

    $route = URLs::find()
      ->where('url="' . $parts[0] . '"')
      ->one();

    if (!$route) {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }

    $arr = ['parameters' => $url, 'params' => array()];
    $arr[$route->parameters] = $route->url;
    $arr['params'][$route->parameters] = $route->url;
    $action = $route->action;
    $url_parts = [];

    if ($route->parameters == 'make') {
      $url_parts = [
        ['param' => 'make', 'action' => 'makes/models'],
        ['param' => 'model', 'action' => 'makes/model'],
        ['param' => 'category', 'action' => 'products/products'],
        ['param' => 'id', 'action' => 'products/product']
      ];
    } else if ($route->parameters == 'group_url') {
      $group = \app\models\products\ProductGroups::find()
        ->where(['url' => $route->url])
        ->one();

      if (!$group) {
        throw new \yii\web\HttpException(404, 'Страница на найдена');
      }

      if (!$group->is_default) {
        $url_parts[] = ['param' => 'group_url', 'action' => 'products/products'];
      }

      $exp = explode('/', $group->product_url_template);

      if (count($parts) >= 2) {
        if (intval(end($parts))) {
          $action = 'products/product';
          $arr['id'] = end($parts);
        } else {
          $rel_route = URLs::find()
            ->where('url="' . $parts[1] . '"')
            ->one();

          if ($rel_route) {
            if ($rel_route->parameters == 'make') {
              $url_parts[] = ['param' => 'make', 'action' => 'products/products'];
            }
          }
        }
      } else {
        $arr['url'] = '/' . $parts[0];
      }

      $arr['group_url'] = $group;
    } else {
      $arr['url'] = '/' . $parts[0];
    }

    foreach ($parts as $n => $part) {
      if (!isset($url_parts[$n])) break;
      $arr[$url_parts[$n]['param']] = $part;
      $arr['params'][$url_parts[$n]['param']] = $part;

      if ($action != 'products/product') {
        $action = $url_parts[$n]['action'];
      }
    }

    return Yii::$app->runAction($action, $arr);
  }
}
