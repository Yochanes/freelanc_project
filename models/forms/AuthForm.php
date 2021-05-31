<?php

namespace app\models\forms;

use Yii;
use yii\base\Model;
use app\models\User;

class AuthForm extends Model
{
  public $step;
  public $username;

  public function rules()
  {
    return [
      ['username', 'required', 'message' => Yii::t('app', 'required_field')],
      ['username', 'string', 'min' => 4, 'max' => 100, 'tooShort' => 'Длина пароля должна быть не менее 6 символов', 'tooLong' => 'Длина пароля должна быть не более 100 символов'],
      ['step', 'integer'],
    ];
  }

  public function check($check = true)
  {
    if ($this->validate()) {
      $user_exists = $check ? ($this->exists() ? true : false) : $check;

      if (!$user_exists) {
        $code = Yii::$app->session->get('tmp_code');

        if (!$code) {
          $res = \app\models\helpers\Helpers::sendSMSCode($this->username);

          if (!$res['success']) {
            return json_encode([
              'success' => false,
              'errors' => ['phone' => 'Ошибка отправки кода: ' . $res['error']]
            ]);
          }
        }

        Yii::$app->session->set('auth_phone', $this->username);
        Yii::$app->session->set('auth_step', 3);
      } else {
        Yii::$app->session->set('auth_phone', $this->username);
        Yii::$app->session->set('auth_step', 2);
      }

      return json_encode([
        'success' => true,
        'user_exists' => $user_exists,
      ]);
    } else {
      return json_encode([
        'success' => false,
        'errors' => $this->errors
      ]);
    }
  }

  public function exists()
  {
    return User::find()->where(['username' => $this->username ])->count() ? true : false;
  }
}
