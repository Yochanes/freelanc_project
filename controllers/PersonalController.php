<?php

namespace app\controllers;

use app\models\helpers\JSONData;
use app\models\helpers\PageUtils;
use app\models\user\NotificationDialogMessages;
use Yii;

use app\models\User;
use app\models\Cities;
use app\models\Products;
use app\models\Requests;

use app\models\user\Cars;
use app\models\user\Dialogs;
use app\models\user\DialogMessages;
use app\models\user\NotificationDialogs;
use app\models\user\Rates;
use app\models\user\Statistics;
use app\models\user\ProfileStatistics;

use app\models\makes\Makes;
use app\models\products\ProductGroups;
use app\models\products\Categories;
use app\models\products\CatalogCategories;

use app\models\forms\CabinetForm;
use app\models\forms\CarForm;

use app\models\helpers\Lists;

use yii\data\Pagination;
use yii\web\UploadedFile;

class PersonalController extends \yii\web\Controller
{

  private $arViewVars = array();
  public $layout = '@app/views/layouts/cabinet';

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

  public function actionFeedbacksawait()
  {
    if (Yii::$app->user->isGuest) {
      return $this->redirect(['/vhod']);
    }

    $ids = Yii::$app->user->identity->await;

    $prod_ids = '';

    foreach ($ids as $id) {
      $prod_ids .= ',' . $id->obj_id;
    }

    $data = ['groups' => ProductGroups::find()->orderBy('sort_order ASC')->all()];
    $pg = Yii::$app->request->get('page');
    $offset = (int)$pg ? (((int)$pg - 1) * 20) : '0';
    $products = array();
    $data['params'] = Lists::getOptions([], false, Yii::$app->request->get());

    if ($prod_ids) {
      $products = Products::find()
        ->with(['user', 'contacts', 'attributesArray'])
        ->where('id IN (' . substr($prod_ids, 1) . ')')
        ->limit(20)
        ->offset($offset)
        ->orderBy('date_created DESC')
        ->all();
    }

    $data = array_merge($data, [
      'products' => $products,
      'favourites' => Yii::$app->db->createCommand('SELECT product_id FROM user_favourites')->queryAll()
    ]);

    Yii::$app->view->params['breadcrumbs'][] = ['label' => 'Ждут ваш отзыв', 'url' => ''];
    return $this->render('reviews/waited', $data);
  }

  public function actionIndex($url = '')
  {
    if (Yii::$app->user->isGuest) {
      return $this->redirect(['/vhod']);
    }

    return $this->render('index');
  }

  public function actionCurs()
  {

    if (Yii::$app->request->isPost) {
      $model = new \app\models\forms\CursForm();
      $model->load(Yii::$app->request->post(), '');
      $result = $model->saveData();
      $result['errors'] = $model->getErrors();
      return json_encode($result);
    }

    Yii::$app->view->params['breadcrumbs'][] = ['label' => 'Курс', 'url' => ''];

    return $this->render('ad/curs',
      ['groups' => ProductGroups::find()->orderBy('sort_order ASC')->all()]
    );
  }

  public function actionMycars()
  {
    if (Yii::$app->user->isGuest) {
      return $this->redirect(['/vhod']);
    }

    if (Yii::$app->request->isPost) {
      $model = new CarForm();
      $json = array();

      if ($model->load(Yii::$app->request->post(), '')) {
        $result = $model->saveData();

        if (!$result['validated']) {
          $json['error'] = 'Не все поля заполнены верно';
          $json['errors'] = $model->errors;
        } else {
          $json['errors'] = isset($result['errors']) && is_array($result['errors']) ? array_merge($model->errors, $result['errors']) : $model->errors;
          if (isset($result['image_updated'])) $json['image_updated'] = $result['image_updated'];
          $json['success'] = true;
        }
      } else {
        $json['error'] = 'Ошибка выполнения запроса';
      }

      return json_encode($json);
    }

    $items = Yii::$app->db
      ->createCommand('SELECT product_category_attribute_groups.filter_name, 
				product_category_attributes.value, product_category_attributes.url FROM product_category_attribute_groups
				LEFT JOIN product_category_attributes ON product_category_attributes.attribute_group_id = product_category_attribute_groups.attribute_group_id 
				WHERE product_category_attribute_groups.use_in_car_form=1 ORDER BY product_category_attribute_groups.sort_order ASC')
      ->cache(3600)
      ->queryAll();

    $group = Yii::$app->db
      ->createCommand('SELECT product_group_id, url, is_default FROM product_groups WHERE use_in_car_form=1')
      ->queryOne();

    $data = [
      'attributes' => $items,
      'group' => $group,
      'group_id' => $group ? $group['product_group_id'] : false,
      'cars' => Cars::find()
        ->with('attributesArray')
        ->where(['user_id' => Yii::$app->user->identity->id])
        ->all(),
    ];

    Yii::$app->view->params['breadcrumbs'][] = ['label' => 'Мои машины', 'url' => ''];
    return $this->render('cars/index', $data);
  }

  public function actionRequests($url = '')
  {
    if (Yii::$app->user->isGuest) {
      return $this->redirect(['/vhod']);
    }

    $pg = Yii::$app->request->get('page');
    $offset = (int)$pg ? (((int)$pg - 1) * 20) : '0';

    $pg = Yii::$app->request->get('page');
    $offset = (int)$pg ? (((int)$pg - 1) * 20) : '0';

    $group = false;
    $groups_arr = ProductGroups::find()->orderBy('sort_order ASC')->all();
    $groups = array();

    foreach ($groups_arr as $group) {
      $groups[$group['product_group_id']] = $group;
    }

    Yii::$app->view->params['breadcrumbs'][] = ['label' => 'Мои запросы', 'url' => ''];

    $add_where = '';

    if (Yii::$app->request->get('sort') == 1) {
      $date_offset = date("Y-m-d", strtotime('-7 days'));
      $add_where = ' AND date_updated>="' . $date_offset . '"';
    } else if (Yii::$app->request->get('sort') == 2) {
      $date_offset = date("Y-m-d", strtotime('-7 days'));
      $add_where = ' AND date_updated<"' . $date_offset . '"';
    }

    return $this->render('requests/index', [
      'groups' => $groups,
      'params' => Lists::getOptions([]),
      'url' => $url,

      'requests' => Requests::find()
        ->where('user_id='.  Yii::$app->user->identity->id . $add_where)
        ->limit(20)
        ->offset($offset)
        ->orderBy('date_created DESC')
        ->with(['contacts', 'attributesArray', 'country'])
        ->all(),

      'pagination' => new Pagination(
        ['totalCount' => Requests::find()
          ->where('user_id='.  Yii::$app->user->identity->id)
          ->count()
        ]
      ),
    ]);
  }

  public function actionRequestssearch($url = '')
  {
    if (Yii::$app->user->isGuest) {
      return $this->redirect(['/vhod']);
    }

    $pg = Yii::$app->request->get('page');
    $offset = (int)$pg ? (((int)$pg - 1) * 20) : '0';

    $where = [];
    $model = new Requests;

    $groups_arr = ProductGroups::find()->orderBy('sort_order ASC')->all();
    $groups = array();
    $params = [];
    $get_gr = Yii::$app->request->get('group');
    $get_group = false;

    foreach ($groups_arr as $group) {
      $groups[$group['product_group_id']] = $group;

      if ($get_gr == $group['url']) {
        $get_group = $group['product_group_id'];
      }
    }

    $user = Yii::$app->user->identity;
    Yii::$app->view->params['breadcrumbs'][] = ['label' => 'Полученные запросы', 'url' => ''];

    $config = $user->config;

    if (!$config) {
      $config = new \app\models\user\Configs();
      $config->user_id = $user->id;
      $config->save();
    }

    $group_config = $config->requests_product_groups;
    if (!$group_config) $group_config = [];
    $make_config = $config->requests_makes;
    if (!$make_config) $make_config = [];
    $data = [];
    $date_offset = date("Y-m-d", strtotime('-7 days'));

    if ($group_config && $make_config) {
      $grs = '';

      if (!$get_group && $group_config) {
        foreach ($group_config as $id) $grs .= $id . ',';
        $grs = substr($grs, 0, -1);
      } else {
        $grs = $get_group;
      }

      $mks = '';

      if ($make_config) {
        foreach ($make_config as $id) $mks .= '"' . $id . '",';
        $mks = substr($mks, 0, -1);
      }

      $data['requests'] = Requests::find()
        ->where('date_updated>="' . $date_offset . '" AND user_id!='.$user->id . ($grs ? ' AND group_id IN (' . $grs . ')' : '') . ($mks ? ' AND make IN (' . $mks . ')' : ''))
        ->limit(20)
        ->offset($offset)
        ->orderBy('date_created DESC')
        ->with(['contacts', 'attributesArray', 'country'])
        ->all();

      $data['pagination'] = new Pagination(
        ['totalCount' => Requests::find()
          ->where('date_updated>="' . $date_offset . '" AND user_id!='.$user->id . ($grs ? ' AND group_id IN (' . $grs . ')' : '') . ($mks ? ' AND make IN (' . $mks . ')' : ''))
          ->count()
        ]
      );
    } else {
      $data['requests'] = Requests::find()
        ->where('date_updated>="' . $date_offset . '" AND user_id!='.$user->id . ($get_group ? ' AND group_id=' . $get_group : ''))
        ->limit(20)
        ->offset($offset)
        ->orderBy('date_created DESC')
        ->with(['contacts', 'attributesArray', 'country'])
        ->all();

      $data['pagination'] = new Pagination(
        ['totalCount' => Requests::find()
          ->where('date_updated>="' . $date_offset . '" AND user_id!='.$user->id . ($get_group ? ' AND group_id=' . $get_group : ''))
          ->count()
        ]
      );
    }

    return $this->render('requests/requests-get', array_merge($params, [
      'groups' => $groups,
      'params' => Lists::getOptions([]),
    ], $data));
  }

  public function actionRequestsconfig()
  {

    if (Yii::$app->request->isPost) {
      $model = new \app\models\forms\RequestConfigForm();
      $model->load(Yii::$app->request->post(), '');
      $result = $model->saveData();
      $result['errors'] = $model->errors;
      return json_encode($result);
    }

    $groups_arr = ProductGroups::find()->orderBy('sort_order ASC')->all();
    $groups = array();

    foreach ($groups_arr as $group) {
      $groups[$group['product_group_id']] = $group;
    }

    Yii::$app->view->params['breadcrumbs'][] = ['label' => 'Настройка запросов', 'url' => ''];

    return $this->render('requests/settings', [
      'groups' => $groups,
      'makes' => Makes::find()->all()
    ]);
  }

  public function actionFavourites($url = '')
  {
    if (Yii::$app->user->isGuest) {
      return $this->redirect(['/vhod']);
    }

    $data = self::getProductPageData($url);

    if (!isset($data['error']) || !$data['error']) {
      $product_group_id = $data['product_group_id'];
      $ids_list = $this->getFavourites($product_group_id);
      $page = Yii::$app->request->get('page');
      $offset = (int)$page ? (((int)$page - 1) * 20) : '0';

      $orderby = 'date_created DESC';
      $sort = Yii::$app->session->get('sorting_personal_favourites');

      if ($sort == 11) {
        $orderby = 'byn_price ASC';
      } else if ($sort == 12) {
        $orderby = 'category ASC';
      } else if ($sort == 13) {
        $orderby = 'make ASC, model ASC';
      } else if ($sort == 14) {
        $orderby = 'views DESC';
      }

      $data['products'] = $ids_list ? Products::find()
        ->where(('id IN (' . $ids_list . ') AND group_id=' . $product_group_id))
        ->orderBy($orderby)
        ->limit(20)
        ->offset($offset)
        ->with('attributesArray', 'country')
        ->all() : [];

      $data['favourites'] = [];
      foreach ($data['products'] as $p) $data['favourites'][] = $p->id;
      Yii::$app->view->params['breadcrumbs'][] = ['label' => 'Избранные', 'url' => ''];

      $data['pagination'] = new Pagination([
        'totalCount' =>
          $ids_list ? Yii::$app->db
            ->createCommand('SELECT COUNT(*) FROM products WHERE id IN (' . $ids_list . ') AND group_id=' . $product_group_id)
            ->queryScalar() : 0
      ]);
    }

    return $this->render('favorite/index', $data);
  }

  public function actionFavouritesellers()
  {
    if (Yii::$app->user->isGuest) {
      return $this->redirect(['/vhod']);
    }

    $data = [];
    $ids_list = $this->getFavouriteSellers();
    $page = Yii::$app->request->get('page');
    $offset = (int)$page ? (((int)$page - 1) * 20) : '0';

    $data['sellers'] = $ids_list ? User::find()
      ->where(('id IN (' . $ids_list . ')'))
      ->limit(20)
      ->offset($offset)
      ->all() : [];

    Yii::$app->view->params['breadcrumbs'][] = ['label' => 'Избранные продавцы', 'url' => ''];

    $data['pagination'] = new Pagination([
      'totalCount' =>
        $ids_list ? Yii::$app->db
          ->createCommand('SELECT COUNT(*) FROM users WHERE id IN (' . $ids_list . ')')
          ->queryScalar() : 0
    ]);

    return $this->render('favorite/sellers', $data);
  }

  /*
  public function actionSearch()
  {
    if (Yii::$app->user->isGuest) {
      return $this->redirect(['/vhod']);
    }

    $seaches_arr = Searches::find()->where(['user_id' => Yii::$app->user->identity->id])->all();

    return $this->render('favorite/find-h', [
      'searches' => $seaches_arr
    ]);
  }
  */

  public function actionNotifications()
  {
    if (Yii::$app->user->isGuest) {
      return $this->redirect(['/vhod']);
    }

    $messages = NotificationDialogs::find()
      ->with('lastMessage')
      ->where('sender_id = ' . Yii::$app->user->identity->id . ' OR receiver_id = ' . Yii::$app->user->identity->id)
      ->orderBy('date_updated DESC')
      ->all();

    Yii::$app->view->params['breadcrumbs'][] = ['label' => 'Уведомления', 'url' => ''];

    return $this->render('notifications/index', [
      'dialogs' => $messages,
      'dialogs_num' => sizeof($messages),
    ]);
  }

  public function actionNotificationsettings()
  {
    if (Yii::$app->user->isGuest) {
      return $this->redirect(['/vhod']);
    }

    $user = Yii::$app->user->identity;

    if (Yii::$app->request->isPost) {
      $configs = $user->config;

      foreach (Yii::$app->request->post() as $k => $v) {
        if ($configs->hasAttribute($k)) {
          $configs->{$k} = $v;
        }
      }

      if ($configs->save()) {
        return json_encode(['success' => true]);
      } else {
        return json_encode(['error' => true, 'errors' => json_encode($configs->errors)]);
      }
    }

    Yii::$app->view->params['breadcrumbs'][] = ['label' => 'Настройки уведомлений', 'url' => ''];
    return $this->render('notifications/settings', []);
  }

  public function actionSupport()
  {
    if (Yii::$app->user->isGuest) {
      return $this->redirect(['/vhod']);
    }

    $items = \app\models\site\SupportCategory::find()
      ->orderBy('sort_order ASC')
      ->all();

    Yii::$app->view->params['breadcrumbs'][] = ['label' => 'Слежба поддержки', 'url' => ''];
    return $this->render('notifications/support', [
      'items' => $items
    ]);
  }

  public function actionMessages()
  {
    if (Yii::$app->user->isGuest) {
      return $this->redirect(['/vhod']);
    }

    $text = Yii::$app->request->get('text');
    $read = (int)Yii::$app->request->get('read') === 0;
    if ($read) $read = isset($_GET['read']);

    $messages = false;
    $user = Yii::$app->user->identity;

    if ($text || $read) {
      if ($read && !$text) {
        $messages = Dialogs::find()
          ->with('lastMessage.item')
          ->joinWith(['lastMessage'])
          ->where('((dialogs.sender_id=' . $user->id . ') 
						OR (dialogs.receiver_id=' . $user->id . '))
						 AND CASE WHEN dialog_messages.receiver_id=' . $user->id . ' THEN dialog_messages.state=' . DialogMessages::STATE_UNREAD . ' END')
          ->limit(20)
          ->orderBy('dialogs.date_updated DESC')
          ->groupBy('dialogs.id')
          ->all();

        $all = Dialogs::find()
          ->with('lastMessage.item')
          ->joinWith(['lastMessage'])
          ->where('(dialogs.sender_id=' . $user->id . '
						OR dialogs.receiver_id=' . $user->id . ')
             AND CASE WHEN dialog_messages.receiver_id=' . $user->id . ' THEN dialog_messages.state=' . DialogMessages::STATE_UNREAD . ' END')
          ->count();
      } else if ($text) {
        $messages = Dialogs::find()
          ->with('lastMessage.item')
          ->joinWith(['lastMessage'])
          ->where('(dialogs.sender_id = ' . $user->id . ' 
						OR dialogs.receiver_id = ' . $user->id . ')
						AND (dialogs.item_search LIKE "%' . trim($text) . '%")
						' . ($read ? ' AND CASE WHEN dialog_messages.receiver_id=' . $user->id . ' THEN dialog_messages.state=' . DialogMessages::STATE_UNREAD . ' END' : '')
          )
          ->limit(20)
          ->orderBy('date_updated DESC')
          ->groupBy('dialogs.id')
          ->all();

        $all = Dialogs::find()
          ->with('lastMessage.item')
          ->joinWith(['lastMessage'])
          ->where('(dialogs.sender_id = ' . $user->id . ' 
						OR dialogs.receiver_id = ' . $user->id . ')
						AND (dialogs.item_search LIKE "%' . trim($text) . '%")
						' . ($read ? ' AND CASE WHEN dialog_messages.receiver_id=' . $user->id . ' THEN dialog_messages.state=' . DialogMessages::STATE_UNREAD . ' END' : '')
          )
          ->count();
      }
    } else {
      $messages = Dialogs::find()
        ->with(['lastMessage', 'lastMessage.item'])
        ->where('dialogs.sender_id=' . $user->id . ' OR dialogs.receiver_id=' . $user->id)
        ->limit(20)
        ->orderBy('date_updated DESC')
        ->groupBy('dialogs.id')
        ->all();

      $all = Dialogs::find()
        ->with(['lastMessage', 'lastMessage.item'])
        ->where('dialogs.sender_id=' . $user->id . ' OR dialogs.receiver_id=' . $user->id)
        ->count();
    }

    Yii::$app->view->params['breadcrumbs'][] = ['label' => 'Сообщения', 'url' => ''];

    return $this->render('messages/index', [
      'dialogs' => $messages,
      'all' => $all,
      'pagination' => new Pagination(['totalCount' => $all])
    ]);
  }

  /*
  public function actionDeletedmessages()
  {
    if (Yii::$app->user->isGuest) {
      return $this->redirect(['/vhod']);
    }

    $user = Yii::$app->user->identity;

    $messages = Dialogs::find()
      ->with('item')
      ->where('((sender_state=' . Dialogs::STATE_TO_DELETE .
        ' OR sender_state=' . Dialogs::STATE_DELETED_AND_LOCKED .
        ') AND sender_id=' . $user->id . ')
			    OR ((receiver_state=' . Dialogs::STATE_TO_DELETE .
        ' OR receiver_state=' . Dialogs::STATE_DELETED_AND_LOCKED .
        ') AND receiver_id=' . $user->id . ')')
      ->orderBy('date_updated')
      ->limit(20)
      ->all();

    $all = Dialogs::find()->where(
      '((sender_state=' . Dialogs::STATE_TO_DELETE .
      ' OR sender_state=' . Dialogs::STATE_DELETED_AND_LOCKED .
      ') AND sender_id=' . $user->id . ')
			    OR ((receiver_state=' . Dialogs::STATE_TO_DELETE .
      ' OR receiver_state=' . Dialogs::STATE_DELETED_AND_LOCKED .
      ') AND receiver_id=' . $user->id . ')')
      ->count();

    Yii::$app->view->params['breadcrumbs'][] = ['label' => 'Удаленные сообщения', 'url' => ''];

    return $this->render('messages/removed', [
      'dialogs' => $messages,
      'pagination' => new Pagination(['totalCount' => $all])
    ]);
  }
  */

  /*
  public function actionBlockedmessages()
  {
    if (Yii::$app->user->isGuest) {
      return $this->redirect(['/vhod']);
    }

    $user = Yii::$app->user->identity;

    $messages = Dialogs::find()
      ->with('item')
      ->where('(sender_state=' . Dialogs::STATE_LOCKED . ' AND sender_id=' . $user->id .
        ') OR (receiver_state=' . Dialogs::STATE_LOCKED . ' AND receiver_id=' . $user->id . ')')
      ->orderBy('date_updated')
      ->limit(20)
      ->all();

    $all = Dialogs::find()->where(
      '(sender_state=' . Dialogs::STATE_LOCKED . ' AND sender_id=' . $user->id .
      ') OR (receiver_state=' . Dialogs::STATE_LOCKED . ' AND receiver_id=' . $user->id . ')')
      ->orderBy('date_updated')
      ->count();

    Yii::$app->view->params['breadcrumbs'][] = ['label' => 'Заблокированные сообщения', 'url' => ''];

    return $this->render('messages/blocked', [
      'dialogs' => $messages,
      'pagination' => new Pagination(['totalCount' => $all])
    ]);
  }
*/

  public function actionNtfdialog($url)
  {
    if (Yii::$app->user->isGuest) {
      return $this->redirect(['/vhod']);
    }

    $user = Yii::$app->user->identity;

    $dialog = NotificationDialogs::find()->where('id=' . $url .
      ' AND (sender_id=' . $user->id . ' OR receiver_id=' . $user->id . ')')
      ->one();

    Yii::$app->view->params['breadcrumbs'][] = ['label' => 'Уведомления', 'url' => '/personal/notifications/'];

    return $this->render('notifications/dialog', [
      'dialog' => $dialog,
      'all' => NotificationDialogMessages::find()
        ->where('dialog_id=' . $url)
        ->count()
    ]);
  }

  public function actionDialog($url)
  {
    if (Yii::$app->user->isGuest) {
      return $this->redirect(['/vhod']);
    }

    $user = Yii::$app->user->identity;

    $dialog = Dialogs::find()
      ->with('item', 'sender', 'receiver')
      ->where('id=' . $url . ' AND (sender_id=' . $user->id . ' OR receiver_id=' . $user->id . ')')
      ->one();

    Yii::$app->view->params['breadcrumbs'][] = ['label' => 'Сообщения', 'url' => '/personal/messages/'];

    return $this->render('messages/dialog', [
      'dialog' => $dialog,
      'all' => DialogMessages::find()
        ->where('dialog_id=' . $url)
        ->count()
    ]);
  }

  public function actionFeedbacks()
  {
    if (Yii::$app->user->isGuest) {
      return $this->redirect(['/vhod']);
    }

    $page = Yii::$app->request->get('page');
    $offset = (int)$page ? ' OFFSET ' . ($page * 20) : '';

    $feedbacks = Rates::find()
      ->with(['comments', 'sender'])
      ->where(['receiver_id' => Yii::$app->user->identity->id])
      ->offset($offset)
      ->limit(20)
      ->all();

    Yii::$app->view->params['breadcrumbs'][] = ['label' => 'Отзывы', 'url' => ''];

    return $this->render('reviews/index', [
      'feedbacks' => $feedbacks,
      'pagination' => new Pagination(
        ['totalCount' => Yii::$app->db
          ->createCommand('SELECT COUNT(*) FROM user_rates WHERE sender_id=' . Yii::$app->user->identity->id)->queryScalar()]),
    ]);
  }

  public function actionFeedbacksmy()
  {
    if (Yii::$app->user->isGuest) {
      return $this->redirect(['/vhod']);
    }

    $page = Yii::$app->request->get('page');
    $offset = (int)$page ? ' OFFSET ' . ($page * 20) : '';

    $feedbacks = Rates::find()
      ->with(['comments', 'receiver'])
      ->where(['sender_id' => Yii::$app->user->identity->id])
      ->offset($offset)
      ->limit(20)
      ->all();

    Yii::$app->view->params['breadcrumbs'][] = ['label' => 'Мои отзывы', 'url' => ''];

    return $this->render('reviews/my_reviews', [
      'feedbacks' => $feedbacks,
      'pagination' => new Pagination(
        ['totalCount' => Yii::$app->db
          ->createCommand('SELECT COUNT(*) FROM user_rates WHERE sender_id=' . Yii::$app->user->identity->id)->queryScalar()]),
    ]);
  }

  public function actionRequestadd($url = '', $id = '')
  {
    if (Yii::$app->user->isGuest) {
      return $this->redirect(['/vhod']);
    }

    $product_group = false;

    if ($url) {
      $product_group = ProductGroups::find()->where(['url' => $url])->one();
    } else {
      $product_group = ProductGroups::find()->where('is_default=1')->one();
    }

    if (!$product_group) {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }

    $request = false;
    $attribute_values = [];

    if ($url) {
      $request = Requests::find()
        ->where(['id' => $id, 'user_id' => Yii::$app->user->identity->id])
        ->one();
    }

    if (!$request) {
      $request = new Requests();
      $request->load(Yii::$app->request->get(), '');

      foreach (Yii::$app->request->get() as $k => $v) {
        if (strpos($k, 'attribute_') !== false) $attribute_values[] = $v;
      }
    } else {
      $attribute_values = array_keys($request->attributeValues);
    }

    if (Yii::$app->request->isPost) {
      $model = new \app\models\forms\RequestForm();
      $model->imgs = UploadedFile::getInstancesByName('imgs');
      $model->load(Yii::$app->request->post(), '');
      $result = $model->saveData($product_group);
      $result['errors'] = $model->errors;
      return json_encode($result);
    }

    Yii::$app->view->params['breadcrumbs'][] = ['label' => 'Новый запрос', 'url' => ''];
    $user = Yii::$app->user->identity;

    return $this->render('requests/add', [
      'product_group' => $product_group,
      'params' => Lists::getOptions(
        [],
        $product_group,
        array_merge(
          $request->attributes,
          [
            'attrs' => $attribute_values,
            'no_empty_values' => false,
            'country' => $request->country_id ? $request->country_id : false
          ])
      ),
      'groups' => ProductGroups::find()->orderBy('sort_order ASC')->all(),
      'product' => $request,
    ]);
  }

  public function actionAddcar($url = '')
  {
    if (Yii::$app->user->isGuest) {
      return $this->redirect(['/vhod']);
    }

    $product_group = ProductGroups::find()
      ->where('use_in_car_form=1')
      ->one();

    if (Yii::$app->request->isPost) {
      $model = new CarForm();
      $model->load(Yii::$app->request->post(), '');
      $model->imgs = UploadedFile::getInstanceByName('imgs');
      $result = $model->saveData();
      $result['errors'] = $model->errors;
      return json_encode($result);
    }

    $car = false;
    $params = array();

    if (!empty($url)) {
      $car = Cars::findOne(['id' => $url, 'user_id' => Yii::$app->user->identity->id]);
    }

    $attribute_values = [];

    if (!$car) {
      $car = new Cars();
      $car->load(Yii::$app->request->get(), '');

      foreach (Yii::$app->request->get() as $k => $v) {
        if (strpos($k, 'attribute_') !== false) $attribute_values[] = $v;
      }
    } else {
      $attribute_values = array_keys($car->attributeValues);
    }

    Yii::$app->view->params['breadcrumbs'][] = ['label' => 'Добавление машины', 'url' => ''];
    $params['group'] = $product_group;
    $params['car'] = $car;
    $user = Yii::$app->user->identity;

    $params['params'] = Lists::getOptions(
      [],
      $product_group,
      array_merge($car->attributes, ['attrs' => $attribute_values, 'city' => $user->city, 'no_empty_values' => true])
    );

    return $this->render('cars/addcar', $params);
  }

  public function actionProducts($url = '')
  {
    if (Yii::$app->user->isGuest) {
      return $this->redirect(['/vhod']);
    }

    $orderby = 'date_created DESC';
    $sort = Yii::$app->session->get('sorting_personal_products' . (!empty($url) ? '_' . $url : ''));
    $user = Yii::$app->user->identity;

    if ($sort == 11) {
      $orderby = 'byn_price ASC';
    } else if ($sort == 12) {
      $orderby = 'category ASC';
    } else if ($sort == 13) {
      $orderby = 'make ASC, model ASC';
    } else if ($sort == 14) {
      $orderby = 'views DESC';
    } else if ($sort == 15) {
      $orderby = 'date_created ASC';
    } else if ($sort == 20) {
      // Yii::$app->response->redirect('/personal/products');
    }

    $page = Yii::$app->request->get('page');
    $offset = (int)$page ? (((int)$page - 1) * 20) : '0';
    $where = '';

    if ($sort == 16) {
      $where .= 'status="' . Products::STATE_ACTIVE . '"';
    } else if ($sort == 17) {
      $where .= 'status IN ("' . Products::STATE_INACTIVE . '","' . Products::STATE_DRAFT . '")';
    } else {
      $where .= 'status!="' . Products::STATE_DELETED . '"';
    }

    $attr_where = [];
    $model = new Products();

    foreach (Yii::$app->request->get() as $key => $get) {
      if (!$get) continue;

      if (strpos($key, 'attribute_') !== false) {
        $attr_where[] = ['like', 'attributes_list', '%' . $get . '%', false];
      } else if ($key === 'category') {
        $cat = Categories::find()
          ->select('synonym_url, connected_category_url')
          ->where('url="' . $get . '" OR synonym LIKE "%' . $get . '%"')
          ->one();

        $in = '"' . $get . '",';
        $section_list = '';

        if ($cat) {
          $syn = explode(';', $cat->synonym_url);

          foreach ($syn as $s) {
            if ($s && !empty($s)) {
              $in .= '"' . $s . '",';
            }
          }

          $conn = explode(';', $cat->connected_category_url);

          foreach ($conn as $s) {
            if ($s && !empty($s)) {
              $in .= '"' . $s . '",';
            }
          }
        } else {
          $cat_section = CatalogCategories::find()
            ->where('url="' . $get . '"')
            ->one();

          if ($cat_section) {
            $section_list .= '"' . $cat_section->id . '",';

            if ($cat_section->parent_id) {
              $cat_sections = CatalogCategories::find()
                ->where('(id="' . $cat_section->parent_id . " OR parent_id=" . $cat_section->parent_id . '") AND id!="' . $cat_section->id . '"')
                ->all();

              foreach ($cat_sections as $section) {
                $section_list .= '"' . $section->id . '",';
              }
            }
          }

          $section_list = substr($section_list, 0, -1);

          $cat_list = Categories::find()
            ->select('url, synonym_url, connected_category_url')
            ->where('catalog_category_id IN (' . $section_list . ')')
            ->all();

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
      } else if ($key == 'partnum' || $key == 'sku') {
        if ($key === 'partnum') {
          $val = preg_replace('/[^A-Za-z0-9\-]/', '', $get);
        } else {
          $val = $get;
        }

        $where .= ' AND `' . $key . '`="' . $val . '"';
      } else if ($model->hasAttribute($key) && $key != 'city' && $key != 'country') {
        $where .= ' AND ' . $key . '="' . $get . '"';
      } else if ($key == 'city' && $get != 'all') {
        $where .= ' AND city_domain="' . $get . '"';
      } else if ($key == 'country' && $get != 'all') {
        $where .= ' AND country_id="' . $get . '"';
      }
    }

    if (!empty($url)) {
      $data = self::getProductPageData($url, true, [], true);

      if (!isset($data['error']) || !$data['error']) {
        $group = $data['product_group'];
        $product_group_id = $data['product_group_id'];
        $cat_where = ($data['product_group']['product_categories'] == 'all' ? '' : 'category_id IN (' . $data['product_group']['product_categories'] . ')');
        $data['categories'] = [];

        if ($data['product_group']['product_categories']) {
          $data['categories'] = Categories::find()
            ->where($cat_where)
            ->cache(3600)
            ->all();

          $categories_file_update_needed = JSONData::updateCategoriesFileVersion();

          $data['categories_file_version'] = $categories_file_update_needed[1];

          if ($categories_file_update_needed[0]) {
            JSONData::createCategoriesJSON($categories_file_update_needed[1], $data['categories']);
          }
        }

        $data['total_active'] = Products::find()
          ->where([
            'group_id' => $product_group_id,
            'status' => Products::STATE_ACTIVE,
            'user_id' => $user->id
          ])
          ->count();

        $data['total_inactive'] = Products::find()
          ->where(
            'group_id=' . $product_group_id . ' AND (
            status=' . Products::STATE_INACTIVE . ' OR status=' . Products::STATE_DRAFT . ') AND user_id=' . $user->id
          )
          ->count();

        $data['products'] = Products::find()
          ->where($where)
          ->andWhere(
            array_merge(
              ['and',
                [
                  'user_id' => Yii::$app->user->identity->id,
                  'group_id' => $product_group_id
                ]
              ], $attr_where
            )
          )
          ->limit(20)
          ->offset($offset)
          ->orderBy($orderby)
          ->with('attributesArray', 'country')
          ->all();

        $data['pagination'] = new Pagination(['totalCount' => Products::find()
          ->where($where)
          ->andWhere(
            array_merge(
              ['and',
                [
                  'user_id' => Yii::$app->user->identity->id,
                  'group_id' => $product_group_id
                ]
              ], $attr_where
            )
          )
          ->count()
        ]);
      }

      Yii::$app->view->params['breadcrumbs'][] = [
        'label' => 'Мои объявления',
        'url' => '/personal/products/'
      ];

      Yii::$app->view->params['breadcrumbs'][] = [
        'label' => 'Мои ' . $group->name,
        'url' => '/personal/products/' . $group->url . '/'
      ];

      return $this->render('ad/index', $data);
    } else {
      $data['params'] = Lists::getOptions([], false, Yii::$app->request->get());
      $data['groups'] = ProductGroups::find()->orderBy('sort_order ASC')->all();
      Yii::$app->view->params['breadcrumbs'][] = ['label' => 'Мои объявления', 'url' => '/personal/products/'];

      $data['total_active'] = Products::find()
        ->where([
          'status' => Products::STATE_ACTIVE,
          'user_id' => $user->id
        ])
        ->count();

      $data['total_inactive'] = Products::find()
        ->where(
            '(status=' . Products::STATE_INACTIVE . ' OR status=' . Products::STATE_DRAFT . ') AND user_id=' . $user->id
        )
        ->count();

      $data['products'] = Products::find()
        ->where($where)
        ->andWhere(
          array_merge(
            ['and', ['user_id' => Yii::$app->user->identity->id]]
          )
        )
        ->limit(20)
        ->offset($offset)
        ->orderBy($orderby)
        ->with('attributesArray', 'country')
        ->all();

      $data['pagination'] = new Pagination(['totalCount' => Products::find()
        ->where($where)
        ->andWhere(
          array_merge(
            ['and', array_merge(['user_id' => Yii::$app->user->identity->id])]
          )
        )
        ->count()
      ]);

      return $this->render('ad/index_all', $data);
    }
  }

  public function actionSell($url = '', $id = '')
  {
    if (Yii::$app->user->isGuest) {
      return $this->redirect(['/vhod']);
    }

    if (Yii::$app->request->isPost) {
      Yii::$app->session->remove('draft');
      Yii::$app->session->remove('last_product_id');
      $model = new \app\models\forms\ProductSellForm();
      $model->imgs = UploadedFile::getInstancesByName('imgs');
      $model->load(Yii::$app->request->post(), '');
      $result = $model->saveData();
      $result['errors'] = $model->getErrors();
      return json_encode($result);
    }

    $product = false;

    if (!$id && Yii::$app->session->get('draft') && Yii::$app->session->get('last_product_id')) {
      $id = Yii::$app->session->get('last_product_id');
    } else if ($id) {
      Yii::$app->session->remove('draft');
    }

    if ($id) {
      $product = Products::find()
        ->where(['id' => $id, 'user_id' => Yii::$app->user->identity->id])
        ->one();
    } else {
      Yii::$app->session->set('draft', 1);
    }

    $user = Yii::$app->user->identity;
    $attribute_values = [];

    if (!$product) {
      $product = new Products();
      $product->load(Yii::$app->request->get(), '');

      foreach (Yii::$app->request->get() as $k => $v) {
        if (strpos($k, 'attribute_') !== false) $attribute_values[] = $v;
      }
    } else {
      $attribute_values = array_keys($product->attributeValues);
    }

    $data = self::getProductPageData($url, !$product->id,
      array_merge(
        $product->attributes,
        [
          'attrs' => $attribute_values,
          'no_empty_values' => true,
          'country' => $product->country_id ? $product->country_id : $user->country->id,
          'city' => $product->city ? $product->city : $user->city,
          'category' => $product->category_val
        ]
      ));

    $data['product'] = $product;

    if (!isset($data['error']) || !$data['error']) {
      $cat_where = ($data['product_group']['product_categories'] == 'all' ? '' : 'category_id IN (' . $data['product_group']['product_categories'] . ')');
      $data['categories'] = [];

      if ($data['product_group']['product_categories']) {
        $data['categories'] = Categories::find()
          ->where($cat_where)
          ->cache(3600)
          ->all();

        $categories_file_update_needed = JSONData::updateCategoriesFileVersion();

        $data['categories_file_version'] = $categories_file_update_needed[1];

        if ($categories_file_update_needed[0]) {
          JSONData::createCategoriesJSON($categories_file_update_needed[1], $data['categories']);
        }
      }
    }

    Yii::$app->view->params['breadcrumbs'][] = ['label' => 'Новое объявление', 'url' => ''];
    return $this->render('ad/add', $data);
  }

  public function actionWallet()
  {
    if (Yii::$app->user->isGuest) {
      return $this->redirect(['/vhod']);
    }

    Yii::$app->view->params['breadcrumbs'][] = ['label' => 'Кошелек', 'url' => ''];

    return $this->render('wallet', [

    ]);
  }

  public function actionUploadparams($url) {
    if (Yii::$app->user->isGuest) {
      return $this->redirect(['/vhod']);
    }

    $user = Yii::$app->user->identity;

    if (Yii::$app->request->isPost) {
      $configs = $user->config;

      foreach (Yii::$app->request->post() as $k => $v) {
        if ($configs->hasAttribute($k)) {
          $configs->{$k} = $v;
        }
      }

      if ($configs->save()) {
        return json_encode(['success' => true]);
      } else {
        return json_encode(['error' => true, 'errors' => json_encode($configs->errors)]);
      }
    }

    $groups = ProductGroups::find()
      ->orderBy('sort_order ASC')
      ->all();

    $group_id = 0;
    $params = [];
    $product_group = [];

    foreach ($groups as $gr) {
      if ($gr->url == $url) {
        $group_id = $gr->product_group_id;
        $params = $gr->attributeGroupsArray;
        $product_group = $gr;
      }
    }

    if (!$group_id) {
      throw new \yii\web\HttpException(404,'Страница не найдена');
    }

    $date_offset = date('Y-m-d', strtotime(date('Y-m-d') . "-30 days"));

    Yii::$app->db
      ->createCommand('DELETE FROM product_uploads WHERE user_id=' . $user->id . ' AND date < "' . $date_offset . '"')
      ->execute();

    $pg = Yii::$app->request->get('page');
    $offset = (int)$pg ? (((int)$pg - 1) * 10) : '0';

    return $this->render('ad/upload', [
      'groups' => $groups,

      'items' => \app\models\products\ProductUploads::find()
        ->select('date, id, upload_id')
        ->where(['user_id' => $user->id, 'group_id' => $group_id])
        ->orderBy('date DESC, id DESC')
        ->groupBy('upload_id')
        ->all(),

      'pagination' => new Pagination(
        [
          'totalCount' => \app\models\products\ProductUploadRules::find()
            ->where(['user_id' => $user->id, 'group_id' => $group_id])
            ->count(),
          'defaultPageSize' => 10
        ]
      ),

      'rules' => \app\models\products\ProductUploadRules::find()
        ->with('values')
        ->where(['user_id' => $user->id, 'group_id' => $group_id])
        ->limit(10)
        ->offset($offset)
        ->orderBy('id DESC')
        ->all(),

      'params' => $params,
      'product_group_id' => $url,
      'product_group' => $product_group,
    ]);
  }

  public function actionViewed($url = '')
  {
    if (Yii::$app->user->isGuest) {
      return $this->redirect(['/vhod']);
    }

    $viewed = [];
    $data = ['url' => $url];
    $data['params'] = Lists::getOptions([], false, Yii::$app->request->get());
    $data['groups'] = ProductGroups::find()->orderBy('sort_order ASC')->all();

    if (isset(Yii::$app->session['viewed'])) {
      $group_id = false;

      if ($url) {
        foreach ($data['groups'] as $gr) {
          if ($gr->url == $url) {
            $group_id = $gr->product_group_id;
            break;
          }
        }
      }

      $arr = Yii::$app->session['viewed'];
      $ids = [];

      foreach ($arr as $gr) {
        $ids = array_merge($ids, $gr);
      }

      $ids_list = '';

      if (is_array($ids)) {
        foreach ($ids as $id) $ids_list .= ',' . $id;
        $ids_list = substr($ids_list, 1);

        if (!isset($data['error']) || !$data['error']) {
          $page = Yii::$app->request->get('page');
          $offset = (int)$page ? (((int)$page - 1) * 20) : '0';

          $orderby = 'date_created DESC';
          $sort = Yii::$app->session->get('sorting_personal_viewed');

          if ($sort == 11) {
            $orderby = 'byn_price ASC';
          } else if ($sort == 12) {
            $orderby = 'category ASC';
          } else if ($sort == 13) {
            $orderby = 'make ASC, model ASC';
          } else if ($sort == 14) {
            $orderby = 'views DESC';
          }

          $data['viewed'] = $ids_list ? Products::find()
            ->where(('id IN (' . $ids_list . ')') . ($group_id ? ' AND group_id=' . $group_id : ''))
            ->orderBy($orderby)
            ->limit(20)
            ->offset($offset)
            ->with('attributesArray', 'country')
            ->all() : false;

          $data['pagination'] = new Pagination([
            'totalCount' => $ids_list ? Yii::$app->db
              ->createCommand('SELECT COUNT(*) FROM products WHERE id IN (' . $ids_list . ')' . ($group_id ? ' AND group_id=' . $group_id : ''))
              ->queryScalar() : 0
          ]);
        }
      } else {
        $data['viewed'] = [];
        $data['pagination'] = new Pagination(['totalCount' => 0]);
      }
    } else {
      $data['viewed'] = [];
      $data['pagination'] = new Pagination(['totalCount' => 0]);
    }

    Yii::$app->view->params['breadcrumbs'][] = ['label' => 'Просмотренное', 'url' => ''];
    return $this->render('viewed/index', $data);
  }

  public function actionDetails()
  {
    if (Yii::$app->user->isGuest) {
      return $this->redirect(['/vhod']);
    }

    if (Yii::$app->request->isPost) {
      $model = new \app\models\forms\UserDetailsForm();
      $json = array();

      if ($model->load(Yii::$app->request->post(), '')) {
        $result = $model->saveData();

        if (!$result['validated']) {
          $json['error'] = 'Не все поля заполнены верно';
          $json['errors'] = $model->errors;
        } else {
          $json['errors'] = $model->errors;
          $json['success'] = true;
        }
      } else {
        $json['error'] = 'Ошибка выполнения запроса';
      }

      return json_encode($json);
    }

    Yii::$app->view->params['breadcrumbs'][] = ['label' => 'Условия продажи', 'url' => ''];
    return $this->render('profile/details', []);
  }

  public function actionProfile()
  {
    if (Yii::$app->user->isGuest) {
      return $this->redirect(['/vhod']);
    }

    if (Yii::$app->request->isPost) {
      $model = new CabinetForm();
      $json = array();

      if ($model->load(Yii::$app->request->post(), '')) {
        $result = $model->saveData();

        if (!$result['validated']) {
          $json['error'] = 'Не все поля заполнены верно';
          $json['errors'] = $model->errors;
        } else {
          if (isset($result['message'])) $json['message'] = $result['message'];
          $json['errors'] = $model->errors;
          if (isset($result['image_updated'])) $json['image_updated'] = $result['image_updated'];
          if (isset($result['email_validation'])) $json['email_validation'] = $result['email_validation'];
          $json['success'] = true;
        }
      } else {
        $json['error'] = 'Ошибка выполнения запроса';
      }

      return json_encode($json);
    }

    Yii::$app->view->params['breadcrumbs'][] = ['label' => 'Профиль', 'url' => ''];
    $user = Yii::$app->user->identity;

    if ($user->role === User::ROLE_COMPANY) {
      return $this->render('profile/corp', [
        'options_country' => Lists::getOptionCountryList($user->country_id, true),
        'options_city' => Lists::getOptionCityList($user->city, $user->country_id, true)
      ]);
    } else {
      return $this->render('profile/fiz', [
        'options_country' => Lists::getOptionCountryList($user->country_id, true),
        'options_city' => Lists::getOptionCityList($user->city, $user->country_id, true)
      ]);
    }
  }

  public function actionShop()
  {
    if (Yii::$app->user->isGuest) {
      return $this->redirect(['/vhod']);
    }

    $user = Yii::$app->user->identity;

    if (Yii::$app->request->isPost) {
      $json = array();
      $template = Yii::$app->request->post('template');

      if (!$template) {
        $json['error'] = 'Ошибка: отсутствует название шаблона';
        $json['validated'] = false;
      } else {
        $user->config->template = $template;

        if ($user->config->save()) {
          $json['success'] = true;
        } else {
          $json['error'] = 'Ошибка: данные неверны';
          $json['validated'] = false;
          $json['errors'] = $user->config->errors;
        }
      }

      return json_encode($json);
    }

    Yii::$app->view->params['breadcrumbs'][] = ['label' => 'Мой магазин', 'url' => ''];
    return $this->render('shop/index');
  }

  public function actionFillialz()
  {
    if (Yii::$app->user->isGuest) {
      return $this->redirect(['/vhod']);
    }

    if (Yii::$app->user->identity->role == User::ROLE_CLIENT) {
      return $this->redirect(['personal/profile']);
    }

    if (Yii::$app->request->isPost) {
      $model = new \app\models\forms\FillialForm();
      $json = array();

      if ($model->load(Yii::$app->request->post(), '')) {
        $result = $model->saveData();

        if (!$result['validated']) {
          $json['error'] = 'Не все поля заполнены верно';
          $json['errors'] = $model->errors;
          if (isset($json['errors']['phone'])) $json['errors']['phs'] = $json['errors']['phone'];
        } else {
          $json['errors'] = $model->errors;
          $json['success'] = true;
        }
      } else {
        $json['error'] = 'Ошибка выполнения запроса';
      }

      return json_encode($json);
    }

    Yii::$app->view->params['breadcrumbs'][] = ['label' => 'Филлиалы', 'url' => ''];
    $user = Yii::$app->user->identity;

    $check = Yii::$app->db
      ->createCommand('SELECT COUNT(*) FROM user_fillialz WHERE empty=1 AND user_id=' . $user->id)
      ->queryScalar();

    if (!$check) {
      $fillial = new \app\models\user\Fillial();
      $fillial->user_id = $user->id;
      $fillial->empty = 1;
      $fillial->name = '';
      $fillial->country = '';
      $fillial->city = '';
      $fillial->address = '';
      $fillial->save(false);
    } else {
      Yii::$app->db
        ->createCommand('UPDATE user_fillialz SET phone=NULL WHERE empty=1 AND user_id=' . $user->id)
        ->execute();
    }

    return $this->render('profile/fillialz', [
      'options_country' => Lists::getOptionCountryList(false, true),
      'options_city' => Lists::getOptionCityList(false, false, true)
    ]);
  }

  public function actionAbout()
  {
    if (Yii::$app->user->isGuest) {
      return $this->redirect(['/vhod']);
    }

    if (Yii::$app->user->identity->role == User::ROLE_CLIENT) {
      return $this->redirect(['personal/profile']);
    }

    if (Yii::$app->request->isPost) {
      $model = new \app\models\forms\CompanyForm();
      $json = array();

      if ($model->load(Yii::$app->request->post(), '')) {
        $result = $model->saveData();

        if (!$result['validated']) {
          $json['error'] = 'Не все поля заполнены верно';
          $json['errors'] = $model->errors;
        } else {
          $json['errors'] = $model->errors;
          $json['success'] = true;
        }
      } else {
        $json['error'] = 'Ошибка выполнения запроса';
      }

      return json_encode($json);
    }

    Yii::$app->view->params['breadcrumbs'][] = ['label' => 'О компании', 'url' => ''];
    return $this->render('profile/about', []);
  }

  public function actionPass()
  {
    if (Yii::$app->user->isGuest) {
      return $this->redirect(['/vhod']);
    }

    if (Yii::$app->request->isPost) {
      $model = new \app\models\forms\PassForm();

      $json = [
        'success' => false,
        'validated' => false
      ];

      if ($model->load(Yii::$app->request->post(), '')) {
        $result = $model->saveData();
        $json['password_changed'] = $result['password_changed'];

        if (!$result['validated']) {
          $json['error'] = 'Не все поля заполнены верно';
          $json['errors'] = $model->errors;
        } else {
          $json['errors'] = $model->errors;
          $json['success'] = true;
        }
      } else {
        $json['error'] = 'Ошибка выполнения запроса';
      }

      return json_encode($json);
    }

    Yii::$app->view->params['breadcrumbs'][] = ['label' => 'Изменение пароля', 'url' => ''];
    return $this->render('profile/pass', []);
  }

  public function actionStatistics()
  {
    if (Yii::$app->user->isGuest) {
      return $this->redirect(['/vhod']);
    }

    $month = Yii::$app->request->get('month');
    $year = date('Y');
    if (!$month) $month = date('m');

    Yii::$app->db
      ->createCommand('DELETE FROM user_statistics WHERE user_id=' . Yii::$app->user->identity->id . ' AND date NOT LIKE "' . $year . '-%"')
      ->execute();

    Yii::$app->db
      ->createCommand('DELETE FROM profile_statistics WHERE user_id=' . Yii::$app->user->identity->id . ' AND date NOT LIKE "' . $year . '-%"')
      ->execute();

    $statistics = Statistics::find()
      ->where('user_id=' . Yii::$app->user->identity->id . ' AND date LIKE "' . $year . '-' . $month . '-%"')
      ->all();

    $pstatistics = ProfileStatistics::find()
      ->where('user_id=' . Yii::$app->user->identity->id . ' AND date LIKE "' . $year . '-' . $month . '-%"')
      ->all();

    Yii::$app->view->params['breadcrumbs'][] = ['label' => 'Статистика', 'url' => ''];

    return $this->render('statistics/statistics', [
      'statistics' => $statistics,
      'pstatistics' => $pstatistics
    ]);
  }

  public function actionProductstatistics()
  {
    if (Yii::$app->user->isGuest) {
      return $this->redirect(['/vhod']);
    }

    $year = date('Y');
    $month = date('m');

    Yii::$app->db
      ->createCommand('DELETE FROM user_statistics WHERE user_id=' . Yii::$app->user->identity->id . ' AND date NOT LIKE "' . $year . '-%"')
      ->execute();

    $statistics = Statistics::find()
      ->where('user_id=' . Yii::$app->user->identity->id . ' AND date LIKE "' . $year . '-' . $month . '-%"')
      ->all();

    $prod_ids = '';

    foreach ($statistics as $stat) {
      $arr = $stat->products;
      foreach ($arr as $k => $v) $prod_ids .= ',' . $k;
    }

    $prod_ids = substr($prod_ids, 1);
    $products = [];

    if (!empty($prod_ids)) {
      $res = Products::find()
        ->where('id IN (' . $prod_ids . ')')
        ->all();

      foreach ($res as $product) {
        $products[$product->id] = array(
          'name' => $product->name,
          'url' => $product->url
        );
      }
    }

    Yii::$app->view->params['breadcrumbs'][] = ['label' => 'Обзор объявлений', 'url' => ''];

    return $this->render('statistics/statistics_views', [
      'statistics' => $statistics,
      'products' => $products
    ]);
  }

  public function actionUploadproducts($url)
  {
    if (Yii::$app->user->isGuest) {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }

    if (Yii::$app->request->isPost) {
      $group = ProductGroups::findOne(['product_group_id' => $url]);

      if (!$group) {
        return json_encode(['error' => 'Некорректный адрес запроса']);
      }

      $model = new \app\models\products\UploadForm();
      $model->load(Yii::$app->request->post(), '');
      $result = $model->saveData($group);
      $result['errors'] = $model->errors;
      return json_encode($result);
    } else {
      return json_encode(['error' => 'true']);
    }
  }

  protected function getFavourites($type)
  {
    $ids = Yii::$app->db
      ->createCommand('SELECT product_id FROM user_favourites WHERE user_id = ' . Yii::$app->user->identity->id . ' AND group_id=' . $type)
      ->queryAll();

    $ids_list = '';
    foreach ($ids as $k => $v) $ids_list .= ',' . $v['product_id'];
    return substr($ids_list, 1);
  }

  protected function getFavouriteSellers()
  {
    $ids = Yii::$app->db
      ->createCommand('SELECT target_user_id FROM user_favourites WHERE user_id = ' . Yii::$app->user->identity->id . ' AND target_user_id IS NOT NULL')
      ->queryAll();

    $ids_list = '';
    foreach ($ids as $k => $v) $ids_list .= ',' . $v['target_user_id'];
    return substr($ids_list, 1);
  }

  public static function getProductPageData($url, $load_all_make_model_generations = false,  $vals = [], $with_sections = false)
  {
    $product_group = false;

    if ($url) {
      $product_group = ProductGroups::find()->where(['url' => $url])->one();
    } else {
      $product_group = ProductGroups::find()->where('is_default=1')->one();
    }

    if (!$product_group) {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }

    $data = array(
      'groups' => ProductGroups::find()->orderBy('sort_order ASC')->all(),
      'products' => [],
    );

    $product_group_id = '';

    if ($product_group) {
      $make_model_gen_arr = [];

      if ($load_all_make_model_generations) {
        $makes_json_update_needed = JSONData::updateMakesFileVersion();

        $data['makes_file_version'] = $makes_json_update_needed[1];

        if ($makes_json_update_needed[0]) {
          $make_model_gen_arr['makes'] = Yii::$app->db
            ->createCommand('SELECT makes.* FROM makes 
            INNER JOIN make_to_group ON make_id=id 
            INNER JOIN make_groups ON make_groups.make_group_id=make_to_group.make_group_id
            ' . ($product_group['make_groups'] ? 'WHERE make_to_group.make_group_id IN (' . $product_group['make_groups'] : '') . ')
             GROUP BY name ORDER BY name')
            ->cache(3600)
            ->queryAll();

          $make_model_gen_arr['models'] = Yii::$app->db
            ->createCommand('SELECT make_models.id, make_models.url, 
            make_models.make_id, make_models.name FROM make_models  
            LEFT JOIN make_to_group ON make_to_group.make_id = make_models.make_id
					   ' . ($product_group['make_groups'] ? 'WHERE make_to_group.make_group_id IN (' . $product_group['make_groups'] : '') . ')')
            ->cache(3600)
            ->queryAll();

          $make_model_gen_arr['generations'] = Yii::$app->db
            ->createCommand('SELECT make_generations.id, make_generations.url, 
              make_generations.make_id, make_generations.model_id, name, make_generations.years FROM make_generations  
					    LEFT JOIN make_to_group ON make_to_group.make_id = make_generations.make_id
              ' . ($product_group['make_groups'] ? 'WHERE make_to_group.make_group_id IN (' . $product_group['make_groups'] : '') . ')')
            ->cache(3600)
            ->queryAll();

          JSONData::createMakesJSON(
            $makes_json_update_needed[1],
            $make_model_gen_arr['makes'],
            $make_model_gen_arr['models'],
            $make_model_gen_arr['generations']
          );
        }
      }

      $data = array_merge($data, $make_model_gen_arr);
      $data['product_group'] = $product_group;
      $data['product_group_id'] = $product_group['product_group_id'];
      $data['category'] = $product_group['product_categories'] != 'all' ? $product_group['product_categories'] : false;
      $data['params'] = Lists::getOptions([], $product_group, !$vals ? Yii::$app->request->get() : $vals, '', $with_sections);
    } else {
      $data['error'] = 'Возникла ошибка: группа товаров не определена';
    }

    return $data;
  }

  public function beforeAction($action)
  {
    PageUtils::getMenus();
    PageUtils::registerPageDataByUrl(PageUtils::getPageUrl(Yii::$app->request->pathInfo));

    $host = explode('.', Yii::$app->request->hostName);

    if (sizeof($host) >= 2) {
      Yii::$app->view->params['site_city'] = Cities::find()
        ->where(['domain' => $host[0]])
        ->one();
    }

    Yii::$app->view->params['breadcrumbs'] = [
      ['label' => 'Главная', 'url' => '/'],
      ['label' => 'Мой кабинет', 'url' => '/personal/']
    ];

    if ($action->id == 'sell') $this->enableCsrfValidation = false;
    return parent::beforeAction($action);
  }
}
