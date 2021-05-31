<?php

namespace app\modules\controlpanel\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;

use app\models\User;

use app\modules\controlpanel\models\forms\UploadForm;
use app\modules\controlpanel\models\forms\TiresUploadForm

class UploadController extends Controller
{
	
	public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }
	
    public function actionMakes() {
		if (Yii::$app->user->isGuest || !Yii::$app->user->identity->isAdmin()) {
            return json_encode(['error' => 'Операция недоступна']);
        }
		
		if (Yii::$app->request->isPost) {
			$model = new UploadForm();
			$json = array();
			
			if ($model->load(Yii::$app->request->post(), '')) {	
				$result = $model->saveData();
				foreach ($result as $key => $val) $json[$key] = $val;
				
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
		} else {
			return json_encode(['error' => 'Операция недоступна']);
		}
	}
	
	public function actionTires() {
		if (Yii::$app->user->isGuest || !Yii::$app->user->identity->isAdmin()) {
            return json_encode(['error' => 'Операция недоступна']);
        }
		
		if (Yii::$app->request->isPost) {
			$model = new TiresUploadForm();
			$json = array();
			
			if ($model->load(Yii::$app->request->post(), '')) {	
				$result = $model->saveData();
				foreach ($result as $key => $val) $json[$key] = $val;
				
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
		} else {
			return json_encode(['error' => 'Операция недоступна']);
		}
	}
}
