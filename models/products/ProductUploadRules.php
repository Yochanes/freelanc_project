<?php

namespace app\models\products;

class ProductUploadRules extends \yii\db\ActiveRecord
{

  public function rules()
  {
    return [
      [['user_id', 'group_id'], 'required'],
      [['upload_key', 'param_key', 'param_val', 'orig_val', 'maybe_hash', 'comment'], 'string'],
      ['active', 'boolean'],
      [['id', 'user_id', 'group_id'], 'integer'],
    ];
  }

  public static function tableName()
  {
    return 'upload_rules';
  }

  public function getValues()
  {
    return $this->hasOne(ProductUploadRuleValues::class, [
      'maybe_hash' => 'maybe_hash',
      'user_id' => 'user_id',
      'group_id' => 'group_id'
    ]);
  }
}
