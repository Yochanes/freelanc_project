<?php

namespace app\models\user;

class ProfileStatistics extends \yii\db\ActiveRecord
{

  public function rules()
  {
    return [
      [['user_id', 'date'], 'required'],
      ['date', 'string'],
      [['user_id', 'clicks', 'views'], 'integer']
    ];
  }

  public static function tableName()
  {
    return 'profile_statistics';
  }

  public function addClick()
  {
    $this->clicks++;
  }

  public function addView()
  {
    $this->views++;
  }
}
