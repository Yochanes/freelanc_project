<?php

namespace app\models\products;

class CategoryAttributes extends \yii\db\ActiveRecord
{

  public function rules()
  {
    return [
      [['value', 'attribute_group_id'], 'required'],
      [['value', 'catalog_text'], 'string', 'max' => 255],
      [['attribute_id', 'attribute_group_id'], 'integer'],
    ];
  }

  public static function tableName()
  {
    return 'product_category_attributes';
  }

  public function getId()
  {
    return $this->attribute_id;
  }
}
