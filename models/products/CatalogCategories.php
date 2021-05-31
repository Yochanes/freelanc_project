<?php

namespace app\models\products;


class CatalogCategories extends \yii\db\ActiveRecord
{

  public function rules()
  {
    return [
      ['name', 'required'],
      [['name', 'url'], 'string', 'max' => 255],
      ['parent_id', 'integer'],
    ];
  }

  public static function tableName()
  {
    return 'product_catalog_categories';
  }

  public function getChildren()
  {
    return $this->hasMany(CatalogCategories::class, ['parent_id' => 'id']);
  }

  public function beforeSave($insert)
  {
    if (!parent::beforeSave($insert)) {
      return false;
    }

    return true;
  }
}
