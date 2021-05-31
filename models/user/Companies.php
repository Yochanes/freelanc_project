<?php

namespace app\models\user;

use Yii;

class Companies extends \yii\db\ActiveRecord
{

  public static function tableName()
  {
    return 'user_companies';
  }

  public function rules()
  {
    return [
      [['address', 'inn', 'ogrn', 'name', 'is_visible'], 'required'],
      [['address', 'inn', 'ogrn', 'name'], 'string', 'max' => 255],
      [['info'], 'string', 'max' => 1000],
      [['is_visible'], 'boolean'],
    ];
  }

  public function beforeSave($insert)
  {
    if (!parent::beforeSave($insert)) {
      return false;
    }

    $this->name = htmlspecialchars($this->name);
    $this->info = htmlspecialchars($this->info);
    $this->address = htmlspecialchars($this->address);
    return true;
  }
}
