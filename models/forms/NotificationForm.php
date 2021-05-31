<?php

namespace app\models\forms;

use Yii;
use yii\base\Model;
use yii\web\UploadedFile;

use app\models\user\NotificationDialogs;
use app\models\user\NotificationDialogMessages;
use app\models\config\SMTP;

use app\models\helpers\Helpers;
use yii\imagine\Image;

class NotificationForm extends Model
{
  public $sender_id;
  public $dialog_id;
  public $receiver_id;
  public $text;
  public $imgs;

  public function rules()
  {
    return [
      [['receiver_id'], 'required', 'message' => 'Необходимые данные отсутствуют'],
      [['receiver_id', 'dialog_id'], 'integer'],
      [['text'], 'string', 'max' => 1000, 'tooLong' => 'Значение этого поля не может превышать 1000 символов'],
      ['imgs', 'each', 'rule' => ['file', 'extensions' => 'png, jpg, jpeg, gif'], 'skipOnEmpty' => true],
    ];
  }

  public function saveData()
  {
    $this->imgs = UploadedFile::getInstancesByName('imgs');
    $result = array('validated' => false);

    $dialog = false;
    $user = Yii::$app->user->identity;

    if (!$this->text && !$this->imgs) {
      $result['error'] = 'Ошибка';
      return $result;
    }

    if ($this->receiver_id == $user->id) {
      $result['error'] = 'Ошибка';
      return $result;
    }

    if (!empty($this->dialog_id)) {
      $dialog = NotificationDialogs::find()
        ->where('id=' . $this->dialog_id . ' 
				  AND (sender_id=' . $user->id . ' OR receiver_id=' . $user->id . ')')
        ->one();
    } else {
      $dialog = NotificationDialogs::find()
        ->where('sender_id=' . ($user->id != $this->receiver_id ? $user->id : $this->receiver_id) . ' 
				    AND receiver_id=' . ($user->id != $this->receiver_id ? $this->receiver_id : $user->id))
        ->one();
    }

    if (!$dialog) {
      $dialog = new NotificationDialogs();
      $dialog->sender_id = $user->id;

      if (!$dialog->load(Yii::$app->request->post(), '')) {
        $result['error'] = 'Ошибка сохранения сообщения: некорректные данные запроса';
        return $result;
      }

      if (!$dialog->save()) {
        $result['error'] = 'Ошибка сохранения сообщения: некорректные данные запроса';
        return $result;
      }
    }

    if ($this->validate() && ($this->text || $this->imgs)) {
      if ($dialog) {
        $message = new NotificationDialogMessages();
        $message->sender_id = $user->id;
        $message->dialog_id = $dialog->id;

        Image::$driver = [Image::DRIVER_GD2];
        $imagine = Image::getImagine();
        $rimgs = [];
        $fimgs = [];
        $new_images = [];

        foreach ($this->imgs as $key => $image) {
          $name = 'ntfs_' . $user->id . '_' . Yii::$app->security->generateRandomString(20) . '.' . $image->extension;

          if ($image->saveAs('gallery/tmpupload/' . $name, true)) {
            $tmppath = Yii::$app->basePath . '/web/gallery/tmpupload/' . $name;
            $regpath = Yii::$app->basePath . '/web/gallery/notifications/' . $name;

            $dimensions = getimagesize($tmppath);

            if ($dimensions[0] <= 640 || $dimensions[1] <= 480) {
              $imagine
                ->open($tmppath)
                ->save($regpath, ['quality' => 30]);
            } else {
              Image::resize($tmppath, 640, 480)
                ->save($regpath, ['quality' => 30]);
            }

            $new_images[] = '/web/gallery/notifications/' . $name;
            $fimgs[] = '/web/gallery/notifications/' . $name;
            $rimgs[] = Helpers::getImageByURL('/web/gallery/notifications/' . $name, 100, 100);
            unlink($tmppath);
          }
        }

        $message->images = $new_images;

        if ($message->load(Yii::$app->request->post(), '') && $message->save()) {
          $dialog->date_updated = date('Y-m-d H:i:s');
          $dialog->save();

          $dt = new \DateTime($message->date_updated);

          $result['text'] = $message->text;
          $result['imgs'] = $rimgs;
          $result['orig_imgs'] = $fimgs;
          $result['date'] = $dt->format('H') . ':' . $dt->format('i');
          $result['error'] = false;
          $result['validated'] = true;
          $result['success'] = true;

          $notify = Yii::$app->db
            ->createCommand('SELECT uc.on_ntf, ucc.email FROM user_configs uc
              LEFT JOIN user_contacts ucc ON ucc.user_id = uc.user_id 
              WHERE uc.user_id=' . $this->receiver_id . ' AND ucc.email_approved=1 LIMIT 1')
            ->queryOne();

          if ($notify && $notify['on_ntf']) {
            $cfg = SMTP::find()->where('active=1')->one();

            try {
              if ($cfg) {
                $to = $notify['email'];
                $subject = 'У вас новое уведомление';
                $text = '<p>У вас новое уведомление</p>
									<p>Пользователь <strong>' . $dialog->sender['display_name'] . '</strong> отправил вам новое сообщение:</p>
									<p>' . $message->text ?: '[Сообщение содержит только изображения]' . '</p>';

                $cfg->sendEmail($to, $subject, '', $text);
              }
            } catch (\Exception $e) {}
          }

          return $result;
        } else {
          foreach ($new_images as $img) {
            unlink(Yii::$app->basePath . $img);
          }

          $result['errors'] = $message->errors;
          $result['error'] = 'Ошибка сохранения: некорректные данные запроса';
        }
      } else {
        $result['error'] = 'Ошибка сохранения: некорректные данные запроса';
      }
    } else {
      $result['error'] = 'Ошибка сохранения: некорректные данные запроса';
    }

    return $result;
  }
}
