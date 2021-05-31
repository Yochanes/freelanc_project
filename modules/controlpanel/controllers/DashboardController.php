<?php

namespace app\modules\controlpanel\controllers;

use app\components\SupportComponent;
use app\models\catalogs\Catalogs;
use app\models\helpers\PageUtils;
use app\models\site\ConfigSchedule;
use Yii;
use yii\web\Controller;
use yii\data\Pagination;

use app\models\site\Pages;
use app\models\site\Menus;
use app\models\site\Robots;

use app\models\User;
use app\models\Products;
use app\models\Countries;
use app\models\Requests;

use app\models\config\SMTP;
use app\models\makes\MakeGroups;

use app\models\products\Categories;
use app\models\products\CatalogCategories;
use app\models\products\ProductGroups;
use app\models\products\CategoryAttributeGroups;

use app\models\helpers\Lists;

use app\models\user\NotificationDialogs;
use app\models\user\Complaints;

use app\modules\controlpanel\models\forms\LoginForm;
use app\modules\controlpanel\models\forms\RegisterForm;

class DashboardController extends Controller
{

  public function beforeAction($action)
  {
    $this->layout = 'main';

    if (Yii::$app->user->isGuest && $this->action->id != 'login') {
      return $this->redirect(['/controlpanel/dashboard/login']);
    }

    if ((Yii::$app->user->identity == null || !Yii::$app->user->identity->isAdmin()) && $this->action->id != 'login') {
      return $this->redirect(['/controlpanel/dashboard/login']);
    }

    if (Yii::$app->request->isPost) {
      Yii::$app->cache->flush();
    }

    return parent::beforeAction($action);
  }

  public function actions()
  {
    return [
      'error' => [
        'class' => 'yii\web\ErrorAction',
      ],
    ];
  }

  public function actionIndex()
  {
    if (Yii::$app->user->isGuest && $this->action->id != 'login') {
      return $this->redirect(['/controlpanel/dashboard/login']);
    }

    if (!Yii::$app->user->identity->isAdmin() && $this->action->id != 'login') {
      return $this->redirect(['/controlpanel/dashboard/login']);
    }

    return $this->render('index', [
      'users_num' => User::find()->count(),
      'users_client_num' => User::find()->where(['role' => User::ROLE_CLIENT])->count(),
      'users_company_num' => User::find()->where(['role' => User::ROLE_COMPANY])->count(),
      'users_admin_num' => User::find()->where(['user_role' => User::ROLE_ADMIN])->count(),
      'products_num' => Products::find()->count(),
      'products_check_num' => Products::find()->where(['status' => Products::STATE_UNCHECKED])->count(),
      'products_active_num' => Products::find()->where(['status' => Products::STATE_ACTIVE])->count(),
      'products_inactive_num' => Products::find()->where(['status' => Products::STATE_INACTIVE])->count(),
      'requests_num' => Requests::find()->count(),
    ]);
  }

  public function actionUsers()
  {
        $query = $this->createSearchQuery($_GET, ['role', 'username', 'display_name']);
    if (empty($query)) $query = 'user_role != "' . User::ROLE_ADMIN . '"';

    $items = User::find()->where($query)->all();

    return $this->render('users/users', [
      'users' => $items
    ]);
  }

  public function actionCatalogs()
  {
    return $this->render('catalogs/index', [
      'items' => Catalogs::find()
        ->with(['paramsArray', 'linksArray'])
        ->all(),

      'groups' => ProductGroups::find()->all(),

      'attribute_groups' => CategoryAttributeGroups::find()
        ->all()
    ]);
  }

  public function actionAdmins()
  {

    $query = $this->createSearchQuery($_GET, ['username', 'display_name']);
    if (!empty($query)) $query .= 'AND ';
    $query .= 'user_role = "' . User::ROLE_ADMIN . '"';
    $items = User::find()->where($query)->all();

    return $this->render('users/admins', [
      'users' => $items
    ]);
  }

  public function actionUploadcolumns()
  {

    return $this->render('params/uploadcolumns', [
      'groups' => ProductGroups::find()
        ->select('product_group_id, name, attribute_groups')
        ->all(),

      'items' => \app\models\site\UploadColumns::find()->all(),
    ]);
  }

  public function actionComplaints()
  {

    $array_of_available = ['username', 'display_name', 'user_id', 'target_id'];
    $where = '';

    foreach ($_GET as $k => $v) {
      if (!in_array($k, $array_of_available)) continue;
      if (!empty($v)) $where .= (!empty($where) ? ' AND ' : '') . 'complaints.' . $k . '="' . $v . '"';
    }

    $items = Complaints::find()
      ->with('target', 'user')
      ->where(!empty($where) ? $where : '')
      ->all();

    return $this->render('users/complaints', [
      'complaints' => $items
    ]);
  }

  public function actionMessages()
  {

    $array_of_available = ['username', 'display_name', 'receiver_id', 'sender_id'];
    $where = '';

    foreach ($_GET as $k => $v) {
      if (!in_array($k, $array_of_available)) continue;

      if (!empty($v)) {
        if ($k == 'sender_id' || $k == 'receiver_id') {
          $where .= (!empty($where) ? ' AND ' : '') . 'notification_dialogs.' . $k . '="' . $v . '"';
        } else {
          $where .= (!empty($where) ? ' AND ' : '') . 'users.' . $k . ' LIKE "%' . $v . '%"';
        }
      }
    }

    $messages = NotificationDialogs::find()
      ->select('notification_dialogs.*, users.id AS user_id')
      ->innerJoin('users', 'users.id=notification_dialogs.receiver_id')
      ->where('(sender_id = ' . Yii::$app->user->identity->id . ' OR receiver_id = ' . Yii::$app->user->identity->id . ')' .
        (!empty($where) ? ' AND ' . $where : ''))
      ->orderBy('date_updated DESC')
      ->all();

    return $this->render('users/dialogs', [
      'dialogs' => $messages,
      'dialogs_num' => sizeof($messages)
    ]);
  }

  public function actionDialog($url)
  {

    $dialog = NotificationDialogs::find()->where('id = ' . $url . ' AND (sender_id = ' . Yii::$app->user->identity->id . ' OR receiver_id = ' . Yii::$app->user->identity->id . ')')
      ->one();

    return $this->render('users/dialog', [
      'dialog' => $dialog
    ]);
  }

  public function actionSmtp()
  {

    return $this->render('configs/smtp', [
      'configs' => SMTP::find()->all()
    ]);
  }

  public function actionSchedule()
  {

    if (!ConfigSchedule::find()->count()) {
      $configs = new ConfigSchedule();
      $configs->token = 'Rh0yN@,R0v@yl@';
      $configs->curs_schedule = 1;
      $configs->request_schedule = 1;
      $configs->curs_schedule_rate = 24;
      $configs->request_schedule_rate = 168;
      $configs->save();
    }

    return $this->render('configs/schedule', [
      'configs' => ConfigSchedule::find()->all()
    ]);
  }

  public function actionCategories()
  {

    $page = Yii::$app->request->get('page');
    $offset = (int)$page ? (((int)$page - 1) * 30) : '0';
    $categories = [];

    if (Yii::$app->request->get('name')) {
      $categories = Categories::find()
        ->where('LOWER(name) LIKE "%' . mb_strtolower(Yii::$app->request->get('name')) . '%"')
        ->orderBy('name ASC')->offset($offset)->limit(30)
        ->all();
    } else {
      $categories = Categories::find()->orderBy('name ASC')
        ->offset($offset)
        ->limit(30)
        ->all();
    }

    return $this->render('params/categories', [
      'items' => $categories,
      'attribute_groups' => CategoryAttributeGroups::find()
        ->with('attributesArray')
        ->all(),
      'catalog' => CatalogCategories::find()
        ->where('parent_id IS NULL')
        ->with('children')
        ->all(),
      'pagination' => new Pagination(
        [
          'totalCount' => Yii::$app->db
            ->createCommand('SELECT COUNT(*) FROM product_categories ' .
            (
              Yii::$app->request->get('name') ?
					    'WHERE LOWER(name) LIKE "%' . mb_strtolower(Yii::$app->request->get('name')) . '%"' :
              '')
            )
            ->queryScalar(),
          'route' => '/controlpanel/dashboard/categories',
          'pageSize' => 30
        ]
      ),
    ]);
  }

  public function actionProducts()
  {

    $query = $this->createSearchQuery($_GET, ['make', 'model', 'category', 'available', 'generation', 'year', 'status', 'city', 'user_id']);
    if (empty($query) && Yii::$app->request->get('status') !== '5') $query = 'status=' . Products::STATE_UNCHECKED;

    $items = Products::find()->where($query)->all();

    $params = [
      'products' => $items,
      'option_make' => Lists::getOptionMakeList()['options_make'],
      'option_name' => Lists::getOptionCategoryList()['options_category'],
      'select_year' => Lists::getOptionYearList(),
      'select_city' => Lists::getOptionCityList()
    ];

    $params = array_merge(Lists::getOptionAttributeList('', [], true), $params);
    $params['option_model'] = Lists::getOptionModelList(Yii::$app->request->get('make_id'))['options_model'];
    $params['gen_list'] = Lists::getOptionGenerationlList(Yii::$app->request->get('model_id'))['options_generation'];

    return $this->render('products/products', $params);
  }

  public function actionRequests()
  {

    $query = $this->createSearchQuery($_GET, ['make', 'model', 'generation', 'available', 'year', 'fuel', 'engine_volume', 'body', 'transmission', 'status', 'city', 'user_id']);
    $items = Requests::find()->where($query)->all();

    $params = [
      'option_make' => Lists::getOptionMakeList()['options_make'],
      'option_name' => Lists::getOptionCategoryList()['options_category'],
      'select_year' => Lists::getOptionYearList(),
      'select_city' => Lists::getOptionCityList(),
      'products' => $items
    ];

    $params = array_merge(Lists::getOptionAttributeList('', [], true), $params);
    $params['option_model'] = Lists::getOptionModelList(Yii::$app->request->get('make_id'))['options_model'];
    $params['gen_list'] = Lists::getOptionGenerationlList(Yii::$app->request->get('model_id'))['options_generation'];

    return $this->render('products/requests', $params);
  }

  public function actionAttributegroups()
  {

    return $this->render('params/attribute_groups', [
      'items' => CategoryAttributeGroups::find()->orderBy('name')->all(),
    ]);
  }

  public function actionMakegroups()
  {
    if (Yii::$app->user->isGuest && $this->action->id != 'login') {
      return $this->redirect(['/controlpanel/dashboard/login']);
    }

    if (!Yii::$app->user->identity->isAdmin() && $this->action->id != 'login') {
      return $this->redirect(['/controlpanel/dashboard/login']);
    }

    $groups = false;

    if (Yii::$app->request->get('name')) {
      $groups = MakeGroups::find()
        ->with('makes')
        ->where('LOWER(name) LIKE "%' . mb_strtolower(Yii::$app->request->get('name')) . '%"')
        ->orderBy('name')
        ->all();
    } else {
      $groups = MakeGroups::find()
        ->with('makes')
        ->orderBy('name')
        ->all();
    }

    return $this->render('params/make_groups', [
      'groups' => $groups
    ]);
  }

  public function actionProductgroups()
  {

    return $this->render('params/product_groups', [
      'items' => ProductGroups::find()->orderBy('name')->all(),
      'categories' => Categories::find()->orderBy('name')->all(),
      'make_groups' => MakeGroups::find()->orderBy('name')->all(),
      'attribute_groups' => CategoryAttributeGroups::find()->orderBy('name')->all(),
    ]);
  }

  public function actionProductgroupstemp()
  {

    return $this->render('site/catalog_page_templates', [
      'items' => ProductGroups::find()
        ->with('seoTemplates')
        ->orderBy('name')
        ->all(),

      'product_attributes' => CategoryAttributeGroups::find()
        ->all()
    ]);
  }

  public function actionCities()
  {

    $page = Yii::$app->request->get('page');
    $offset = (int)$page ? (((int)$page - 1) * 30) : '0';

    return $this->render('site/cities', [
      'items' => Countries::find()
        ->with('cities')
        ->orderBy('name')
        ->offset($offset)
        ->limit(30)
        ->all(),

      'pagination' => new Pagination(
        [
          'totalCount' => Yii::$app->db
            ->createCommand('SELECT COUNT(*) FROM countries')
            ->queryScalar(),

          'route' => '/controlpanel/dashboard/cities',
          'pageSize' => 30
        ]
      ),
    ]);
  }

  public function actionRobots()
  {

    $page = Yii::$app->request->get('page');
    $offset = (int)$page ? (((int)$page - 1) * 30) : '0';

    return $this->render('site/robots', [
      'items' => Robots::find()
        ->offset($offset)
        ->limit(30)
        ->all(),

      'pagination' => new Pagination(
        [
          'totalCount' => Yii::$app->db
            ->createCommand('SELECT COUNT(*) FROM config_robots')
            ->queryScalar(),

          'route' => '/controlpanel/dashboard/robots',
          'pageSize' => 30
        ]
      ),
    ]);
  }

  public function actionPages()
  {

    $page = Yii::$app->request->get('page');
    $offset = (int)$page ? (((int)$page - 1) * 30) : '0';
    $url = Yii::$app->request->get('url');

    if ($url) {
      $url = PageUtils::getPageUrl($url);
    }

    return $this->render('site/pages', [
      'items' => Pages::find()
        ->where('relative=0 AND informational=0' . ($url ? ' AND url="' . $url . '"' : ''))
        ->orderBy('name')
        ->offset($offset)
        ->limit(30)
        ->all(),

      'pagination' => new Pagination(
        [
          'totalCount' => Yii::$app->db
            ->createCommand('SELECT COUNT(*) FROM pages WHERE relative=0 AND informational=0' . ($url ? ' AND url="' . $url . '"' : ''))
            ->queryScalar(),

          'route' => '/controlpanel/dashboard/pages',
          'pageSize' => 30
        ]
      ),
    ]);
  }

  public function actionPostpages()
  {

    $page = Yii::$app->request->get('page');
    $offset = (int)$page ? (((int)$page - 1) * 30) : '0';

    $url = Yii::$app->request->get('url');

    if ($url) {
      $url = PageUtils::getPageUrl($url);
    }

    return $this->render('site/post_pages', [
      'items' => Pages::find()
        ->where('relative=0 AND informational=1' . ($url ? ' AND url="' . $url . '"' : ''))
        ->orderBy('name')
        ->offset($offset)
        ->limit(30)
        ->all(),

      'groups' => ProductGroups::find()->orderBy('name')->all(),
      'pagination' => new Pagination(
        [
          'totalCount' => Yii::$app->db
            ->createCommand('SELECT COUNT(*) FROM pages WHERE relative=0 AND informational=1' . ($url ? ' AND url="' . $url . '"' : ''))
            ->queryScalar(),

          'route' => '/controlpanel/dashboard/postpages',
          'pageSize' => 30
        ]
      ),
    ]);
  }

  public function actionCatalogpages()
  {

    $page = Yii::$app->request->get('page');
    $offset = (int)$page ? (((int)$page - 1) * 30) : '0';

    $url = Yii::$app->request->get('url');

    if ($url) {
      $url = PageUtils::getPageUrl($url);
    }

    return $this->render('site/catalog_pages', [
      'items' => Pages::find()
        ->where('type="' . Pages::PAGE_TYPE_CATALOG . '"' . ($url ? ' AND url="' . $url . '"' : ''))
        ->offset($offset)
        ->limit(30)
        ->all(),

      'pagination' => new Pagination(
        [
          'totalCount' => Yii::$app->db
            ->createCommand('SELECT COUNT(*) FROM pages WHERE type="' . Pages::PAGE_TYPE_CATALOG . '"' . ($url ? ' AND url="' . $url . '"' : ''))
            ->queryScalar(),

          'route' => '/controlpanel/dashboard/catalogpages',
          'pageSize' => 30
        ]
      ),
    ]);
  }

  public function actionCategorypages()
  {

    $url = Yii::$app->request->get('url');

    if ($url) {
      $url = PageUtils::getPageUrl($url);
    }

    $page = Yii::$app->request->get('page');
    $offset = (int)$page ? (((int)$page - 1) * 30) : '0';

    return $this->render('site/catalog_pages', [
      'items' => Pages::find()
        ->where('type="' . Pages::PAGE_TYPE_CATEGORY . '"' . ($url ? ' AND url="' . $url . '"' : ''))
        ->offset($offset)
        ->limit(30)
        ->all(),

      'pagination' => new Pagination(
        [
          'totalCount' => Yii::$app->db
            ->createCommand('SELECT COUNT(*) FROM pages WHERE type="' . Pages::PAGE_TYPE_CATALOG . '"' . ($url ? ' AND url="' . $url . '"' : ''))
            ->queryScalar(),

          'route' => '/controlpanel/dashboard/categorypages',
          'pageSize' => 30
        ]
      ),
    ]);
  }

  public function actionSupport()
  {
    $support = new SupportComponent();

    return $this->render('site/support', [
      'items' => $support->getSupportData()
    ]);
  }

  public function actionMenus()
  {

    $items = Menus::find()->all();

    return $this->render('site/menus', [
      'items' => $items
    ]);
  }

  public function actionLogin()
  {
    if (!Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin()) {
      return $this->redirect(['/controlpanel/dashboard']);
    }

    $this->layout = 'empty';

    $_admin = User::findOne(['user_role' => 'admin']);

    if (!$_admin) {
      return $this->redirect(['/controlpanel/dashboard/register']);
    }

    $model = new LoginForm();

    if (Yii::$app->request->isPost) {
      if ($model->load(Yii::$app->request->post()) && $model->login()) {
        return $this->redirect(['/controlpanel/dashboard']);
      }
    }

    return $this->render('login', [
      'model' => $model
    ]);
  }

  public function actionRegister()
  {
    if (!Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin()) {
      return $this->redirect(['/controlpanel/dashboard']);
    }

    $_admin = User::findOne(['user_role' => 'admin']);

    if ($_admin) {
      return $this->redirect(['/controlpanel/dashboard/login']);
    }

    $model = new RegisterForm();

    if (Yii::$app->request->isPost) {
      if ($model->load(Yii::$app->request->post()) && $model->register()) {
        return $this->redirect(['/controlpanel/dashboard']);
      }
    }


    $this->layout = 'empty';

    return $this->render('register', [
      'model' => $model
    ]);
  }

  protected function createSearchQuery($get, $array_of_available)
  {
    $where = '';

    foreach ($_GET as $k => $v) {
      if (!in_array($k, $array_of_available)) continue;

      if (!empty($v) || (int)$v === 0) {
        if ($k != 'status') {
          $where .= (!empty($where) ? ' AND ' : '') . $k . '="' . $v . '"';
        } else {
          if ($v === '5') continue;
          $where .= (!empty($where) ? ' AND ' : '') . $k . '="' . $v . '"';
        }
      }
    }

    return $where;
  }
}
