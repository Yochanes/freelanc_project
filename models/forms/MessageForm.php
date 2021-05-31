<?php

namespace app\models\forms;

use Yii;
use yii\base\Model;
use yii\web\UploadedFile;

use app\models\user\Dialogs;
use app\models\user\DialogMessages;

use app\models\helpers\Helpers;
use app\models\config\SMTP;

use yii\imagine\Image;

class MessageForm extends Model
{
  public $sender_id;
  public $dialog_id;
  public $receiver_id;
  public $item_id;
  public $item_type;
  public $text;
  public $imgs;

  public function rules()
  {
    return [
      [['receiver_id', 'item_id', 'item_type'], 'required', 'message' => 'Необходимые данные отсутствуют'],
      [['receiver_id', 'item_id', 'dialog_id'], 'integer'],
      [['text'], 'string', 'max' => 1000, 'tooLong' => 'Значение этого поля не может превышать 1000 символов'],
      ['item_type', 'string', 'max' => 24],
      ['imgs', 'each', 'rule' => ['file', 'extensions' => 'png, jpg, jpeg, gif'], 'skipOnEmpty' => true],
    ];
  }

  public function saveData()
  {
    $this->imgs = UploadedFile::getInstancesByName('imgs');
    $result = array();
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
      $check = Yii::$app->db
        ->createCommand('SELECT COUNT(*) FROM dialogs WHERE id="' . $this->dialog_id . '"' .
				  ' AND ((sender_id="' . $user->id . '"' .
          ' AND (receiver_state="' . Dialogs::STATE_DELETED_AND_LOCKED . '" OR receiver_state="' . Dialogs::STATE_LOCKED . '"))' .
          ' OR (receiver_id="' . $user->id . '"' .
          ' AND (sender_state="' . Dialogs::STATE_DELETED_AND_LOCKED . '" OR sender_state="' . Dialogs::STATE_LOCKED . '")))')
        ->queryScalar();

      if ($check) {
        $result['error'] = 'Пользователь заблокировал ваши сообщения';
        return $result;
      }

      $dialog = Dialogs::find()
        ->where('id="' . $this->dialog_id . '"' .
				  ' AND ((sender_id="' . $user->id . '" AND sender_state="' . Dialogs::STATE_ACTIVE . '")'.
          ' OR (receiver_id="' . $user->id . '" AND receiver_state="' . Dialogs::STATE_ACTIVE . '"))')
        ->one();
    } else {
      $check = Yii::$app->db
        ->createCommand('SELECT COUNT(*) FROM dialogs WHERE
          ((sender_id="' . $user->id . '"' .
          ' AND (receiver_state="' . Dialogs::STATE_DELETED_AND_LOCKED . '" OR receiver_state="' . Dialogs::STATE_LOCKED . '"))'.
          ' OR (receiver_id="' . $this->receiver_id . '"' .
          ' AND (sender_state="' . Dialogs::STATE_DELETED_AND_LOCKED . '" OR sender_state="' . Dialogs::STATE_LOCKED . '")))')
        ->queryScalar();

      if ($check) {
        $result['error'] = 'Пользователь заблокировал ваши сообщения';
        return $result;
      }

      $dialog = Dialogs::find()
        ->where('(sender_id="' . $user->id . '"' .
          ' AND sender_state="' . Dialogs::STATE_ACTIVE . '")' .
          ' OR (receiver_id="' . $this->receiver_id . '"' .
          ' AND receiver_state="' . Dialogs::STATE_ACTIVE . '")')
        ->one();
    }

    if (!$dialog) {
      $dialog = new Dialogs();
      $dialog->sender_id = $user->id;

      if (!$dialog->load(Yii::$app->request->post(), '')) {
        $result['error'] = 'Ошибка сохранения сообщения: некорректные данные запроса';
        return $result;
      }

      if ($dialog->item_type == 'product') {
        $product = $dialog->item;
        if ($product) $dialog->item_search = $product->name;
      }

      if (!$dialog->save()) {
        $result['error'] = 'Ошибка сохранения сообщения: диалог не сохранен';
        $result['errors'] = $dialog->errors;
        return $result;
      }
    }

    if ($this->validate() && ($this->text || $this->imgs)) {
      if ($dialog) {
        $message = new DialogMessages();
        $message->sender_id = $user->id;
        $message->dialog_id = $dialog->id;
        $message->item_id = $this->item_id;
        $message->item_type = $this->item_type;

        if ($message->item_type == 'product') {
          $product = $message->item;
          if ($product) $message->item_search = $product->name;
        }

        Image::$driver = [Image::DRIVER_GD2];
        $imagine = Image::getImagine();
        $rimgs = [];
        $fimgs = [];
        $new_images = [];

        foreach ($this->imgs as $key => $image) {
          $name = 'messages_' . $user->id . '_' . Yii::$app->security->generateRandomString(20) . '.' . $image->extension;

          if ($image->saveAs('gallery/tmpupload/' . $name, true)) {
            $iname = '/web/gallery/messages/' . $name;
            $tmppath = Yii::$app->basePath . '/web/gallery/tmpupload/' . $name;
            $regpath = Yii::$app->basePath . $iname;
            $dimensions = getimagesize($tmppath);

            if ($dimensions[0] <= 640 || $dimensions[1] <= 480) {
              $imagine
                ->open($tmppath)
                ->save($regpath, ['quality' => 40]);
            } else {
              Image::resize($tmppath, 640, 480)
                ->save($regpath, ['quality' => 40]);
            }

            $new_images[] = $iname;
            $fimgs[] = $iname;
            $rimgs[] = Helpers::getImageByURL($iname, 100, 100);
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
            ->createCommand('SELECT uc.on_message, ucc.email FROM user_configs uc
              LEFT JOIN user_contacts ucc ON ucc.user_id = uc.user_id 
              WHERE uc.user_id=' . $this->receiver_id . ' AND ucc.email_approved=1 LIMIT 1')
            ->queryOne();

          if ($notify && $notify['on_message']) {
            $cfg = SMTP::find()->where('active=1')->one();

            try {
              if ($cfg) {
                $to = $notify['email'];
                $subject = 'У вас новое сообщение';
                $text = '<p>У вас новое сообщение</p>
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

          $result['validated'] = false;
          $result['errors'] = $message->errors;
          $result['error'] = 'Ошибка сохранения: некорректные данные запроса';
        }
      } else {
        $result['validated'] = false;
        $result['error'] = 'Ошибка сохранения: диалог не найден';
      }
    } else {
      $result['validated'] = false;
      $result['error'] = 'Ошибка сохранения: сообщение не может быть пустым';
    }

    return $result;
  }
}
