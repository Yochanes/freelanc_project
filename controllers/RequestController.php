<?php

namespace app\controllers;

use app\models\Products;
use app\models\Requests;
use app\models\products\ProductGroups;

use Yii;
use yii\web\Controller;

use app\models\forms\RequestForm;
use app\models\helpers\PageUtils;
use app\models\helpers\Lists;

use app\models\site\Pages;
use yii\data\Pagination;

class RequestController extends Controller
{

  private $view_vars = [];

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

  public function actionRequest($group_url = '')
  {

    /*
    if (!Yii::$app->user->isGuest && !Yii::$app->request->isPost) {
      return $this->redirect('/personal/requestssearch' . ($group_url ? '?group=' . $group_url : ''));
    }
    */

    $product_group = false;

    if ($group_url) {
      $product_group = ProductGroups::find()->where(['url' => $group_url])->one();
    }

    if ($group_url && !$product_group) {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }

    $groups_arr = ProductGroups::find()->all();
    $groups = array();
    $def_group = false;

    foreach ($groups_arr as $group) {
      $groups[$group['product_group_id']] = $group;
      if ($group->is_default) $def_group = $group;
    }

    PageUtils::registerPageData(Pages::findOne(['url' => PageUtils::getPageUrl(Yii::$app->request->pathInfo)]));

    $pg = Yii::$app->request->get('page');
    $offset = (int)$pg ? (((int)$pg - 1) * 20) : '0';

    $orderby = [];

    $session = Yii::$app->session;
    $sorting_date = $session->get('sorting_date');

    if ($sorting_date == 1) {
      $orderby['date_created'] = SORT_DESC;
    } elseif ($sorting_date == 2) {
      $orderby['date_created'] = SORT_ASC;
    } else {
      $orderby['date_created'] = SORT_DESC;
    }

    if ($product_group) {
      Yii::$app->view->params['breadcrumbs'][] = [
        'label' => $product_group->name,
        'url' => '/zapros-na-poisk/' . $product_group->url
      ];
    }

    $params = [
      'groups' => $groups,
      'product_group' => $product_group,
      'url' => $group_url ? $group_url : false,
      'params' => $product_group ? Lists::getAttributesByGroup([], $product_group) : Lists::getOptionAttributeList('', '', true),

      'pagination' => new Pagination(
        ['totalCount' => Yii::$app->db
          ->createCommand('SELECT COUNT(*) FROM requests WHERE target_user_id IS NULL' . ($product_group ? ' AND group_id=' . $product_group->product_group_id : ''))
          ->queryScalar()
        ]),

      'requests' => Requests::find()
        ->where('target_user_id IS NULL' . ($product_group ? ' AND group_id=' . $product_group->product_group_id : ''))
        ->with(['user', 'contacts', 'attributesArray', 'country'])
        ->limit(20)
        ->offset($offset)
        ->orderBy($orderby)
        ->all(),
    ];

    if (!Yii::$app->request->get('partial')) {
      return $this->render('request', array_merge($this->view_vars, $params));
    } else {
      $r = '';

      foreach ($params['requests'] as $product) {
        $r .= $this->render('//layouts/parts/request.php', [
            'product' => $product,
            'attributes' => $params['attributes_array'],
            'answer' => true
          ]);
      }

      $pagination = $this->renderPartial('//layouts/parts/pagination',
        ['pagination' => $params['pagination'], 'container' => 'products']
      );

      return json_encode(['items' => $r, 'pagination' => $pagination]);
    }
  }

  public function actionAdd($group_url = '')
  {
    if (!Yii::$app->user->isGuest && !Yii::$app->request->isPost) {
      return $this->redirect('/personal/requestadd' . ($group_url ? '/' . $group_url : ''));
    }

    $product_group = false;

    if ($group_url) {
      $product_group = ProductGroups::find()->where(['url' => $group_url])->one();
    } else {
      $product_group = ProductGroups::find()->where('is_default=1')->one();
    }

    if (!$product_group) {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }

    if (Yii::$app->request->isPost) {
      $model = new RequestForm();

      if ($model->load(Yii::$app->request->post(), '')) {
        $res = $model->saveData($product_group);
        if (!isset($res['errors'])) $res['errors'] = [];
        foreach ($model->errors as $key => $val) $res['errors'][$key] = $val;
        return json_encode($res);
      }
    }

    PageUtils::registerPageData(Pages::findOne(['url' => PageUtils::getPageUrl(Yii::$app->request->pathInfo)]));

    $pg = Yii::$app->request->get('page');
    $offset = (int)$pg ? (((int)$pg - 1) * 20) : '0';

    $orderby = [];

    $session = Yii::$app->session;
    $sorting_date = $session->get('sorting_date');

    if ($sorting_date == 1) {
      $orderby['date_created'] = SORT_DESC;
    } elseif ($sorting_date == 2) {
      $orderby['date_created'] = SORT_ASC;
    } else {
      $orderby['date_created'] = SORT_DESC;
    }

    Yii::$app->view->params['breadcrumbs'][] = [
      'label' => $product_group->name,
      'url' => '/zapros-na-poisk/' . $product_group->url
    ];

    Yii::$app->view->params['breadcrumbs'][] = [
      'label' => 'Новый запрос',
      'url' => '/noviy-zapros-na-poisk/' . $product_group->url
    ];

    $params = [
      'product_group' => $product_group,
      'params' => Lists::getOptions(
        [],
        $product_group,
        array_merge(Yii::$app->request->get(), ['no_empty_values' => false])
      ),

      'pagination' => new Pagination(
        ['totalCount' => Yii::$app->db
          ->createCommand('SELECT COUNT(*) FROM requests')
          ->queryScalar()
        ]),

      'requests' => Requests::find()
        ->where('target_user_id IS NULL AND group_id=' . $product_group->product_group_id)
        ->with(['user', 'contacts', 'attributesArray', 'country'])
        ->limit(20)
        ->offset($offset)
        ->orderBy($orderby)
        ->all(),
    ];

    if (!Yii::$app->request->get('partial')) {
      return $this->render('requestadd', array_merge($this->view_vars, $params));
    } else {
      $r = '';

      foreach ($params['requests'] as $product) {
        $r .= $this->render('//layouts/parts/request.php', [
          'product' => $product,
          'attributes' => $params['attributes_array'],
          'answer' => true
        ]);
      }

      $pagination = $this->renderPartial('//layouts/parts/pagination',
        ['pagination' => $params['pagination'], 'container' => 'products']
      );

      return json_encode(['items' => $r, 'pagination' => $pagination]);
    }
  }

  public function actionSorting()
  {
    $json = ['status' => 'false'];

    if (Yii::$app->request->post('type')) {
      Yii::$app->session->set('sorting', Yii::$app->request->post('type'));
      $json = ['status' => 'ok'];
    }

    return json_encode($json);
  }

  protected function processSearchAction($url, $tableName, $search_params = array(), $columns = array(), $additional_select = '', $addwhere = '', $ignore_cities = false, $ignore_viewed = false)
  {
    $this->registerPageData(Pages::findOne(['url' => $url]));
    // Сортировка
    $order = 'ORDER BY ' . $tableName . '.date_created DESC';

    $session = Yii::$app->session;
    $sorting = $session->get('sorting');

    if ($sorting == 1) {
      $order = 'ORDER BY ' . $tableName . '.date_created DESC';
    } elseif ($sorting == 2) {
      $order = 'ORDER BY ' . $tableName . '.date_created ASC';
    } elseif ($sorting == 3) {
      $order = 'ORDER BY ' . $tableName . '.price DESC';
    } elseif ($sorting == 4) {
      $order = 'ORDER BY ' . $tableName . '.price ASC';
    }

    $where = $tableName . '.status=' . Products::STATE_ACTIVE;

    foreach ($_GET as $k => $v) {
      if (!in_array($k, $search_params)) continue;

      if (!empty($v)) {
        if ($k == 'parttype') {
          $synonym = Yii::$app->db
            ->createCommand(
              'SELECT name FROM product_categories WHERE synonym LIKE "%' . $v . '%" OR name LIKE "%' . $v . '%"')
            ->queryOne();

          if ($synonym && $synonym['name']) $v = $synonym['name'];
        }

        $where .= ' AND ' . $tableName . '.' . $k . '="' . $v . '"';
      }
    }

    $page = Yii::$app->request->get('page');
    $offset = (int)$page ? (((int)$page - 1) * 20) : '0';

    // Список товаров
    $products = Yii::$app->db
      ->createCommand(Lists::buildProductSQL(
        $tableName, $columns, $additional_select, $where . (!empty($addwhere) ? (!empty($where) ? ' AND ' : '') . $addwhere : ''))
      . ' LIMIT 20' . $offset)
      ->queryAll();

    // Просмотренные товары
    $viewed = null;
    if (!$ignore_viewed) $viewed = Lists::getViewed($tableName);

    $params = [
      'select_city' => !$ignore_cities ? Lists::getOptionCityList(false, Yii::$app->view->params['country']) : false,
      'products' => $products,
      'viewed' => $viewed,
      'pagination' => new Pagination(
        ['totalCount' => Yii::$app->db->createCommand(
          'SELECT COUNT(*) FROM ' . $tableName . ' WHERE ' . $where . (!empty($addwhere) ? (!empty($where) ? ' AND ' : '') . $addwhere : ''))
          ->queryScalar()]
      ),
    ];

    return $params;
  }

  public function beforeAction($action)
  {
    $this->enableCsrfValidation = true;
    PageUtils::getMenus();

    $host = explode('.', Yii::$app->request->hostName);

    $this->view->params['page_name'] = '';
    $this->view->params['page_content'] = '';

    if (sizeof($host) >= 2) {
      Yii::$app->view->params['site_city'] = \app\models\Cities::find()
        ->where(['domain' => $host[0]])
        ->one();
    }

    Yii::$app->view->params['breadcrumbs'] = [
      ['label' => 'Главная', 'url' => '/'],
      ['label' => 'Запрос на поиск', 'url' => '/zapros-na-poisk']
    ];

    if ($action->id == 'add' && !Yii::$app->user->isGuest) $this->enableCsrfValidation = false;

    return parent::beforeAction($action);
  }
}
