<?php

namespace app\models\products;

class ProductAttributes extends \yii\db\ActiveRecord
{

  public function rules()
  {
    return [
      [['product_id', 'name', 'value', 'url'], 'required'],
      [['name', 'value', 'url'], 'string', 'max' => 255],
      [['attribute_id', 'product_id'], 'integer'],
    ];
  }

  public static function tableName()
  {
    return 'product_attributes';
  }
}
