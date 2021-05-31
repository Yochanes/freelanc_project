<?php

namespace app\models\products;

class CategoryAttributeGroups extends \yii\db\ActiveRecord
{

  public function rules()
  {
    return [
      [['name', 'filter_name', 'important', 'use_in_car_form'], 'required'],
      [['name', 'filter_name', 'catalog_suffix', 'catalog_prefix'], 'string', 'max' => 255],
      ['url_template', 'string', 'max' => 100],
      [['important', 'use_in_car_form', 'use_in_category'], 'boolean'],
      [['attribute_group_id', 'sort_order'], 'integer'],
    ];
  }

  public static function tableName()
  {
    return 'product_category_attribute_groups';
  }

  public function getId()
  {
    return $this->attribute_group_id;
  }

  public function getAttributesArray()
  {
    return $this
      ->hasMany(CategoryAttributes::class, ['attribute_group_id' => 'attribute_group_id'])
      ->orderBy('value');
  }
}
