<?php

namespace app\controllers;

use Yii;

use app\models\User;

use app\models\Products;
use app\models\user\ProfileStatistics;
use app\models\products\ProductGroups;
use app\models\helpers\Lists;
use yii\data\Pagination;

class CabinetController extends \yii\web\Controller
{

  private $arViewVars = array();
  public $layout = '@app/views/layouts/company';

  public function init()
  {
    parent::init();
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

  public function actionProducts($url, $group_url = '')
  {

    $user = $this->checkUser($url);

    $id = ProductGroups::find()
      ->where(['url' => $group_url])
      ->one();

    $data = $id ? PersonalController::getProductPageData($id, false) : [];

    if (!$id) {
      $data['params'] = Lists::getOptions([], false, Yii::$app->request->get());
    }

    $model = new Products();
    $where = [];
    $attr_where = [];

    foreach (Yii::$app->request->get() as $key => $get) {
      if (!$get) continue;
      if ($model->hasAttribute($key) && $key != 'id') $where[$key] = $get;

      if (strpos($key, 'attribute_') !== false) {
        $attr_where[] = ['like', 'attributes_list', '%' . $get . '%', false];
      }
    }

    if (!isset($data['error']) || !$data['error']) {
      $orderby = [];

      $session = Yii::$app->session;
      $sorting_date = $session->get('sorting_date');
      $sorting_price = $session->get('sorting_price');

      if ($sorting_date == 1) {
        $orderby['date_created'] = SORT_DESC;
      } elseif ($sorting_date == 2) {
        $orderby['date_created'] = SORT_ASC;
      }

      if ($sorting_price == 3) {
        $orderby['price'] = SORT_DESC;
      } elseif ($sorting_price == 4) {
        $orderby['price'] = SORT_ASC;
      }

      if (!$orderby) $orderby['date_created'] = 'DESC';

      if ($id) $product_group_id = $data['product_group_id'];
      $page = Yii::$app->request->get('page');
      $offset = (int)$page ? (((int)$page - 1) * 20) : '0';

      $data['products'] = Products::find()
        ->where(array_merge(['and', array_merge(
          ['user_id' => $user->id, 'status' => Products::STATE_ACTIVE], $id ? ['group_id' => $product_group_id] : [], $where
        )], $attr_where))
        ->limit(20)
        ->offset($offset)
        ->orderBy($orderby)
        ->with('attributesArray')
        ->all();

      $data['pagination'] = new Pagination(['totalCount' => Products::find()
        ->where(array_merge(['and', array_merge(['user_id' => $user->id, 'status' => Products::STATE_ACTIVE], $id ? ['group_id' => $product_group_id] : [], $where)], $attr_where))
        ->count()
      ]);
    }

    if ($id) {
      Yii::$app->view->params['breadcrumbs'][] = ['label' => $data['product_group']->name, 'url' => ''];
    }

    if (!Yii::$app->request->get('partial')) {
      return $this->render('zaps', $data);
    } else {
      $r = '';

      foreach ($data['products'] as $product) {
        $r .= '<div class="row">' .
          $this->renderPartial('//layouts/parts/product.php', [
            'product' => $product,
            'product_group' => $data['product_group'],
            'favourites' => isset($data['favourites']) ? $data['favourites'] : [],
            'attributes' => $data['params']['attributes'],
          ]) .
          '</div>';
      }

      $pagination = $this->renderPartial('//layouts/parts/pagination',
        ['pagination' => $data['pagination'], 'container' => 'products']
      );

      return json_encode(['items' => $r, 'pagination' => $pagination]);
    }
  }

  public function actionFeedbacks($url)
  {
    $user = $this->checkUser($url);

    $page = Yii::$app->request->get('page');
    $offset = (int)$page ? ' OFFSET ' . ($page * 20) : '';

    $feedbacks = \app\models\user\Rates::find()
      ->with(['comments', 'sender'])
      ->where(['receiver_id' => $user->id])
      ->offset($offset)
      ->limit(20)
      ->all();

    Yii::$app->view->params['breadcrumbs'][] = ['label' => 'Отзывы', 'url' => ''];

    return $this->render('otz', [
      'user' => $user,
      'feedbacks' => $feedbacks,
      'pagination' => new Pagination(['totalCount' => Yii::$app->db->createCommand('SELECT COUNT(*) FROM user_rates WHERE sender_id=' . $user->id)->queryScalar()]),
    ]);
  }

  public function actionAddfeedback($url) {
    if (Yii::$app->user->isGuest) {
      $this->redirect('/vhod');
    }

    $user = $this->checkUser($url);

    Yii::$app->view->params['breadcrumbs'][] = ['label' => 'Отзывы', 'url' => $user->getUrlWithPath('feedbacks')];
    Yii::$app->view->params['breadcrumbs'][] = ['label' => 'Новый отзыв', 'url' => ''];

    return $this->render('new_feed', [
      'user' => $user,
    ]);
  }

  public function actionProfile($url)
  {
    $user = $this->checkUser($url);

    if (Yii::$app->user->isGuest || $user->id != Yii::$app->user->identity->id) {
      $date = date('Y-m-d');

      $statistics = ProfileStatistics::find()->where('user_id=' . $user->id .
        ' AND date="' . $date . '"')->one();

      if (!$statistics) {
        $statistics = new ProfileStatistics();
        $statistics->user_id = $user->id;
        $statistics->date = $date;
        $statistics->views = 0;
      }

      $statistics->views++;
      $statistics->save();
    }

    Yii::$app->view->params['breadcrumbs'][] = ['label' => 'О продавце', 'url' => ''];

    return $this->render('index', [
      'user' => $user,
      'id' => $url
    ]);
  }

  public function actionWaranty($url)
  {
    $user = $this->checkUser($url);

    Yii::$app->view->params['breadcrumbs'][] = ['label' => 'Гарантия', 'url' => ''];

    return $this->render('waranty', [
      'user' => $user,
      'id' => $url
    ]);
  }

  public function actionDelivery($url)
  {
    $user = $this->checkUser($url);

    Yii::$app->view->params['breadcrumbs'][] = ['label' => 'Доставка', 'url' => ''];

    return $this->render('delivery', [
      'user' => $user,
      'id' => $url
    ]);
  }

  public function actionPayment($url)
  {
    $user = $this->checkUser($url);

    Yii::$app->view->params['breadcrumbs'][] = ['label' => 'Оплата', 'url' => ''];

    return $this->render('payment', [
      'user' => $user,
      'id' => $url
    ]);
  }

  public function checkUser($url) {
    $user = User::find()
      ->with('config')
      ->where(['id' => $url])
      ->one();

    if (!$user) {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }

    $config = $user->config;

    if (!$config) {
      $config = new \app\models\user\Configs();
      $config->user_id = $user->id;
      $config->save();
    }

    Yii::$app->view->params['user'] = $user;
    Yii::$app->view->params['user_id'] = $user->id;

    if ($config->template == 'template-2') {
      $this->layout = '@app/views/layouts/company_2';
    } else if ($config->template == 'template-3') {
      $this->layout = '@app/views/layouts/company_3';
    }

    Yii::$app->view->params['breadcrumbs'] = [
      ['label' => 'Главная', 'url' => '/'],
      ['label' => 'Кабинет', 'url' => $user->url]
    ];

    return $user;
  }

  public function beforeAction($action)
  {
    \app\models\helpers\PageUtils::getMenus();

    Yii::$app->view->params['product_groups'] = ProductGroups::find()->cache(3600)->all();

    $this->view->params['page_name'] = '';
    $this->view->params['page_content'] = '';

    $host = explode('.', Yii::$app->request->hostName);

    if (sizeof($host) >= 2) {
      Yii::$app->view->params['site_city'] = \app\models\Cities::find()
        ->where(['domain' => $host[0]])
        ->one();
    }

    return parent::beforeAction($action);
  }
}
