<?php

namespace app\models\forms;

use Yii;
use yii\base\Model;

use app\models\User;
use app\models\user\Contacts;
use app\models\user\Configs;
use app\models\user\Curs;
use app\models\user\Details;

use app\models\config\SMTP;

class RegisterForm extends Model
{
  public $username;
  public $code;
  public $role = User::ROLE_CLIENT;
  public $display_name;
  public $email;
  public $city;
  public $country;
  public $address;

  public $codeIsNeeded = true;
  public $emailIsNeeded = false;

  public function rules()
  {
    return [
      ['username', 'required', 'message' => Yii::t('app', 'required_field')],
      ['email', 'required','when' => function ($model, $attribute) { return $model->emailIsNeeded; }, 'message' => Yii::t('app', 'required_field')],
      ['code', 'required','when' => function ($model, $attribute) { return $model->codeIsNeeded; }, 'message' => Yii::t('app', 'required_field')],
      [['city', 'country'], 'string', 'max' => 100, 'tooLong' => 'Длина этого поля не может превышать 100 символов'],
      [['display_name', 'address'], 'string', 'max' => 255, 'tooLong' => 'Длина этого поля не может превышать 255 символов'],
      [['email'], 'email', 'skipOnEmpty' => true, 'message' => 'Вы ввели некорректный email'],
      ['role', 'string', 'max' => 20, 'tooLong' => 'Длина имени должна быть не более 255 символов'],
    ];
  }

  public function register($country_id)
  {
    $user = new User();

    if ($this->validate()) {
      $check = User::find()->where('username = "' . $this->username . '"')->one();

      if ($check) {
        $this->addError('username', 'Пользователь с таким логином уже зарегистрирован на сайте');
        return false;
      }

      $code = Yii::$app->session->get('tmp_code');

      if (!$code) {
        $this->addError('code', 'Введенный вами код более недействителен');
        return false;
      } else if ($code != $this->code) {
        $this->addError('code', 'Вы ввели неверный код');
        return false;
      }

      if ($user->load(Yii::$app->request->post(), '')) {
        $user->country_id = $country_id;
        $password = $code;
        $user->password = $password;
        $user->role = (($this->role == User::ROLE_CLIENT || $this->role = User::ROLE_COMPANY) ? $this->role : User::ROLE_CLIENT);

        if ($user->save()) {
          $user->display_name = 'id' . $user->id;
          $user->save();

          Yii::$app->session->remove('auth_step');
          Yii::$app->session->remove('tmp_code');
          Yii::$app->session->remove('auth_code_times');
          Yii::$app->session->remove('auth_pass_times');

          $contact = new Contacts();
          $contact->email = '';
          $contact->phone = array($this->username);
          $contact->user_id = $user->id;
          $contact->save();

          $configs = new Configs();
          $configs->user_id = $user->id;
          $configs->save();

          $details = new Details();
          $details->user_id = $user->id;
          $details->save();

          $curs = new Curs();
          $curs->user_id = $user->id;
          $curs->save();

          if ($user->role == User::ROLE_COMPANY) {
            $company = new \app\models\user\Companies();
            $company->user_id = $user->id;
            $company->save(false);
          }

          Yii::$app->user->login($user, 3600 * 24 * 30);
          return true;
        } else {
          $this->addError('username', 'Ошибка сохранения пользователя');
          $this->addError('user', $user->errors);
        }
      } else {
        $this->addError('username', 'Ошибка сохранения пользователя: неверные данные');
      }
    }

    return false;
  }

  public function create()
  {

    $this->codeIsNeeded = false;
    $user = Yii::$app->user->identity;
    $result = ['error' => false];

    if (!$user) {
      $this->emailIsNeeded = true;

      if (!$this->load(Yii::$app->request->post(), '') || !$this->validate()) {
        $result['error'] = true;
        $result['errors'] = $this->errors;
        return $result;
      }

      $check = User::find()
        ->where('username = "' . $this->username . '"')
        ->one();

      if ($check) {
        $this->addError('username', 'Пользователь с таким логином уже зарегистрирован на сайте');
        $result['error'] = true;
        $result['errors'] = $this->errors;
        $result['user_exists'] = true;
        return $result;
      }

      $user = new User();

      if (!$user->load(Yii::$app->request->post(), '')) {
        $result['error'] = true;
        $result['errors']['general'] = 'Ошибка: неверные данные пользователя';
        return $result;
      }

      $password = Yii::$app->security->generateRandomString(10);
      $user->password = $password;
      $user->role = User::ROLE_CLIENT;
      $user->country_id = $this->country;
      $user->city = $this->city;

      if (!$user->save()) {
        $result['error'] = true;
        $result['errors'] = $user->errors;
        $result['errors']['general'] = 'Ошибка: пользователь не добавлен';
        return $result;
      }

      if (!$this->display_name) $user->display_name = 'id' . $user->id;
      $contact = new Contacts();
      if ($this->email) $contact->email = $this->email;
      $contact->phone = array($this->username);
      $contact->user_id = $user->id;

      $hash = Yii::$app->security->generateRandomString(20);
      $contact->hash = $hash;
      $contact->save();

      $configs = new Configs();
      $configs->user_id = $user->id;
      $configs->save();

      $details = new Details();
      $details->user_id = $user->id;
      $details->address = $this->address;
      $details->save();

      $res = \app\models\helpers\Helpers::sendSMSCode($user->username, 'Ваш пароль для входа на сайт');

      if ($res['success']) {
        $password = $res['code'];
        $user->password = Yii::$app->security->generatePasswordHash($password);
      }

      $user->save();

      if ($this->email) {
        $email_check = Yii::$app->db
          ->createCommand('SELECT COUNT(*) FROM user_contacts WHERE email="'.$this->email.'" AND email_approved=1')
          ->queryScalar();

        if (!$email_check) {
          try {
            $config = SMTP::find()->where('active=1')->one();

            if ($config) {
              $to = $this->email;
              $subject = 'Подтверждение адреса';

              $text = '<p>Подтвердите ваш адрес электронной почты перейдя по ссылке ниже:</p>
								<p><a href="http://' . $_SERVER['HTTP_HOST'] . '/actions/validate/' . urlencode($hash) . '/">
								<strong>Подтвердить адрес</strong></a></p>';

              $config->sendEmail($to, $subject, '', $text);

              $to = $this->email;
              $subject = 'Регистрация на сайте';
              $text = '<p>Благодарим Вас за регистрацию на нашем сайте</p>
								<p><strong>Ваш логин: </strong>' . $this->username . '</p>
								<p><strong>Ваш пароль: </strong>' . $password . '</p>';

              $config->sendEmail($to, $subject, '', $text);
            }
          } catch (\Exception $e) {}
        }
      }

      Yii::$app->session->setFlash('regMsg', 'Вы успешно зарегистрировались на нашем сайте<br>На указанный адрес электронной почты отправлено письмо для подтверждения регистрации');
    }

    $result['user'] = $user;
    return $result;
  }
}
