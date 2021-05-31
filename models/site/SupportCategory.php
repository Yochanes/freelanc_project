<?php

namespace app\models\site;

class SupportCategory extends \yii\db\ActiveRecord
{

  public static function tableName()
  {
    return 'support_categories';
  }

  public function rules()
  {
    return [
      [['title'], 'required'],
      [['title', 'image'], 'string', 'max' => 255],
      [['sort_order', 'parent_id'], 'integer']
    ];
  }

  public function getCategoryItems()
  {
    return $this->hasMany(SupportCategoryItem::class, ['category_id' => 'id'])
      ->orderBy(['sort_order' => SORT_ASC]);
  }
}
