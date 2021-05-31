<?php

namespace app\models\products;

class ProductUploadRuleValues extends \yii\db\ActiveRecord
{

  public function rules()
  {
    return [
      [['maybe_hash', 'class_name', 'user_id', 'group_id'], 'required'],
      [['maybe_hash', 'class_name', 'where_str'], 'string'],
      [['id', 'user_id', 'group_id'], 'integer'],
    ];
  }

  public static function tableName()
  {
    return 'upload_rules_values';
  }
}
