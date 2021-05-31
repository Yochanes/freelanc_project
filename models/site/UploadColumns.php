<?php

namespace app\models\site;

class UploadColumns extends \yii\db\ActiveRecord
{

  public static function tableName()
  {
    return 'upload_columns';
  }

  public function rules()
  {
    return [
      [['param_name', 'text_value'], 'required'],
      [['param_name', 'text_value'], 'string'],
      [['id', 'group_id'], 'integer'],
    ];
  }
}
