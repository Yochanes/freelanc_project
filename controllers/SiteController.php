<?php

namespace app\controllers;

use app\components\SupportComponent;
use app\models\forms\AuthForm;
use app\models\makes\Models;
use Yii;
use yii\data\ArrayDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;

use app\models\User;

use app\models\forms\LoginForm;
use app\models\forms\RegisterForm;

use app\models\helpers\Lists;
use app\models\helpers\PageUtils;
use app\models\site\Pages;

use app\models\Products;
use app\models\products\ProductGroups;
use yii\data\Pagination;

use alexandernst\devicedetect\DeviceDetect;

class SiteController extends Controller
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
        'view' => 'error.php',
      ],
      'captcha' => [
        'class' => 'yii\captcha\CaptchaAction',
        'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
      ],
    ];
  }

  public function actionIndex($parameters = '') {
    $detect = new DeviceDetect();
    $page = Pages::findOne(['url' => '/']);

    if ($page) {
      PageUtils::registerPageData($page);
    }

    if ($detect->isMobile()) {
      $groups = ProductGroups::find()->all();
      $arr = [];
      foreach ($groups as $gr) $arr[$gr->product_group_id] = $gr;
      $groups = $arr;

      $pg = Yii::$app->request->get('page');
      $offset = (int)$pg ? (((int)$pg - 1) * 20) : '0';

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
        'groups' => $groups,
        'products' => Products::find()
          ->with(['user', 'contacts', 'attributesArray', 'country'])
          ->where('status=' . Products::STATE_ACTIVE)
          ->limit(20)
          ->offset($offset)
          ->orderBy($orderby)
          ->all(),

        'pagination' => new Pagination(
          [
            'totalCount' => Yii::$app->db
              ->createCommand('SELECT COUNT(*) FROM products WHERE status=' . Products::STATE_ACTIVE)
              ->queryScalar(),
            'route' => preg_replace('/\?page=\d+|&partial=1/m', '', Yii::$app->request->url)
          ]
        ),
      ];

      $resp['params'] = Lists::getOptionAttributeList('', '', true);

      return $this->render('index_mobile', array_merge($this->view_vars, $resp));
    } else {
      return Yii::$app->runAction('products/products');
    }
  }

  public function actionJson()
  {
    $json['success'] = false;
    $popular = 1;

    if (isset($_POST['is_popular'])) {
      $popular = Yii::$app->request->post('is_popular');
    }


    $type = Yii::$app->request->post('type');

    if ($type == 'select_make') {
      $json['success'] = true;
      $json['options_list'] = Lists::getOptionMakeList('', '', '', '', [], $popular)['options_make'];
    } else if ($type == 'select_model') {
      $json['success'] = true;
      $json['options_list'] = Lists::getOptionModelList(Yii::$app->request->post('make'), '', '', $popular)['options_model'];
    } else if ($type == 'select_generation') {
      $model = Yii::$app->request->post('make') ? Models::find()
        ->select('id')
        ->where('(url="' . Yii::$app->request->post('model') . '" OR id="' . Yii::$app->request->post('model') . '") AND (make_url="' . Yii::$app->request->post('make') . '" OR make_id="' . Yii::$app->request->post('make') . '")')
        ->one() : false;

      $model = $model ? $model->id : Yii::$app->request->post('model');

      $json['success'] = true;
      $json['options_list'] = Lists::getOptionGenerationlList($model)['options_generation'];
    } else if ($type == 'select_city') {
      $json['success'] = true;
      $no_empty_value = isset($_POST['no_empty_value']) ? $_POST['no_empty_value'] : true;
      $json['options_list'] = Lists::getOptionCityList(false, Yii::$app->request->post('country'), $no_empty_value);
    } else if ($type == 'select_year') {
        $json['success'] = true;
        $json['options_list'] = Lists::getOptionYearList(false);
    } else if ($type == 'select_category') {
        $json['success'] = true;
        $json['options_list'] = Lists::getOptionCategoryList(false, 'all', true)['options_category'];
    }

    return json_encode($json);
  }

  public function actionUploadparamsjson()
  {
    if (Yii::$app->user->isGuest) {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }

    if (Yii::$app->request->isGet) {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }

    $classname = Yii::$app->request->post('classname');
    $where = Yii::$app->request->post("where");

    $variants = $classname::find()
      ->select(strpos($classname, 'Categories') === false ? 'name' : 'name, synonym')
      ->where($where)
      ->asArray()
      ->all();

    return json_encode($variants);
  }

  public function actionImage($url = '') {
    if (!Yii::$app->request->get('image') && !$url) {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }

    if (Yii::$app->request->get('image')) {
      $image = base64_decode(urldecode(Yii::$app->request->get('image')));
    } else {
      $image = '/web/gallery/' . $url . '/' . basename(Yii::$app->request->pathInfo);
    }

    $images = [];

    $path = Yii::$app->request->pathInfo;
    $path_exp = explode('/', $path);
    $pid_part = count($path_exp) >= 2 ? $path_exp[count($path_exp) - 2] : '';

    if (Yii::$app->request->get('images')) {
      $images = json_decode(base64_decode(Yii::$app->request->get('images')), true);
    } else if (is_numeric($pid_part)) {
      $images = Products::find()
        ->select('images')
        ->where(['id' => $pid_part])
        ->one();

      if ($images) {
        $images = $images->images;
      }
    }

    return $this->renderPartial(
      'image',
      [
        'image' => $image,
        'images' => $images
      ]
    );
  }

  public function actionPage($url)
  {
    $page = Pages::findOne(['url' => $url]);

    if ($page) {
      PageUtils::registerPageData($page);
      return $this->render($page->real_url, Yii::$app->view->params);
    } else {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }
  }

  public function actionSupport()
  {
    $support = new SupportComponent();
    $id = (int)Yii::$app->request->getQueryParam('id');

    $categories = $support->getCategories($id);
    $questions = $support->getQuestions($id);

    if(!$categories && !$questions)
      throw new \yii\web\HttpException(404, 'Страница не найдена');

    $CategoriesDataProvider = new ArrayDataProvider(['allModels' => $categories]);
    $QuestionsDataProvider = new ArrayDataProvider(['allModels' => $questions]);
    $breadcrumbs = $support->getBreadcrumbs($id);

    return $this->render('support_categories',
      array_merge(
        compact('CategoriesDataProvider',  'QuestionsDataProvider', 'breadcrumbs'),
        ['isFirstLevel' => !(bool)$id])
    );
  }

  public function actionQuestion($id)
  {
    $support = new SupportComponent();
    if(!$answer = $support->getAnswer($id))
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    $breadcrumbs = array_merge(
      $support->getBreadcrumbs($answer['category_id'], true),
      ['label' => $answer['title']]
    );
    return $this->render('support_answer', compact('answer', 'breadcrumbs'));
  }

  public function actionForgot()
  {
    $model = new ForgotForm();

    if (Yii::$app->request->isPost) {
      if ($model->load(Yii::$app->request->post()) && $model->check()) {
        Yii::$app->session->setFlash('flashMsg', 'Ваш пароль был отправлен вам на указанный адрес электронной почты');
        return $this->refresh();
      }
    }

    if (!Yii::$app->user->isGuest) {
      return $this->goHome();
    }

    $page = Pages::findOne(['url' => 'forgot']);
    PageUtils::registerPageData($page);
    $params = ['model' => $model];

    return $this->render($page ? $page->real_url : 'forgot', array_merge($this->view_vars, $params));
  }

  public function actionLogin()
  {
    $step = Yii::$app->request->post('step');

    if (Yii::$app->request->isPost && $step) {
      if (!Yii::$app->session->get('auth_phone')) $step = 1;

      if ($step == 1) {
        $model = new AuthForm();
        $model->load(Yii::$app->request->post(), '');
        return $model->check();
      } else if ($step == 2) {
        $model = new LoginForm();

        if ($model->load(Yii::$app->request->post(), '') && $model->login()) {
          return json_encode([
            'success' => true,
            'redirect' => Yii::$app->session->get('referer')
          ]);
        } else {
          return json_encode([
            'success' => false,
            'error' => $model->errors ? $model->errors : 'Ошибка авторизации'
          ]);
        }
      } else if ($step == 3) {
        $model = new RegisterForm();

        if ($model->load(Yii::$app->request->post(), '') && $model->register(Yii::$app->view->params['country']['id'])) {
          return json_encode([
            'success' => true,
            'redirect' => '/personal/pass'
          ]);
        } else {
          return json_encode([
            'success' => false,
            'errors' => $model->getErrors()
          ]);
        }
      }
    } else {
      if (!Yii::$app->user->isGuest) {
        return $this->redirect(Yii::$app->session->get('referer') ? Yii::$app->session->get('referer') : Yii::$app->homeUrl);
      }

      $referrer = Yii::$app->request->referrer;

      if (strpos($referrer, Yii::$app->homeUrl) !== false && strpos($referrer, 'login') === false && strpos($referrer, 'vhod') === false) {
        Yii::$app->session->set('referer', $referrer ? $referrer : Yii::$app->homeUrl);
      }

      $page = Pages::findOne(['url' => 'login']);
      PageUtils::registerPageData($page);
      $get_step = Yii::$app->request->get('step');

      Yii::$app->view->params['breadcrumbs'] = [
        ['label' => 'Главная', 'url' => ''],
        ['label' => 'Вход', 'url' => '/vhod']
      ];

      return $this->render('login', array_merge($this->view_vars, [
        'code_exists' => Yii::$app->session->get('tmp_code') ? true : false,
        'step' => $get_step && $get_step == 1 ? $get_step : Yii::$app->session->get('auth_step')
      ]));
    }
  }

  public function actionRemind()
  {
    if (Yii::$app->request->isPost && Yii::$app->request->post('username')) {
      $user = User::findByUsername(Yii::$app->request->post('username'));

      if ($user) {
        $times = Yii::$app->session->get('auth_pass_times');
        if (!$times) $times = 1;
        Yii::$app->session->set('auth_pass_times', $times + 1);

        if ($times < 4) {
          $res = \app\models\helpers\Helpers::sendSMSCode($user->username, 'Ваш код (временный пароль) для входа на сайт %s. Для вашей безопасности, смените ваш пароль после входа на сайт.');

          if (!$res['success']) {
            return json_encode([
              'success' => false,
              'error' => 'Ошибка отправки кода: ' . $res['error']
            ]);
          } else {
            $user->password = Yii::$app->security->generatePasswordHash($res['code']);
            $user->save();

            return json_encode([
              'success' => true,
              'message' => 'На номер <b>' . $user->username . '</b> отправлено СМС с новым паролем.<br>Введите пароль из СМС'
            ]);
          }
        } else {
          return json_encode([
            'success' => false,
            'error' => 'Ошибка: Вы исчерпали лимит повторных отправок кода'
          ]);
        }
      } else {
        return json_encode([
          'success' => false,
          'error' => 'Ошибка: пользователь не зарегистрирован'
        ]);
      }
    } else {
      return json_encode([
        'success' => false,
        'error' => 'Ошибка: отсутствуют данные пользователя'
      ]);
    }
  }

  public function actionResend()
  {
    if (Yii::$app->request->isPost && Yii::$app->session->get('auth_phone')) {
      $times = Yii::$app->session->get('auth_code_times');
      if (!$times) $times = 1;
      Yii::$app->session->set('auth_code_times', $times + 1);

      if ($times < 4) {
        $res = \app\models\helpers\Helpers::sendSMSCode(Yii::$app->session->get('auth_phone'), 'Ваш код (временный пароль) для входа на сайт %s. Для вашей безопасности, смените ваш пароль после входа на сайт.');

        if (!$res['success']) {
          return json_encode([
            'success' => false,
            'error' => 'Ошибка отправки кода: ' . $res['error']
          ]);
        } else {
          return json_encode([
            'success' => true,
            'message' => 'Код отправлен заново на ваш телефон'
          ]);
        }
      } else {
        return json_encode([
          'success' => false,
          'error' => 'Ошибка: Вы исчерпали лимит повторных отправок кода'
        ]);
      }
    } else {
      return json_encode([
        'success' => false,
        'error' => 'Ошибка: Попробуйте начать процедуру авторизации заново'
      ]);
    }
  }

  public function actionLogout()
  {
    try {
      Yii::$app->user->logout();
    } catch (\Exception $ex) {
      Yii::$app->response->cookies->remove('OCSESSID');
    }

    $referrer = Yii::$app->request->referrer;

    if (strpos($referrer, '/personal') !== false) {
      $referrer = '/';
    }

    return $referrer ? $this->redirect($referrer) : $this->goBack();
  }

  public function actionContact()
  {
    $model = new ContactForm();
    if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
      Yii::$app->session->setFlash('contactFormSubmitted');

      return $this->refresh();
    }
    return $this->render('contact', [
      'model' => $model,
    ]);
  }

  public function beforeAction($action)
  {
    $this->enableCsrfValidation = true;
    PageUtils::getMenus();

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

  /**
   * Загружаем файлы для использования в tinymce
   * @see https://www.tiny.cloud/docs/general-configuration-guide/upload-images/
   * @return false|string|void
   */
  public function actionUpload()
  {
    $accepted_origins = ['http://localhost:8080', 'http://localhost', 'http://178.172.236.239', 'http://autorazborkaby.by'];

    $imageFolder = '/web/images/';

    $_models = array_keys($_FILES);
    $model   = reset($_models);
    $temp = \yii\web\UploadedFile::getInstanceByName($model);

    if(!$temp->error) {
      if(isset($_SERVER['HTTP_ORIGIN']))
      {
        if(in_array($_SERVER['HTTP_ORIGIN'], $accepted_origins)){
          header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
        }
        else
          throw new \yii\web\HttpException(403, 'Origin Denied');
      }

      if (!in_array($temp->getExtension(), ['jpeg', 'jpg', 'png', 'gif'])) {
        throw new \yii\web\HttpException(400, 'Incorrect file extension');
      }

      $file = $imageFolder.$temp->name;
      $temp->saveAs($_SERVER['DOCUMENT_ROOT'].$file);
      return json_encode(['location' => $file]);
    }
    else
      throw new \yii\web\HttpException(500, 'Upload error');

  }
}
