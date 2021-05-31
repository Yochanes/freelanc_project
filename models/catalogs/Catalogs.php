<?php

namespace app\models\catalogs;

use app\models\products\ProductGroups;

class Catalogs extends \yii\db\ActiveRecord
{

  public function rules()
  {
    return [
      [['title', 'url', 'product_group_id'], 'required'],
      [['title', 'subtitle', 'url'], 'string', 'max' => 255],
      ['product_group_id', 'integer']
    ];
  }

  public static function tableName()
  {
    return 'catalogs';
  }

  public function getProductGroup()
  {
    return $this->hasOne(ProductGroups::class, ['product_group_id' => 'product_group_id']);
  }

  public function getLinksArray()
  {
    return $this->hasMany(Catalog_Links::class, ['catalog_id' => 'id']);
  }

  public function getParamsArray()
  {
    return $this
      ->hasMany(Catalog_Params::class, ['catalog_id' => 'id'])
      ->orderBy('sort_order');
  }
}
