<?php

namespace app\models\site;

class URLs extends \yii\db\ActiveRecord
{

  public static function tableName()
  {
    return 'urls';
  }

  public function init()
  {
    parent::init();
    $this->parameters = '';
  }

  public function rules()
  {
    return [
      [['url', 'action'], 'required'],
      [['url', 'action', 'parameters'], 'string'],
      [['id'], 'integer'],
    ];
  }
}
