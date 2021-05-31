<?php

namespace app\models\user;

use Yii;

class CarAttributes extends \yii\db\ActiveRecord
{

  public function rules()
  {
    return [
      [['car_id', 'name', 'value', 'url', 'attribute_group_id'], 'required'],
      [['name', 'value', 'url'], 'string', 'max' => 255],
      [['attribute_id', 'car_id', 'attribute_group_id'], 'integer'],
    ];
  }

  public static function tableName()
  {
    return 'user_cars_attributes';
  }
}
