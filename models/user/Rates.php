<?php

namespace app\models\user;

use app\models\User;

class Rates extends \yii\db\ActiveRecord
{

  public static function tableName()
  {
    return 'user_rates';
  }

  public function rules()
  {
    return [
      [['receiver_id', 'rate', 'item_name', 'item_id', 'item_type'], 'required'],
      [['sender_id', 'receiver_id', 'item_id'], 'integer'],
      ['rate', 'integer', 'min' => 1, 'max' => 5],
      ['item_type', 'string', 'max' => 11],
      [['item_name', 'item_url'], 'string', 'max' => 255],
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

  public function getComments()
  {
    return $this->hasMany(RateComments::class, ['rate_id' => 'id']);
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
