<?php

namespace app\models\forms;

use Yii;
use yii\base\Model;

class PassForm extends Model
{
  public $password;
  public $password_new;

  public function rules()
  {
    return [
      [['password', 'password_new'], 'string', 'max' => 255, 'tooLong' => 'Длина пароля должна быть не более 255 символов'],
      // ['phone', 'each', 'rule' => ['string', 'max' => 20, 'tooLong' => 'Длина пароля должна быть не более 20 символов'], 'skipOnEmpty' => true],
    ];
  }

  public function saveData()
  {
    $result = array('password_changed' => false);
    $user = Yii::$app->user->identity;

    if ($this->validate()) {
      if ($this->password && $this->password_new) {
        if ($user->validatePassword($this->password)) {
          $user->password = Yii::$app->security->generatePasswordHash($this->password_new);
          $user->save(false);
          $result['password_changed'] = true;
        } else {
          $this->addError('password', 'Вы ввели неверный пароль');
        }
      }

      $result['success'] = true;
      $result['validated'] = true;
      return $result;
    }

    $result['validated'] = false;
    $result['success'] = false;
    return $result;
  }
}
