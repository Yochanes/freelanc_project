<?php

namespace app\models\forms;

use Yii;
use yii\base\Model;
use yii\web\UploadedFile;
use yii\imagine\Image;

use app\models\User;
use app\models\config\SMTP;

class CabinetForm extends Model
{
  public $display_name;
  public $address;
  public $whatsapp;
  public $telegram;
  public $viber;
  public $email;
  public $city;
  public $time;
  public $call_time;
  public $office_time;
  public $sklad_time;
  public $user_image;

  public function rules()
  {
    return [
      [['city', 'address'], 'required', 'message' => Yii::t('app', 'required_field')],
      //['call_time', 'required', 'when' => function ($model, $attribute) { return Yii::$app->user->identity->role == User::ROLE_CLIENT; }, 'message' => Yii::t('app', 'required_field')],
      [['sklad_time'], 'required', 'when' => function ($model, $attribute) { return Yii::$app->user->identity->role == User::ROLE_COMPANY; }, 'message' => Yii::t('app', 'required_field')],
      ['city', 'string', 'max' => 100, 'tooLong' => 'Максимальная длина 100 символов'],
      [['display_name', 'address', 'whatsapp', 'viber', 'telegram', 'email'], 'string', 'max' => 255, 'tooLong' => 'Максимальная длина 255 символов'],
      [['call_time', 'sklad_time'], 'string', 'max' => 500, 'skipOnEmpty' => true, 'tooLong' => 'Максимальная длина 500 символов'],
      ['email', 'email', 'skipOnEmpty' => true, 'message' => 'Адрес почты указан некорректно. Пожалуйста, проверьте написание адреса'],
      ['user_image', 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg'],
    ];
  }

  public function saveData()
  {
    $result = array();
    $user = Yii::$app->user->identity;
    $this->user_image = UploadedFile::getInstanceByName('user_image');
    $contacts = $user->contacts;

    if ($this->validate()) {
      $old_email = $contacts->email;

      if ($contacts->load(Yii::$app->request->post(), '')) {
        if ($this->email && $this->email != $old_email) {
          $email_check = Yii::$app->db
            ->createCommand('SELECT COUNT(*) FROM user_contacts WHERE email="' . $this->email . '"')
            ->queryScalar();

          if (!$email_check) {
            $contacts->email_approved = 0;
            $hash = str_replace('-', '_', Yii::$app->security->generateRandomString(20));
            $contacts->hash = $hash;
            $config = SMTP::find()->where('active=1')->one();

            try {
              if ($config) {
                $to = $this->email;
                $subject = 'Подтверждение адреса';

                $text = '<p>Подтвердите ваш адрес электронной почты перейдя по ссылке ниже:</p>
								<p><a href="http://' . $_SERVER['HTTP_HOST'] . '/actions/validate/' . urlencode($hash) . '/">
								<strong>Подтвердить адрес</strong></a></p>';

                $config->sendEmail($to, $subject, '', $text);
                $result['message'] = 'На ваш адрес почты отправлено письмо для подтверждения.<br>Пожалуйста, нажмите на ссылку в письме,<br>и ваш адрес будет подтвержден автоматически.';
                $result['email_validation'] = true;
              }
            } catch (\Exception $e) {
              $contacts->email = $old_email;
            }
          } else {
            $contacts->email = $old_email;
            $result['error'] = true;
            $this->addError('email', 'Данный адрес почты уже используется на сайте.<br>Пожалуйста, укажите другой адрес.');
            $result['validated'] = false;
            return $result;
          }
        } else if ($this->email) {
          $contacts->email = $old_email;
        }

        $contacts->save();
      } else {
        $old_email = $contacts->email;
      }

      $usr_image_to_delete = false;
      $old_name = $user->display_name;
      $user->load(Yii::$app->request->post(), '');

      if ($this->display_name && $this->display_name != $old_name) {
        $check = Yii::$app->db
          ->createCommand('SELECT COUNT(*) FROM users WHERE LOWER(display_name)="' . trim(mb_strtolower($this->display_name)) . '" AND id!=' . $user->id)
          ->queryScalar();

        if ($check) {
          $result['validated'] = false;
          $result['error'] = true;
          $this->addError('display_name', 'Это имя уже используется другим пользователем');
          return $result;
        }
      }

      if (!empty($this->user_image)) {
        $name = 'user_' . $user->id . '_' . Yii::$app->security->generateRandomString(20) . '.' . $this->user_image->extension;
        $tmppath = Yii::$app->basePath . '/web/gallery/tmpupload/' . $name;
        $regpath = Yii::$app->basePath . '/web/gallery/uploads/' . $name;

        if ($this->user_image->saveAs('gallery/tmpupload/' . $name, true)) {
          if ($user->image) {
            if (file_exists(Yii::$app->basePath . $user->image)) {
              unlink(Yii::$app->basePath . $user->image);
            }

            $dirname = Yii::$app->basePath . '/web/gallery/tmp/user_' . $user->id;

            if (file_exists($dirname)) {
              array_map('unlink', glob("$dirname/*.*"));
            }
          }

          Image::$driver = [Image::DRIVER_GD2];
          $imagine = Image::getImagine();
          $dimensions = getimagesize($tmppath);

          if ($dimensions[0] <= 640 || $dimensions[1] <= 480) {
            $imagine
              ->open($tmppath)
              ->save($regpath, ['quality' => 40]);
          } else {
            Image::resize($tmppath, 640, 480)
              ->save($regpath, ['quality' => 40]);
          }

          $user->image = '/web/gallery/uploads/' . $name;
          $result['image_updated'] = $user->image;
        } else {
          $this->addError('user_image', 'ошибка сохранения изображения');
        }
      }

      $user->save(false);
      $details = $user->details;

      if ($details->load(Yii::$app->request->post(), '')) {
        $details->save();
      }

      $result['validated'] = true;
      return $result;
    }

    $result['validated'] = false;
    return $result;
  }
}
