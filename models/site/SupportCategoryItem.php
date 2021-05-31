<?php

namespace app\models\site;

class SupportCategoryItem extends \yii\db\ActiveRecord
{

  public static function tableName()
  {
    return 'support_category_items';
  }

  public function rules()
  {
    return [
      [['title', 'text', 'category_id'], 'required'],
      [['title'], 'string', 'max' => 255],
      [['text'], 'string'],
      [['sort_order', 'category_id'], 'integer']
    ];
  }
}
