<?php

namespace app\models\forms;

use app\models\config\SMTP;
use app\models\User;
use app\models\user\Complaints;
use app\models\user\Configs;
use app\models\user\Contacts;
use app\models\user\Details;
use Yii;
use yii\base\Model;

class SpamForm extends Model
{
  public $username;
  public $phone;
  public $user_id;
  public $target_id;
  public $target_name;
  public $target_url;
  public $reason;
  public $text;

  public function rules()
  {
    return [
      [['target_id', 'reason', 'target_name', 'target_url'], 'required', 'message' => Yii::t('app', 'required_field')],
      ['username', 'email', 'message' => 'Вы ввели некорректный email адрес'],
      [['target_url', 'target_name'], 'string', 'max' => 255, 'tooLong' => 'Длина имени должна быть не более 255 символов'],
      ['text', 'string', 'max' => 32, 'tooLong' => 'Длина имени должна быть не более 32 символа'],
      ['reason', 'string', 'max' => 1000, 'tooLong' => 'Длина имени должна быть не более 1000 символов'],
      ['phone', 'string', 'max' => 20, 'tooLong' => 'Длина телефона должна быть не более 20 символов']
    ];
  }

  public function saveData()
  {
    $result = array(
      'error' => true
    );

    if ($this->validate()) {
      $user = false;

      if (Yii::$app->user->isGuest) {
        $check = User::find()
          ->where('username = "' . $this->username . '"')
          ->one();

        if ($check) {
          $this->addError('username', 'Пользователь с таким логином уже зарегистрирован на сайте');
          return $result;
        }

        $user = new User();
        $user->username = $this->username;
        $password = Yii::$app->security->generateRandomString(8);
        $user->password = $password;

        if ($user->save()) {
          $contact = new Contacts();
          $contact->email = $this->username;
          $contact->phone = array($this->phone);
          $contact->user_id = $user->id;
          $contact->save();

          $configs = new Configs();
          $configs->user_id = $user->id;
          $configs->save();

          $details = new Details();
          $details->user_id = $user->id;
          $details->save();

          $config = SMTP::find()->where('active=1')->one();

          try {
            if ($config) {
              $to = $this->username;
              $subject = 'Регистрация на сайте';
              $text = '<p>Благодарим Вас за регистрацию на нашем сайте</p>
								<p><strong>Ваш логин: </strong>' . $this->username . '</p>
								<p><strong>Ваш пароль: </strong>' . $password . '</p>';

              $config->sendEmail($to, $subject, '', $text);
            }
          } catch (\Exception $e) {
          }
        } else {
          $this->addError('username', 'Ошибка сохранения пользователя');
        }
      } else {
        $user = Yii::$app->user->identity;
      }

      if ($user) {
        Yii::$app->db
          ->createCommand('DELETE FROM complaints WHERE user_id=' . $user->id . ' AND target_id=' . $this->target_id . ' AND target_url="' . $this->target_url . '"')
          ->execute();

        $complaint = new Complaints();

        if ($complaint->load(Yii::$app->request->post(), '')) {
          $complaint->user_id = $user->id;
          $complaint->text = 'Жалоба на <a href="' . $this->target_url . '">объявление</a>: ' . $this->text . '. ' . $this->reason;

          if ($complaint->save()) {
            $result['success'] = true;
          } else {
            $result['error'] = 'Ошибка сохранения данных';
          }
        } else {
          $result['error'] = 'Ошибка сохранения данных';
        }
      } else {
        $result['error'] = 'Ошибка сохранения данных';
      }
    }

    return $result;
  }
}
