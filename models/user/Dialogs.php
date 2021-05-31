<?php

namespace app\models\user;

use Yii;

use app\models\User;
use app\models\Products;
use app\models\Requests;

class Dialogs extends \yii\db\ActiveRecord
{

  const STATE_ACTIVE = 0;
  const STATE_TO_DELETE = 1;
  const STATE_DELETED_AND_LOCKED = 2;
  const STATE_DELETED = 3;
  const STATE_LOCKED = 4;

  private $_count = false;

  public static function tableName()
  {
    return 'dialogs';
  }

  public function rules()
  {
    return [
      [['sender_id', 'receiver_id', 'item_id', 'item_type'], 'required'],
      [['sender_id', 'receiver_id', 'item_id'], 'integer'],
      [['item_type'], 'string', 'max' => 24]
    ];
  }

  public function getItem()
  {
    if ($this->item_type == 'product') {
      return $this->hasOne(Products::class, ['id' => 'item_id']);
    } else if ($this->item_type == 'request') {
      return $this->hasOne(Requests::class, ['id' => 'item_id']);
    } else return false;
  }

  public function getLastMessage()
  {
    return $this
      ->hasOne(DialogMessages::class, ['dialog_id' => 'id'])
      ->orderBy('dialog_messages.date_updated DESC');
  }

  public function getMessages($min = 0, $max = 5)
  {
    $user = Yii::$app->user->identity;

    /*
    $messages = DialogMessages::findBySql('SELECT * FROM dialog_messages WHERE dialog_id = ' . $this->id .
      ' ORDER BY date_updated DESC LIMIT ' . $max . ' OFFSET ' . $min)
      ->all();
    */

    $messages = DialogMessages::find()
      ->select([
        'dialog_messages.*',
        'sender_name' => '(SELECT display_name FROM users WHERE id=sender_id)'
      ])
      ->where(['dialog_id' => $this->id])
      ->limit($max)
      ->offset($min)
      ->orderBy('date_updated DESC')
      ->all();

    $str = 'UPDATE dialog_messages SET state=1 WHERE id in (';
    $where = '';

    foreach ($messages as $message) {
      if (!$user || $message->receiver_id != $user->id) continue;

      if ($message->state != 1) {
        if (!empty($where)) $where .= ',';
        $where .= $message->id;
      }
    }

    if (!empty($where)) {
      Yii::$app->db
        ->createCommand($str . $where . ') AND sender_id!=' . $user->id)
        ->execute();
    }

    return $messages;
  }

  public function getMessagesCount()
  {
    if ($this->_count === false) $this->_count = DialogMessages::find()->where('dialog_id = ' . $this->id)->count();
    return $this->_count;
  }

  public function getCompanion()
  {
    if (Yii::$app->user->identity->id == $this->sender_id) {
      return $this->receiver;
    } else {
      return $this->sender;
    }
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
