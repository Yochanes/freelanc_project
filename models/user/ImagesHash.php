<?php

namespace app\models\user;

class ImagesHash extends \yii\db\ActiveRecord
{

    public static function tableName()
    {
        return 'images_hash';
    }

  public function rules()
  {
    return [
      [['hash', 'url', 'user_id'], 'required'],
      [['id', 'user_id'], 'integer'],
      [['hash', 'url'], 'string', 'max' => 255],
    ];
  }
}
