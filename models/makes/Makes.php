<?php

namespace app\models\makes;

class Makes extends \yii\db\ActiveRecord
{

  public function rules()
  {
    return [
      [['name'], 'required'],
      [['name', 'image'], 'string', 'max' => 255],
      ['is_popular', 'boolean'],
      ['id', 'integer']
    ];
  }

  public static function tableName()
  {
    return 'makes';
  }

  public function getMakeGroups()
  {
    return $this
      ->hasMany(MakeGroups::class, ['id' => 'make_group_id'])
      ->viaTable('make_to_group', ['make_id' => 'id']);
  }

  public function getModels()
  {
    return $this->hasMany(Models::class, ['make_id' => 'id']);
  }

  public function getModelsCount()
  {
    return $this
      ->hasMany(Models::class, ['make_id' => 'id'])
      ->count();
  }
}
