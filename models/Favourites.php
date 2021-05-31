<?php

namespace app\models;

class Favourites extends \yii\db\ActiveRecord
{

  public static function tableName()
  {
    return 'user_favourites';
  }

  public function rules()
  {
    return [
      ['user_id', 'required'],
      [['product_id', 'group_id', 'user_id'], 'integer'],
      ['id', 'integer']
    ];
  }
}
