<?php

namespace app\models\user;

use app\models\User;
use app\models\Products;
use app\models\Requests;

class DialogMessages extends \yii\db\ActiveRecord
{

  const STATE_READ = 1;
  const STATE_UNREAD = 0;
  public $sender_name;

  public static function tableName()
  {
    return 'dialog_messages';
  }

  public function rules()
  {
    return [
      [['sender_id', 'receiver_id', 'dialog_id', 'item_id', 'item_type'], 'required'],
      [['sender_id', 'receiver_id', 'dialog_id', 'item_id'], 'integer'],
      [['text'], 'string', 'max' => 1000],
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
