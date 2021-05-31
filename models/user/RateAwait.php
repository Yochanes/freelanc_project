<?php

namespace app\models\user;

class RateAwait extends \yii\db\ActiveRecord
{

  const STATE_UNUSED = 0;
  const STATE_USED = 1;

  public static function tableName()
  {
    return 'user_rate_await';
  }

  public function rules()
  {
    return [
      [['user_id', 'obj_id', 'obj_type'], 'required'],
      [['user_id', 'obj_id'], 'integer'],
      ['obj_type', 'string']
    ];
  }
}
