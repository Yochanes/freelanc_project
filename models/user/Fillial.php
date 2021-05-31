<?php

namespace app\models\user;

use Yii;

class Fillial extends \yii\db\ActiveRecord
{

  public static function tableName()
  {
    return 'user_fillialz';
  }

  public function rules()
  {
    return [
      [['name', 'country', 'city', 'address', 'time'], 'required', 'message' => 'Это поле должно быть заполнено обязательно'],
      [['name', 'whatsapp', 'viber', 'telegram', 'address', 'city', 'country'], 'string', 'max' => 255, 'tooLong' => 'Длина этого поля не может превышать 255 символов'],
      ['time', 'string', 'max' => 500, 'tooLong' => 'Длина этого поля не может превышать 500 символов'],
      [['id'], 'integer']
    ];
  }

  public function getPhones()
  {
    $arr = json_decode($this->phone);
    if (!is_array($arr)) $arr = [];
    return $arr;
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

    $this->name = htmlspecialchars($this->name);
    $this->address = htmlspecialchars($this->address);
    return false;
  }
}
