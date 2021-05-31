<?php

namespace app\models\user;

use Yii;
use app\models\Products;

class Contacts extends \yii\db\ActiveRecord
{

  public static function tableName()
  {
    return 'user_contacts';
  }

  public function rules()
  {
    return [
      [['whatsapp', 'viber', 'telegram', 'email'], 'string', 'max' => 255],
      [['email'], 'email', 'skipOnEmpty' => true],
    ];
  }

  public function getPhones()
  {
    $arr = json_decode($this->phone);
    if (!is_array($arr)) $arr = [];
    return $arr;
  }

  public function getProducts()
  {
    return $this->hasMany(Products::class, ['user_id' => 'user_id']);
  }

  public function setPhone($val)
  {
    $this->phone = json_encode($this->phone);
  }

  public function beforeSave($insert)
  {
    if (parent::beforeSave($insert)) {
      if (is_array($this->phone) && !empty($this->phone)) {
        $this->phone = json_encode($this->phone);
      } else if (!is_string($this->phone)) {
        $this->phone = json_encode([]);
      }

      return true;
    }

    return false;
  }
}
