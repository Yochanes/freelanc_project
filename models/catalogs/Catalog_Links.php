<?php

namespace app\models\catalogs;

class Catalog_Links extends \yii\db\ActiveRecord
{

  public function rules()
  {
    return [
      [['catalog_id', 'text', 'href'], 'required'],
      [['text', 'href'], 'string', 'max' => 255],
      [['catalog_id'], 'integer']
    ];
  }

  public static function tableName()
  {
    return 'catalog_links';
  }
}
