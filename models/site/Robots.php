<?php

namespace app\models\site;

class Robots extends \yii\db\ActiveRecord
{

  public static function tableName()
  {
    return 'config_robots';
  }

  public function rules()
  {
    return [
      [['content', 'url'], 'required'],
      ['content', 'string'],
      [['url'], 'string', 'max' => 255],
      ['default_flag', 'boolean'],
      ['id', 'integer'],
    ];
  }
}
