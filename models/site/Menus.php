<?php

namespace app\models\site;


class Menus extends \yii\db\ActiveRecord
{

  public static function tableName()
  {
    return 'menus';
  }

  public function rules()
  {
    return [
      [['label'], 'required'],
      [['name'], 'string', 'max' => 100],
      [['position'], 'string', 'max' => 20],
      [['active'], 'boolean'],
      [['id'], 'integer'],
    ];
  }

  public function getMenuItems()
  {
    return $this->hasMany(MenuItems::class, ['menu_id' => 'id'])
      ->orderBy(['sort_order' => SORT_ASC]);
  }
}
