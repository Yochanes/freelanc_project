<?php

namespace app\models;

use Yii;

class Countries extends \yii\db\ActiveRecord
{

  public function rules()
  {
    return [
      [['name'], 'required', 'message' => Yii::t('app', 'required_field')],
      [['name', 'domain'], 'string', 'max' => 255, 'tooLong' => 'Длина поля должна быть не более 255 символов'],
      ['code', 'string', 'max' => 2, 'tooLong' => 'Длина поля должна быть не более 2 символов'],
      [['id'], 'integer'],
    ];
  }

  public static function tableName()
  {
    return 'countries';
  }

  public function getCities()
  {
    return $this->hasMany(Cities::class, ['country_id' => 'id']);
  }
}
