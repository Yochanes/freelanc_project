<?php

namespace app\models\user;

use Yii;
use app\models\User;

class NotificationDialogMessages extends \yii\db\ActiveRecord
{

  public static function tableName()
  {
    return 'notification_dialog_messages';
  }

  public function rules()
  {
    return [
      [['sender_id', 'receiver_id', 'dialog_id'], 'required'],
      [['sender_id', 'receiver_id', 'dialog_id'], 'integer'],
      [['text'], 'string', 'max' => 1000]
    ];
  }

  public function getReceiver()
  {
    return $this->hasOne(User::class, ['id' => 'receiver_id']);
  }

  public function getSender()
  {
    return $this->hasOne(User::class, ['id' => 'sender_id']);
  }

  public function afterFind()
  {
    parent::afterFind();
    $this->images = !is_null($this->images) && !empty($this->images) ? json_decode($this->images) : [];
  }

  public function beforeSave($insert)
  {
    if (!parent::beforeSave($insert)) {
      return false;
    }

    $this->text = htmlspecialchars($this->text);
    $this->images = isset($this->images) && !empty($this->images) ? json_encode($this->images) : null;
    return true;
  }
}
