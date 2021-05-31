<?php

namespace app\models;

use Yii;

class Cities extends \yii\db\ActiveRecord
{

  public function rules()
  {
    return [
      [['name', 'country_id'], 'required', 'message' => Yii::t('app', 'required_field')],
      [['name', 'region', 'domain'], 'string', 'max' => 255, 'tooLong' => 'Длина поля должна быть не более 255 символов'],
      [['id', 'country_id'], 'integer'],
    ];
  }

  public static function tableName()
  {
    return 'cities';
  }

}
