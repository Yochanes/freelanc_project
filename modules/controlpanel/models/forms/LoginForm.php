<?php

namespace app\modules\controlpanel\models\forms;

use Yii;
use yii\base\Model;
use app\models\User;

class LoginForm extends Model
{
  public $username;
  public $password;
  public $rememberMe = true;

  private $_user = false;


  /**
   * @return array the validation rules.
   */
  public function rules()
  {
    return [
      [['username'], 'string', 'min' => 4, 'max' => 100],
      [['password'], 'string', 'min' => 4, 'tooShort' => 'Длина пароля должна быть не менее 6 символов'],
      ['rememberMe', 'boolean'],
      ['password', 'validatePassword'],
    ];
  }

  public function validatePassword($attribute, $params)
  {
    if (!$this->hasErrors()) {
      $user = $this->getUser();

      if (!$user) {
        $this->addError($attribute, 'Пользователь ' . $this->username . ' отсутствует в базе данных');
        return false;
      }

      if (!$user->validatePassword($this->password)) {
        $this->addError($attribute, 'Проверьте правильность ввода пароля');
        return false;
      }

      return true;
    }
  }

  public function login()
  {
    if ($this->validate()) {
      return Yii::$app->user->login($this->getUser(), $this->rememberMe ? 3600 * 24 * 30 : 0);
    }

    return false;
  }

  public function getUser()
  {
    if ($this->_user === false) {
      $this->_user = User::findByUsername($this->username);
    }

    return $this->_user;
  }
}
