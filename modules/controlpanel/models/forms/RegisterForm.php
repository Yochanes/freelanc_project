<?php

namespace app\modules\controlpanel\models\forms;

use Yii;
use yii\base\Model;

use app\models\User;
use app\models\user\Contacts;
use app\models\user\Details;

/**
 * LoginForm is the model behind the login form.
 *
 * @property User|null $user This property is read-only.
 *
 */
class RegisterForm extends Model
{
  public $username;

  public $display_name;

  // holds the entered password
  public $password;

  // holds the password confirmation word
  public $repeat_password;

  /**
   * @return array the validation rules.
   */
  public function rules()
  {
    return [
      // username and password are both required
      [['username'], 'string', 'max' => 100, 'tooLong' => 'Длина имени пользователя должна быть не более 100 символов'],
      [['password', 'repeat_password'], 'string', 'min' => 4, 'max' => 40, 'tooShort' => 'Длина пароля должна быть не менее 6 символов', 'tooLong' => 'Длина пароля должна быть не более 40 символов'],
      [['password'], 'compare', 'compareAttribute' => 'repeat_password', 'message' => 'Пароли не совпадают'],
    ];
  }

  public function register()
  {
    if ($this->validate()) {
      $user = new User();

      if ($user->load(Yii::$app->request->post(), 'RegisterForm')) {
        $user->user_role = User::ROLE_ADMIN;
        $contact = new Contacts();

        foreach ($contact->attributes as $key => $value) {
          $contact->{$key} = '';
        }

        $contact->save();
        $contact_id = $contact->id;

        $details = new Details();

        foreach ($details->attributes as $key => $value) {
          $details->{$key} = '';
        }

        $details->save();
        $details_id = $details->id;

        $user->contact_id = $contact_id;
        $user->details_id = $details_id;

        if ($user->save(false)) {
          $contact->user_id = $user->id;
          $contact->save();

          $details->user_id = $user->id;
          $details->save();

          Yii::$app->user->login($user);
          $user->id = Yii::$app->user->identity->id;
          return ['user_id' => $user->id];
        }
      }
    }

    return false;
  }
}
