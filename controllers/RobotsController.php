<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\models\site\Robots;

class RobotsController extends Controller
{

    public function actionIndex()
	{

        $robots = Robots::find()->where(['url' => Yii::$app->request->hostName])->orWhere(['default_flag' => 1])->one();
        $this->layout = false;
        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->add('Content-Type', 'text/plain');

        if ($robots) {
            echo $robots->content;
        } else {
            echo '';
        }

        Yii::$app->end();
    }
}
