<?php

namespace app\models\products;

use Yii;

class RequestAttributes extends \yii\db\ActiveRecord
{

  private $_attributes = false;

  public function rules()
  {
    return [
      [['request_id', 'name', 'value', 'url'], 'required'],
      [['name', 'value', 'url'], 'string', 'max' => 255],
      [['attribute_id', 'request_id'], 'integer'],
    ];
  }

  public static function tableName()
  {
    return 'request_attributes';
  }
}
