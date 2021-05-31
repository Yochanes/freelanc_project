<?php

namespace app\models\user;

use app\models\User;

class RateComments extends \yii\db\ActiveRecord
{

  private $_sender = false;
  private $_receiver = false;

  public static function tableName()
  {
    return 'user_rate_comments';
  }

  public function rules()
  {
    return [
      [['sender_id', 'receiver_id', 'rate_id'], 'required'],
      [['sender_id', 'receiver_id', 'rate_id'], 'integer'],
      [['text'], 'string', 'max' => 1000]
    ];
  }

  public function getReceiver()
  {
    if ($this->_receiver === false) {
      $this->_receiver = User::find()->select('id, username, display_name')->where('id = ' . $this->receiver_id)->one();
    }

    return $this->_receiver;
  }

  public function getSender()
  {
    if ($this->_sender === false) {
      $this->_sender = User::find()->select('id, username, display_name')->where('id = ' . $this->sender_id)->one();
    }

    return $this->_sender;
  }

  public function beforeSave($insert)
  {
    if (!parent::beforeSave($insert)) {
      return false;
    }

    $this->text = htmlspecialchars($this->text);
    return true;
  }
}
