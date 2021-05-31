<?php

namespace app\controllers;

use app\models\Cities;
use app\models\Products;
use app\models\products\ProductGroups;

use app\models\products\SeoTemplates;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;

use app\models\makes\Makes;
use app\models\makes\Models;
use app\models\products\Categories;
use app\models\products\CatalogCategories;

use app\models\helpers\Lists;
use app\models\user\Statistics;
use app\models\helpers\PageUtils;
use app\models\site\Pages;
use yii\data\Pagination;


class ProductsController extends Controller
{

  private $view_vars = [];

  public function behaviors()
  {
    return [
      'access' => [
        'class' => AccessControl::className(),
        'only' => ['logout'],
        'rules' => [
          [
            'actions' => ['logout'],
            'allow' => true,
            'roles' => ['@'],
          ],
        ],
      ],
      'verbs' => [
        'class' => VerbFilter::className(),
        'actions' => [
          'logout' => ['post'],
        ],
      ],
    ];
  }

  public function actions()
  {
    return [
      'error' => [
        'class' => 'yii\web\ErrorAction',
      ],
      'captcha' => [
        'class' => 'yii\captcha\CaptchaAction',
        'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
      ],
    ];
  }

  public function actionIndex($parameters = '') {
    return $this->actionProducts('', $parameters);
  }

  public function actionProduct()
  {
    $id = basename(Yii::$app->request->pathInfo);

    $product = Products::find()
      ->where(['id' => $id, 'status' => Products::STATE_ACTIVE])
      ->with(['country', 'user'])
      ->one();

    $user = Yii::$app->user->identity;

    $data = array(
      'product' => false,
      'similar' => false,
      'related' => false,
      'seller' => false,
      'error' => false,
    );

    if (!$product) {
      throw new \yii\web\HttpException(404, 'Page not found');
    }

    $error = false;

    if ($product->status != Products::STATE_ACTIVE && (!$user || ($user->id != $product->user_id && $user->isAdmin()))) {
      $error = 'Объявление неактивно';
    }

    if (!$error) {
      $page = new Pages();
      $name = $product->name;

      $arr = array(
        'title' => $name,
        'meta_title' => $name,
        'meta_description' => $name,
        'name' => $name,
        'meta_robots' => 'index,follow'
      );

      $page->load($arr, '');
      PageUtils::registerPageData($page, $product->category);

      $this->checkViewed($product);
      $data['product'] = $product;

      $data['similar'] = Products::find()
        ->with(['user', 'contacts', 'attributesArray', 'country'])
        ->where('category="' . $product->category .
          '" AND make="' . $product->make .
          '" AND id!=' . $product->id .
          ' AND status=' . Products::STATE_ACTIVE .
          ($user ? ' AND user_id!=' . $user->id : ''))
        ->limit(10)
        ->all();

      $group = ProductGroups::findOne(['product_group_id' => $product->group_id]);
      $data['select_city'] = Lists::getOptionCityList(false, false, true);

      $data['city'] = Cities::find()
        ->where(['name' => $product->city])
        ->one();

      if ($group) {
        $attribute_ids = '';

        foreach ($group->attribute_groups as $key => $attr) {
          if (strpos($key, 'attribute_') !== false) {
            $exp = explode('_', $key);
            $attribute_ids .= ',' . $exp[1];
          }
        }

        if ($attribute_ids) {
          $attribute_ids = substr($attribute_ids, 1);

          $data['attributes'] = Yii::$app->db
            ->createCommand('SELECT product_category_attribute_groups.filter_name, 
              product_category_attribute_groups.important, product_category_attribute_groups.attribute_group_id, 
              product_category_attribute_groups.important, product_category_attribute_groups.required, 
              product_category_attributes.value, product_category_attributes.url, product_category_attributes.attribute_id, 
              product_category_attribute_groups.alt_names FROM product_category_attribute_groups
              LEFT JOIN product_category_attributes ON product_category_attributes.attribute_group_id=product_category_attribute_groups.attribute_group_id 
              WHERE product_category_attribute_groups.attribute_group_id IN (' . $attribute_ids . ') 
              ORDER BY product_category_attribute_groups.sort_order ASC')
            ->cache(3600)
            ->queryAll();
        } else {
          $data['attributes'];
        }
      }

      $data['related'] = Products::find()
        ->with(['user', 'contacts', 'attributesArray', 'country'])
        ->where('category="' . $product->category .
          '" AND make="' . $product->make .
          '" AND model="' . $product->model .
          '"AND generation="' . $product->generation .
          '" AND year="' . $product->year .
          '" AND id!=' . $product->id .
          ' AND status=' . Products::STATE_ACTIVE .
          ($user ? ' AND user_id!=' . $user->id : ''))
        ->limit(10)
        ->all();

      $data['favourites'] = array();

      if (!Yii::$app->user->isGuest) {
        $favourites = Yii::$app->db
          ->createCommand('SELECT product_id FROM user_favourites WHERE group_id=' . $product->group_id)
          ->queryAll();


        foreach ($favourites as $fav) $data['favourites'][] = $fav['product_id'];
      }

      $data['product_group'] = $group;
      $urlt = $product->url_template;
      $exp = explode('/', $urlt);

      $attrs = $product->attributesArray;
      $attrs_arr = [];

      foreach ($attrs as $attr) {
        $attrs_arr[mb_strtolower($attr['name'])] = $attr;
      }

      $group_url = $group['url'];
      $url = '/' . $group_url;
      $bread = [['label' => 'Главная', 'url' => '/']];
      $bread[] = ['label' => $group->name, 'url' => '/' . $group->url];

      foreach ($exp as $par) {
        if (isset($product->{$par}) && $par != 'id') {
          $url .= '/' . $product->{$par};
          $bread[] = ['label' => $product->{$par . '_val'}, 'url' => $url];
        } else if (isset($attrs_arr[mb_strtolower($par)])) {
          $attr = $attrs_arr[mb_strtolower($par)];
          $url .= '/' . $attr['url'];
          $bread[] = ['label' => $group->name . ' ' . $attr['name'] . ' ' . $attr['value'], 'url' => $url];
        }
      }

      Yii::$app->view->params['breadcrumbs'] = $bread;
    } else $data['error'] = $error;

    return $this->render('product', array_merge($this->view_vars, $data));
  }

  public function actionProducts($group_url = '', $parameters = '', array $params = [])
  {
    $product_group = false;

    if (is_object($group_url)) {
      $product_group = $group_url;
      $group_url = $product_group['url'];
    } else if ($group_url) {
      $product_group = ProductGroups::find()
        ->where('url="' . $group_url . '"')
        ->one();
    }

    $groups = array();

    if ($group_url && !$product_group) {
      throw new \yii\web\HttpException(404, 'Page not found');
    } else {
      $groups = ProductGroups::find()->all();
      $arr = [];
      foreach ($groups as $gr) $arr[$gr->product_group_id] = $gr;
      $groups = $arr;
    }

    if ($group_url) {
      PageUtils::registerPageDataByUrl(PageUtils::getPageUrl($group_url));
    }

    $item_model = new Products();
    $similar_categories = [];
    $req = Yii::$app->request;
    $make = isset($params['make']) ? $params['make'] : $req->get('make');
    $model = isset($params['model']) ? $params['model'] : $req->get('model');
    $category = isset($params['category']) ? $params['category'] : $req->get('category');
    $section = false;
    $subsection = false;
    $host = explode('.', Yii::$app->request->hostName);
    $parameters_arr = explode('/', $parameters);
    $generation = false;
    $city = false;
    $year = false;
    $partnum = false;
    $url_params = [];
    $url_params_arr_add = [];
    $attrs = [];
    $where = 'status=' . Products::STATE_ACTIVE;

    if ($make) {
      $where .= ' AND make="' . $make . '"';
    }

    if ($model) {
      $where .= ' AND model="' . $model . '"';
    }

    $url_params['category'] = [];

    foreach ($parameters_arr as $v) {
      if (strpos($v, 'm-') !== false) {
        $make = $v;
        $where .= ' AND make="' . $v . '"';
        $url_params['make'] = $v;
      } else if (strpos($v, 'mod-') !== false) {
        $model = $v;
        $where .= ' AND model="' . $v . '"';
        $url_params['model'] = $v;
      } else if (strpos($v, 'gen-') !== false) {
        $generation = $v;
        $where .= ' AND generation="' . $v . '"';
        $url_params['generation'] = $v;
      } else if (strpos($v, 'year_') !== false) {
        $year = $v;
        $year_val = explode('_', $v);
        $where .= ' AND (year="' . $v . '" OR years LIKE "%' . $year_val[1] . '%")';
        $url_params['year'] = $v;
      } else if (strpos($v, 'nomer-') !== false) {
        $partnum = $v;
        $where .= ' AND (partnum_orig LIKE "%' . $v . '%" OR partnum LIKE "%' . $v . '%")';
        $url_params['partnum'] = $v;
      } else if (strpos($v, 'r-') !== false) {
        $section = $v;
        $category = $v;
      } else if (strpos($v, 'pr-') !== false) {
        $subsection = $v;
        $category = $v;
      } else if (strpos($v, 'zp-') !== false) {
        $category = $v;
      } else if (!empty($v) && $v != $group_url && (!isset($params['make_group']) || $params['make_group'] != $v)) {
        $attrs[] = $v;
        $where .= ' AND attributes_list LIKE "%' . $v . '%"';
        if (!isset($url_params['attribute'])) $url_params['attribute'] = [];
        $url_params['attribute'][] = $v;
      }
    }

    $cat = false;

    if ($category) {
      $cat = Categories::find()
        ->select('category_id, synonym_url, connected_category_url, connected_category, name, url')
        ->where('url="' . $category . '" OR synonym LIKE "%' . $category . '%"')
        ->one();

      $in = '"' . $category . '",';
      $section_list = '';

      if ($cat) {
        $url_params_arr_add['category'][] = [
          'connected_id' => $cat->category_id,
          'name' => 'category',
          'title' => $cat->name
        ];

        $url_params['category'] = $cat->category_id;
        $syn = explode(';', $cat->synonym_url);

        foreach ($syn as $s) {
          if ($s && !empty($s)) {
            $in .= '"' . $s . '",';
          }
        }

        $conn = explode(';', $cat->connected_category_url);
        $conn_names = explode(';', $cat->connected_category);

        foreach ($conn as $si => $s) {
          if ($s && !empty($s)) {
            $in .= '"' . $s . '",';

            if (strpos($conn_names[$si], $cat->name) !== false) {
              $similar_categories[$s] = $conn_names[$si];
            }
          }
        }
      } else {
        $cat_section = CatalogCategories::find()
          ->where('url="' . $category . '"')
          ->one();

        $url_params['category_group'] = [];
        $url_params['category_subgroup'] = [];


        if ($cat_section) {
          $section_list .= '"' . $cat_section->id . '",';

          if ($cat_section->parent_id) {
            $url_params_arr_add['category_subgroup'][] = [
              'connected_id' => $cat_section->id,
              'name' => 'category',
              'title' => $cat_section->name
            ];

            $url_params['category_subgroup'] = $cat_section->id;

            $cat_sections = CatalogCategories::find()
              ->where('(id="' . $cat_section->parent_id . " OR parent_id=" . $cat_section->parent_id . '") AND id!="' . $cat_section->id . '"')
              ->all();

            foreach ($cat_sections as $section) {
              $section_list .= '"' . $section->id . '",';
            }
          } else {
            $url_params_arr_add['category_group'][] = [
              'connected_id' => $cat_section->id,
              'name' => 'category',
              'title' => $cat_section->name
            ];

            $url_params['category_group'] = $cat_section->id;
          }
        }

        $section_list = substr($section_list, 0, -1);

        $cat_list = $section_list ? Categories::find()
          ->select('category_id, url, synonym_url, connected_category_url')
          ->where('catalog_category_id IN (' . $section_list . ')')
          ->all() : [];

        foreach ($cat_list as $cat_item) {
          $in .= '"' . $cat_item->url . '",';
          $syn = explode(';', $cat_item->synonym_url);

          foreach ($syn as $s) {
            if ($s && !empty($s)) {
              $in .= '"' . $s . '",';
            }
          }

          $conn = explode(';', $cat_item->connected_category_url);

          foreach ($conn as $s) {
            if ($s && !empty($s)) {
              $in .= '"' . $s . '",';
            }
          }
        }
      }

      $in = substr($in, 0, -1);
      $where .= ' AND category IN (' . $in . ')';
    }

    foreach (Yii::$app->request->get() as $key => $val) {
      if (strpos($key, 'attribute_') !== false) {
        $attrs[] = $val;
        $where .= ' AND attributes_list LIKE "%' . $val . '%"';
      } else if ($key == 'partnum' || $key == 'sku') {
        if ($key === 'partnum') {
          $val = preg_replace('/[^A-Za-z0-9\-]/', '', $val);
        }

        $where .= ' AND `' . $key . '`="' . $val . '"';
      }
    }
    
    if (Yii::$app->request->get('available')) {
      $where .= ' AND available=1';
    }

    if (Yii::$app->request->get('country_selected') && Yii::$app->request->get('country') != 'all') {
      $where .= ' AND country_id="' . Yii::$app->request->get('country') . '"';
    }

    if (Yii::$app->request->get('city') && Yii::$app->request->get('city') != 'all') {
      $where .= ' AND city="' . Yii::$app->request->get('city') . '"';
      $city = Yii::$app->request->get('city');
    } else if (sizeof($host) >= 2) {
      if ($host[1] == 'localhost') {
        $where .= ' AND city_domain="' . $host[0] . '"';
        $city = $host[0];
      } else if (sizeof($host) >= 3) {
        $where .= ' AND city_domain="' . $host[0] . '"';
        $city = $host[0];
      }
    }

    $this->getBreadcrumbs([
      'group' => $product_group ? $product_group : false,
      'make' => $make ? ['url' => $make, 'page' => '/' . $make] : false,
      'model' => $make ? ['page' => ($make . '/' . $model), 'url' => $model] : false,
      'category' => $make && $model ? ['page' => ('/' . $make . '/' . $model . '/' . $category), 'url' => $category] : false
    ]);

    $pg = Yii::$app->request->get('page');
    $offset = (int)$pg ? (((int)$pg - 1) * 20) : '0';
    if ($product_group) $where .= ' AND group_id=' . $product_group->product_group_id;

    $make_group_ids = '';

    if (isset($params['make_group']) && $params['make_group']) {
      $mg = Yii::$app->db
        ->createCommand('SELECT makes.url, make_groups.make_group_id FROM makes INNER JOIN make_to_group ON make_id=id 
          INNER JOIN make_groups ON make_groups.make_group_id=make_to_group.make_group_id 
          WHERE make_groups.url="' . $params['make_group']. '"')
        ->queryAll();

      if ($mg) {
        $makes = ' AND make IN (';

        foreach ($mg as $m) {
          $makes .= '"' . $m['url'] . '",';
          $make_group_ids = 'make_to_group.make_group_id=' . $m['make_group_id'];
        }

        $where .= substr($makes, 0, -1) . ')';
      }
    }

    $orderby = [];

    $session = Yii::$app->session;
    $sorting_date = $session->get('sorting_date');
    $sorting_price = $session->get('sorting_price');

    if ($sorting_date == 1) {
      $orderby['date_created'] = SORT_DESC;
    } elseif ($sorting_date == 2) {
      $orderby['date_created'] = SORT_ASC;
    } else {
      $orderby['date_created'] = SORT_DESC;
    }

    if ($sorting_price == 3) {
      $orderby['byn_price'] = SORT_DESC;
    } elseif ($sorting_price == 4) {
      $orderby['byn_price'] = SORT_ASC;
    }

    if (!$orderby) $orderby['date_created'] = 'DESC';

    $resp = [
      'scrollY' => $parameters_arr || $req->get() ? 1 : 0,
      'product_group' => $product_group,
      'groups' => $groups,
      'selected_category' => $cat ? $cat->url : '',
      'category' => $product_group && $product_group['product_categories'] != 'all' ? $product_group['product_categories'] : false,
      'similar_categories' => $similar_categories,
      'products' => Products::find()
        ->with(['user', 'contacts', 'attributesArray', 'country'])
        ->where($where)
        ->limit(20)
        ->offset($offset)
        ->orderBy($orderby)
        ->all(),
      'pagination' => new Pagination(
        [
          'totalCount' => Yii::$app->db
            ->createCommand('SELECT COUNT(*) FROM products WHERE ' . $where)
            ->queryScalar(),
          'route' => Yii::$app->request->pathInfo
        ]
      ),
    ];
    
    if ($product_group) {
      $values = [
        'make' => $make,
        'model' => $model,
        'generation' => $generation,
        'attrs' => $attrs,
        'category' => $category,
        'year' => $year,
        'country' => Yii::$app->request->get('country'),
        'city' => $city,
        'availability_attrs' => (count($attrs) > 0 ? true : false),
      ];

      $resp['params'] = Lists::getOptions([], $product_group, $values, $make_group_ids, true, true);


    } else {
      $resp['params'] = Lists::getOptionAttributeList('', '', true);
    }

    /*
    if (isset(Yii::$app->session['viewed'])) {
      $arr = Yii::$app->session['viewed'];
      $ids = isset($arr[$product_group->product_group_id]) ? $arr[$product_group->product_group_id] : false;
      $ids_list = null;

      if (is_array($ids)) {
        foreach ($ids as $id) $ids_list .= ',' . $id;

        $resp['viewed'] = $ids_list ? Products::find()
          ->where(('id IN (' . substr($ids_list, 1) . ') AND group_id=' . $product_group->product_group_id))
          ->orderBy('date_created DESC')
          ->limit(20)
          ->all() : false;
      }
    }
    */

    if (!Yii::$app->user->isGuest) {
      $favourites = Yii::$app->db
        ->createCommand('SELECT product_id FROM user_favourites ' . ($product_group ? 'WHERE group_id=' . $product_group->product_group_id : ''))
        ->queryAll();

      $resp['favourites'] = array();
      foreach ($favourites as $fav) $resp['favourites'][] = $fav['product_id'];
    }

    if (!Yii::$app->request->get('partial')) {
      $page = new Pages();

      $url_where = '';

      foreach ($url_params as $k => $p) {
        if ($p) {
          $name_where = 'name="' . $k . '"';

          if (strpos($k, 'attribute') !== false) {
            $name_where = 'name LIKE "' . $k . '_%"';
          }

          if (is_array($p)) {
            foreach ($p as $e) {
              if (is_numeric($e) && strval(intval($p)) == $p) {
                $url_where .= ' OR (' . $name_where . ' AND connected_id="' . $e . '")';
              } else {
                $url_where .= ' OR (' . $name_where . ' AND url="' . $e . '")';
              }
            }
          } else {
            if (is_numeric($p) && strval(intval($p)) == $p) {
              $url_where .= ' OR (' . $name_where . ' AND connected_id="' . $p . '")';
            } else {
              $url_where .= ' OR (' . $name_where . ' AND url="' . $p . '")';
            }
          }
        }
      }

      $url_params_arr = [];

      if ($url_where) {
        $url_where = substr($url_where, 4);

        $url_params_arr = Yii::$app->db
          ->createCommand('SELECT * FROM url_params WHERE ' . $url_where . ' GROUP BY name, url')
          ->queryAll();
      }


      if (isset($url_params_arr_add['category'])) {
        $url_params_arr[] = array_merge($url_params_arr_add['category'], [ 'name' => 'category' ]);
      }

      if (isset($url_params_arr_add['category_group'])) {
        $url_params_arr[] = array_merge($url_params_arr_add['category_group'], [ 'name' => 'category_group' ]);
      }

      if (isset($url_params_arr_add['category_subgroup'])) {
        $url_params_arr[] = array_merge($url_params_arr_add['category_subgroup'], [ 'name' => 'category_subgroup' ]);
      }

      $page_url = PageUtils::getPageUrl(Yii::$app->request->pathInfo);
      $target_gage = false;

      if ($page_url && $page_url != '/' && (!$group_url || $page_url != PageUtils::getPageUrl($group_url))) {
        $target_gage = Pages::find()
          ->where(['url' => $page_url])
          ->one();

        if ($target_gage) {
          PageUtils::registerProductPageData($target_gage);
        }
      }

      $url_template = false;

      if ($product_group) {
        if ($year) {
          $url_params_arr[] = [
            'name' => 'year'
          ];
        }

        $url_template = $product_group->getAppliedSeoTemplate($url_params_arr);

        if ($url_template) {
          PageUtils::registerPageData(
            PageUtils::registerProductPageData($url_template, $url_params_arr, [
              'country' => $this->view->params['country'],
              'site_city' => isset($this->view_vars['site_city']) ? $this->view_vars['site_city'] : '',
              'year' => $year ? $year_val[1] : false,
              'page' => $page
            ])
          );
        }
      }

      if (!$target_gage && !$url_template) {
        PageUtils::registerPageData(
          PageUtils::registerProductPageData($product_group, $url_params_arr, [
            'country' => $this->view->params['country'],
            'site_city' => isset($this->view_vars['site_city']) ? $this->view_vars['site_city'] : '',
            'year' => $year ? $year_val[1] : false,
            'page' => $page
          ])
        );
      }
      return $this->render('index', array_merge($this->view_vars, $resp));
    } else {
      $r = '';

      foreach ($resp['products'] as $product) {
        $r .= $this->renderPartial('//layouts/parts/product.php', [
            'product' => $product,
            'product_group' => $resp['product_group'],
            'groups' => $groups,
            'favourites' => isset($resp['favourites']) ? $resp['favourites'] : [],
            'attributes' => $resp['params']['attributes'],
          ]);
      }

      $pagination = $this->renderPartial('//layouts/parts/pagination',
        ['pagination' => $resp['pagination'], 'container' => 'products']
      );

      return json_encode(['items' => $r, 'pagination' => $pagination]);
    }
  }

  public function actionSell($group_url = '')
  {

    if (!Yii::$app->user->isGuest && !Yii::$app->request->isPost) {
      return $this->redirect('/personal/sell' . ($group_url ? '/' . $group_url : ''));
    }

    if (!Yii::$app->user->isGuest && Yii::$app->request->isPost) {
      return Yii::$app->runAction('personal/sell');
    }

    $params = array();
    $group = false;
    
    if ($group_url) {
      $group = ProductGroups::find()->where(['url' => $group_url])->one();
    } else {
      $group = ProductGroups::find()->where('is_default=1')->one();
    }

    if (!$group) {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }

    if (Yii::$app->request->isPost) {
      $model = new \app\models\forms\SellForm();

      if ($model->load(Yii::$app->request->post(), '')) {
        $res = $model->saveData($group);
        if (!isset($res['errors'])) $res['errors'] = [];
        foreach ($model->errors as $key => $val) $res['errors'][$key] = $val;
        return json_encode($res);
      }

      throw new \yii\web\HttpException(400, 'Ошибка выполнения операции');
    }

    PageUtils::registerPageData(Pages::findOne(['url' => PageUtils::getPageUrl(Yii::$app->request->pathInfo)]));

    $attribute_values = [];
    $product = new Products();
    $product->load(Yii::$app->request->get(), '');

    foreach (Yii::$app->request->get() as $k => $v) {
      if (strpos($k, 'attribute_') !== false) $attribute_values[] = $v;
    }

    $data = array(
      'error_message' => false,
      'product' => false,
      'group' => $group,
      'products' => array(),
    );

    if ($group) {
      $data['product_group_id'] = $group['product_group_id'];

      $data['params'] = Lists::getOptions([], $group, [
        'no_empty_values' => true,
        'country' => Yii::$app->view->params['country']->id
      ]);

      $data['product'] = $product;
    } else {
      $data['error'] = 'Возникла ошибка: группа товаров не определена';
    }

    Yii::$app->view->params['breadcrumbs'][] = [
      'label' => $group->name,
      'url' => '/' . $group->url
    ];

    Yii::$app->view->params['breadcrumbs'][] = [
      'label' => 'Новое объявление',
      'url' => '/prodat/' . $group->url
    ];

    return $this->render('sell', array_merge($this->view_vars, array_merge($params, $data)));
  }

  protected function checkViewed($product)
  {
    $varr = array();
    $cookies = Yii::$app->session;

    if (isset($cookies['viewed'])) {
      $varr = $cookies['viewed'];
    }

    if (!isset($varr[$product['group_id']])) $varr[$product['group_id']] = array();
    $ids = $varr[$product['group_id']];

    if (!in_array($product['id'], $ids) && (Yii::$app->user->isGuest || Yii::$app->user->identity->id != $product['user_id'])) {
      array_push($ids, $product['id']);

      if (count($ids) == 50) array_shift($ids);
      $varr[$product['group_id']] = $ids;

      Yii::$app->db
        ->createCommand('UPDATE products set views="' . ($product['views'] + 1) .
          '" WHERE id="' . $product['id'] . '"')
        ->execute();
    }

    if (Yii::$app->user->isGuest || Yii::$app->user->identity->id != $product['user_id']) {
      $date = date('Y-m-d');

      $statistics = Statistics::find()->where('user_id=' . $product['user_id'] .
        ' AND date="' . $date . '" AND group_id=' . $product['group_id'])->one();

      Yii::$app->db->
        createCommand('INSERT INTO product_statistics
          (' . (!Yii::$app->user->isGuest ? 'user_id,' : '') . 'owner_id,product_id,group_id,date)
				  VALUES (' . (!Yii::$app->user->isGuest ? Yii::$app->user->identity->id . ',' : '') .
          $product['user_id'] . ',' . $product['id'] . ',' . $product['group_id'] . ',"' . $date . '")')
        ->execute();

      if (!$statistics) {
        $statistics = new Statistics();
        $statistics->user_id = $product['user_id'];
        $statistics->date = $date;
        $statistics->views = 0;
        $statistics->group_id = $product['group_id'];
      }

      $statistics->addView($product['id']);
      $statistics->views++;
      $statistics->save();
    }

    $cookies['viewed'] = $varr;
  }

  private function getBreadcrumbs($data, $add = []) {
    $bread = [['label' => 'Главная', 'url' => '/']];
    $group_url = '';

    if (isset($data['group']) && $data['group']) {
      $bread[] = ['label' => $data['group']['name'], 'url' => '/' .  $data['group']['url']];
    }

    foreach ($add as $a => $v) {
      $bread[] = ['label' => $v['name'], 'url' => $v['page']];
    }

    if (isset($data['make']) && $data['make']) {
      $obj = Makes::find()
        ->select('name')
        ->where(['url' => $data['make']['url']])
        ->one();

      if ($obj) {
        $bread[] = ['label' => $obj['name'], 'url' => $data['make']['page']];
      }
    }

    if (isset($data['model']) && $data['model']) {
      $obj = Models::find()
        ->select('name')
        ->where(['url' => $data['model']['url']])
        ->one();

      if ($obj) {
        $bread[] = ['label' => $obj['name'], 'url' => $data['model']['page']];
      }
    }

    if (isset($data['category']) && $data['category']) {
      $obj = Categories::find()
        ->select('name')
        ->where(['url' => $data['category']['url']])
        ->one();

      if ($obj) {
        $bread[] = ['label' => $obj['name'], 'url' => $data['category']['page']];
      }
    }

    Yii::$app->view->params['breadcrumbs'] = $bread;
  }

  public function beforeAction($action)
  {
    $this->enableCsrfValidation = true;
    PageUtils::getMenus();

    $host = explode('.', Yii::$app->request->hostName);

    if (sizeof($host) >= 2) {
      Yii::$app->view->params['site_city'] = Cities::find()
        ->where(['domain' => $host[0]])
        ->one();
    }

    Yii::$app->view->params['breadcrumbs'] = [
      ['label' => 'Главная', 'url' => '/']
    ];

    if (!Yii::$app->user->isGuest && $action->id == 'sell') {
      $this->enableCsrfValidation = false;
    }

    return parent::beforeAction($action);
  }
}
