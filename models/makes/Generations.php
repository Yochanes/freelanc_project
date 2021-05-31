<?php

namespace app\models\makes;

class Generations extends \yii\db\ActiveRecord
{
  public function rules()
  {
    return [
      [['name', 'model_id'], 'required'],
      [['name', 'alt_name', 'years'], 'string', 'max' => 255],
      [['id', 'model_id'], 'integer'],
    ];
  }

  public static function tableName()
  {
    return 'make_generations';
  }

}
