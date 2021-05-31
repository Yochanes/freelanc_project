<?php

namespace app\models\data;

class Data extends \yii\db\ActiveRecord
{
  const CATEGORIES_FILENAME = 'categories';
  const MAKES_FILENAME = 'makes';

  public function rules()
  {
    return [
      [['filename', 'timestamp', 'version', 'file_version'], 'required'],
      ['filename', 'string', 'max' => 32],
      ['timestamp', 'string', 'max' => 24],
      [['version', 'id', 'file_version'], 'integer'],
    ];
  }

  public static function tableName()
  {
    return 'json_data';
  }
}
