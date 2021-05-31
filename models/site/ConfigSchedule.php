<?php

namespace app\models\site;

class ConfigSchedule extends \yii\db\ActiveRecord
{

  public static function tableName()
  {
    return 'config_schedule';
  }

  public function rules()
  {
    return [
      [['token', 'curs_schedule', 'request_schedule', 'curs_schedule_rate', 'request_schedule_rate'], 'required'],
      ['token', 'string', 'max' => 255],
      [['curs_schedule', 'request_schedule'], 'boolean'],
      [['curs_schedule_rate', 'request_schedule_rate'], 'integer'],
      ['id', 'integer'],
    ];
  }
}
