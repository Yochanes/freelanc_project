<?php

namespace app\models\user;

use Yii;

class Searches extends \yii\db\ActiveRecord
{
  /**
   * {@inheritdoc}
   */
  public static function tableName()
  {
    return 'user_search';
  }

  public function rules()
  {
    return [
      [['user_id', 'url', 'title'], 'required'],
      ['url', 'string', 'max' => 255],
      ['title', 'string', 'max' => 100],
      [['user_id', 'id'], 'integer']
    ];
  }
}
