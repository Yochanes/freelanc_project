<?php

namespace app\models\site;

class MenuItems extends \yii\db\ActiveRecord
{

  public static function tableName()
  {
    return 'menu_items';
  }

  public function rules()
  {
    return [
      [['label', 'menu_id', 'sort_order'], 'required'],
      [['label', 'classname'], 'string', 'max' => 100],
      [['url', 'not_loggedin_url', 'info'], 'string', 'max' => 255],
      [['menu_id', 'parent_id', 'id', 'sort_order'], 'integer']
    ];
  }

  public function getChildren()
  {
    return $this->hasMany(MenuItems::class, ['parent_id' => 'id'])->orderBy(['sort_order' => SORT_ASC]);
  }

  public function beforeDelete()
  {
    if (!parent::beforeDelete()) {
      return false;
    }

    MenuItems::deleteAll('parent_id = ' . $this->id);
    return true;
  }

  public function beforeSave($insert)
  {
    if (!parent::beforeSave($insert)) {
      return false;
    }

    $this->label = html_entity_decode($this->label);
    $this->info = html_entity_decode($this->info);

    if ($this->parent_id) {
      $this->menu_id = null;
    }

    return true;
  }
}
