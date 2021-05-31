<?php

namespace app\modules\controlpanel\controllers;

use Yii;
use yii\web\Controller;

class CronController extends Controller
{

  public function actions()
  {
    return [
      'error' => [
        'class' => 'yii\web\ErrorAction',
      ],
    ];
  }

  public function beforeAction($action)
  {
    if (Yii::$app->user->isGuest) {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }

    if (!Yii::$app->user->identity->isAdmin()) {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }

    return parent::beforeAction($action);
  }

  public function actionIndex()
  {

  }
}
