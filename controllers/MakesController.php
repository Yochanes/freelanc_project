<?php

namespace app\controllers;

use Yii;
use app\models\makes\MakeGroups;

use yii\web\Controller;

use app\models\forms\ForgotForm;

use app\models\helpers\PageUtils;
use app\models\site\Pages;
use app\models\makes\Makes;
use app\models\makes\Models;
use yii\web\HttpException;


class MakesController extends Controller
{

  public function actionIndex($url = '')
  {

    $groups = MakeGroups::find()->all();

    Yii::$app->view->params['breadcrumbs'] = [
      ['label' => 'Главная', 'url' => Yii::$app->homeUrl],
    ];

    $params = [
      'groups' => $groups
    ];

    return $this->render('index', $params);
  }

  public function actionMakes($url)
  {

    $group = MakeGroups::find()
      ->where(['url' => $url])
      ->with('makes')
      ->one();

    if (!$group) {
      throw new HttpException('Страница не найдена');
    }

    Yii::$app->view->params['breadcrumbs'] = [
      ['label' => 'Главная', 'url' => Yii::$app->homeUrl],
      ['label' => 'Марки', 'url' => '/katalog/marki'],
      ['label' => $group->name, 'url' => '/katalog/marki/' . $group->url]
    ];

    $params = [
      'makes' => $group->makes,
      'make_group' => $group
    ];

    return $this->render('makes', $params);
  }

  public function actionModels($make)
  {

    $models = Models::find()
      ->select('id, name, url, make_url')
      ->where(['make_url' => $make])
      ->all();

    $make = Makes::find()->where(['url' => $make])->one();

    Yii::$app->view->params['breadcrumbs'] = [
      ['label' => 'Главная', 'url' => Yii::$app->homeUrl],
      ['label' => $make->name, 'url' => '/' . $make->url]
    ];

    $params = [
      'make' => $make,
      'models' => $models
    ];

    return $this->render('models', $params);
  }

  public function actionModel($make, $model)
  {

    $make = Makes::find()->where(['url' => $make])->one();
    $model = Models::find()->where(['url' => $model])->one();

    Yii::$app->view->params['breadcrumbs'] = [
      ['label' => 'Главная', 'url' => Yii::$app->homeUrl],
      ['label' => $make->name, 'url' => '/' . $make->url],
      ['label' => $model->name, 'url' => '/' . $make->url . '/' . $model->url]
    ];

    $params = [
      'make' => $make,
      'model' => $model,
      'parts' => \app\models\products\Categories::find()->cache(3600)->all()
    ];

    return $this->render('model', $params);
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
}
