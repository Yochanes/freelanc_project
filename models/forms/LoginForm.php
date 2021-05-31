<?php

namespace app\models\forms;

use Yii;
use yii\base\Model;
use app\models\User;

class LoginForm extends Model
{
  public $username;
  public $password;
  public $rememberMe = true;
  private $_user = false;

  public function rules()
  {
    return [
      [['username', 'password'], 'required', 'message' => Yii::t('app', 'required_field')],
      [['username'], 'string', 'min' => 4, 'max' => 100, 'tooShort' => 'Длина пароля должна быть не менее 6 символов', 'tooLong' => 'Длина пароля должна быть не более 100 символов'],
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
      Yii::$app->session->remove('auth_code_times');
      Yii::$app->session->remove('auth_pass_times');
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
