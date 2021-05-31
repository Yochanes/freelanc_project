<?php

namespace app\models\user;

use app\models\User;
use Yii;

class NotificationDialogs extends \yii\db\ActiveRecord
{

  const STATE_ACTIVE = 0;
  const STATE_TO_DELETE = 1;
  const STATE_DELETED_AND_LOCKED = 2;
  const STATE_DELETED = 3;
  const STATE_LOCKED = 4;

  private $_count = false;

  public static function tableName()
  {
    return 'notification_dialogs';
  }

  public function rules()
  {
    return [
      [['sender_id', 'receiver_id'], 'required'],
      [['sender_id', 'receiver_id'], 'integer'],
    ];
  }

  public function getLastMessage()
  {
    return $this->hasOne(NotificationDialogMessages::class, ['dialog_id' => 'id'])
      ->orderBy('date_updated DESC');
  }

  public function getMessages($min = 0, $max = 5)
  {
    $user = Yii::$app->user->identity;

    $messages = NotificationDialogMessages::findBySql('SELECT * FROM notification_dialog_messages WHERE dialog_id = ' . $this->id . ' ORDER BY date_updated DESC LIMIT ' . $max . ' OFFSET ' . $min)->all();

    $str = 'UPDATE notification_dialog_messages SET state = 1 WHERE id in (';
    $where = '';

    foreach ($messages as $message) {
      if (!$user || $this->receiver_id != $user->id) continue;

      if ($message->state != 1) {
        if (!empty($where)) $where .= ',';
        $where .= $message->id;
      }
    }

    if (!empty($where)) {
      Yii::$app->db->createCommand($str . $where . ')')->execute();
    }

    return $messages;
  }

  public function getMessagesCount()
  {
    if ($this->_count === false) $this->_count = DialogMessages::find()->where('dialog_id = ' . $this->id)->count();
    return $this->_count;
  }

  public function getReceiver()
  {
    return $this->hasOne(User::class, ['id' => 'receiver_id']);
  }

  public function getSender()
  {
    return $this->hasOne(User::class, ['id' => 'sender_id']);
  }

  public function getState()
  {
    if (Yii::$app->user->identity->id  == $this->sender_id) {
      return $this->sender_state;
    } else {
      return $this->receiver_state;
    }
  }
}
