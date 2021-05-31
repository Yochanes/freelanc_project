<?php

namespace app\models\makes;

class Models extends \yii\db\ActiveRecord
{

  public function rules()
  {
    return [
      [['name', 'make_id'], 'required'],
      ['name', 'string', 'max' => 255],
      ['is_popular', 'boolean'],
      [['id', 'make_id'], 'integer'],
    ];
  }

  public static function tableName()
  {
    return 'make_models';
  }

  public function getGenerations()
  {
    return $this->hasMany(Generations::class, ['model_id' => 'id']);
  }
}
