<?php

namespace app\models\helpers;

use app\models\site\Pages;
use Yii;

use app\models\site\Menus;
use cijic\phpMorphy\Morphy;

class PageUtils
{

  private static $morphy;
  private static $registeredPages = [];

  public static function getMenus()
  {
    $menus = Menus::find()
      ->with(['menuItems', 'menuItems.children'])
      ->where('active = 1')
      ->all();

    $hmenu = false;
    $fmenu = false;
    $mmenu = false;

    foreach ($menus as $menu) {
      if ($menu->position == 'header') {
        $hmenu = $menu;
        continue;
      }

      if ($menu->position == 'footer') {
        $fmenu = $menu;
        continue;
      }

      if ($menu->position == 'main_mob') {
        $mmenu = $menu;
        continue;
      }
    }

    if (!$hmenu) $hmenu = new Menus();
    if (!$fmenu) $fmenu = new Menus();
    if (!$mmenu) $mmenu = new Menus();

    Yii::$app->view->params['hmenu'] = $hmenu;
    Yii::$app->view->params['fmenu'] = $fmenu;
    Yii::$app->view->params['mob_menu'] = $mmenu;

    $sname = Yii::$app->request->serverName;
    $exp = explode('.', $sname);
    $sname = count($exp) < 3 ? $sname : $exp[1] . '.' . $exp[2];

    $country = \app\models\Countries::find()
      ->where(['domain' => $sname])
      ->one();

    Yii::$app->view->params['country'] = $country;

    if (Yii::$app->user->isGuest) {
      $sess = Yii::$app->session;

      if (!isset($sess['city']) || !$sess['city']) {
        $ip = Yii::$app->request->getUserIP();

        if ($ip != '127.0.0.1' && $ip != '::1') {
          $data = Geo::getLocationByIp($ip);

          if ($data && isset($data['city'])) {
            $sess['city'] = $data['city'];
          }
        }
      }
    } else if (Yii::$app->user->identity->city) {
      // $data = Geo::getLocationByAddress(Yii::$app->user->identity->city);
    }
  }

  public static function getPageUrl($url)
  {
    if (!$url) return '/';
    $url = '/' . trim($url, '/') . '/';
    if ($url == '//') $url = '/';
    return $url;
  }

  public static function registerPageDataByUrl($url)
  {
    $url = self::getPageUrl($url);

    $page = Pages::find()
      ->where('url="' . $url . '"')
      ->one();

    return self::registerPageData($page);
  }

  public static function registerPageData($page, $category = false)
  {

    $view = Yii::$app->view;

    if ($page) {
      self::$registeredPages[] = $page;
      $view->params['page'] = $page;
      $view->params['page_name'] = $page->name;
      $view->params['page_content'] = html_entity_decode($page->content);
      $view->title = $page->title;

      if ($category) {
        $view->params['relative_page'] = Yii::$app->db
          ->createCommand(
            'SELECT url, name FROM pages WHERE url="kak_kupit_' . str_replace('category_', '', $category) . '"')
          ->queryOne();
      }

      if (!empty($page->meta_author)) {
        $view->registerMetaTag([
          'name' => 'author',
          'content' => $page->meta_author
        ]);
      }

      $view->registerMetaTag([
        'name' => 'title',
        'content' => $page->meta_title
      ]);

      $view->registerMetaTag([
        'name' => 'description',
        'content' => $page->meta_description
      ]);

      $view->registerMetaTag([
        'name' => 'keywords',
        'content' => $page->meta_keywords
      ]);

      if (!empty($page->meta_robots)) {
        $view->registerMetaTag([
          'name' => 'robots',
          'content' => $page->meta_robots
        ]);
      }
    } else if (empty(self::$registeredPages)) {
      $view->params['page_name'] = '';
      $view->params['page_content'] = '';
    }

    return $view->params;
  }

  public static function registerProductPageData($product_group = '', $url_params_arr = [], $args = [])
  {
    if ($product_group && $url_params_arr) {
      $page = isset($args['page']) ? $args['page'] : new Pages();
      $country = $args['country'];
      $year = isset($args['year']) ? $args['year'] : '';

      $url_params_arr_by_name = [];

      foreach ($url_params_arr as $upa) {
        if (isset($upa['name']) && strpos($upa['name'], 'attribute_') !== false) {
          if (!isset($url_params_arr_by_name['attribute'])) {
            $url_params_arr_by_name['attribute'] = [];
          }

          if (!isset($url_params_arr_by_name['attribute'][$upa['name']])) {
            $url_params_arr_by_name['attribute'][$upa['name']] = [];
          }

          $url_params_arr_by_name['attribute'][$upa['name']][] = $upa;
        } else {
          if (!isset($url_params_arr_by_name[$upa['name']])) {
            $url_params_arr_by_name[$upa['name']] = [];
          }

          $url_params_arr_by_name[$upa['name']][] = $upa;
        }
      }

      $getString = function($attr_name, $descr) use ($product_group, $url_params_arr_by_name, $country, $page, $args, $year) {
        $param_arr = [
          '{категория}' => '',
          '{страна}' => '',
          '{город}' => '',
          '{марка}' => '',
          '{модель}' => '',
          '{поколение}' => '',
          '{год}' => '',
        ];

        if ($descr) {
          if (strpos($descr, '{категория}') !== false) {
            $text = 'Любые б/у запчасти';

            if (isset($url_params_arr_by_name['category_group'])) {
              $text = $url_params_arr_by_name['category_group'][0]['title'];
            }

            if (isset($url_params_arr_by_name['category_subgroup'])) {
              $text = $url_params_arr_by_name['category_subgroup'][0]['title'];
            }

            if (isset($url_params_arr_by_name['category'])) {
              $text = $url_params_arr_by_name['category'][0]['title'];
            }

            $descr = preg_replace('/\{категория\}/', $text, $descr);
          }

          if (!self::$morphy) {
            self::$morphy = new Morphy('ru');
          }

          if (strpos($descr, '{страна}') !== false) {
            $cname = $country->name;

            if ($cname == 'Россия') {
              $cname = 'России';
            } else if ($cname == 'Беларусь') {
              $cname = 'Беларуси';
            }

            $descr = preg_replace('/\{страна\}/', $cname, trim($descr));
          }

          if (strpos($descr, '{город}') !== false) {
            $city_name = '';

            if (isset($args['site_city']) && $args['site_city']) {
              $city_name = self::$morphy->getAllForms(mb_strtoupper($args['site_city']));
              $city_name = isset($city_name[4]) ? $city_name[4] : '';
              $city_name = ucfirst($city_name);
            } else {
              if ($country->name == 'Россия') {
                $descr = preg_replace('/\{город\}/', 'Москве', $descr);
              } else if ($country->name == 'Беларусь') {
                $descr = preg_replace('/\{город\}/', 'Минске', $descr);
              }
            }

            $descr = preg_replace('/\{город\}/', (mb_strtolower($city_name)), $descr);
          }

          if (strpos($descr, '{марка}') !== false) {
            $text = '';

            if (isset($url_params_arr_by_name['make'])) {
              $text = $url_params_arr_by_name['make'][0]['title'];
            }

            $descr = preg_replace('/^\{марка\}/', $text, $descr);
            $descr = preg_replace('/\{марка\}/', 'для ' . $text, $descr);
          }

          if (strpos($descr, '{модель}') !== false) {
            $text = '';

            if (isset($url_params_arr_by_name['model'])) {
              $text = ' ' . $url_params_arr_by_name['model'][0]['title'];
            }

            $descr = preg_replace('/\{модель\}/', $text, $descr);
          }

          if (strpos($descr, '{поколение}') !== false) {
            $text = '';

            if (isset($url_params_arr_by_name['generation'])) {
              $text = ' ' . $url_params_arr_by_name['generation'][0]['title'];
            }

            $descr = preg_replace('/\{поколение\}/', $text, $descr);
          }

          if (strpos($descr, '{год}') !== false) {
            $text = '';

            if ($year) {
              $text = ' ' . $year;
            }

            $descr = preg_replace('/\{год\}/', $text, $descr);
          }

          if (strpos($descr, '{атрибут}') !== false) {
            $text = '';

            if (isset($url_params_arr_by_name['attribute'])) {
              foreach ($url_params_arr_by_name['attribute'] as $uattrs) {
                foreach ($uattrs as $uattr) {
                  $text .= ' ' . $uattr['title'];
                }
              }
            }

            $descr = preg_replace('/\{атрибут\}/', $text, $descr);
          }

          $page->$attr_name = $descr;
        }
      };

      $getString('title', $product_group->title);
      $getString('meta_title', $product_group->meta_title);
      $getString('meta_description', $product_group->meta_description);
      $getString('content', $product_group->page_content);
      return $page;
    }
  }
}
