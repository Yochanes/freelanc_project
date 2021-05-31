<?php

namespace app\models\user;

use app\models\User;

class Complaints extends \yii\db\ActiveRecord
{
  /**
   * {@inheritdoc}
   */
  public static function tableName()
  {
    return 'complaints';
  }

  public function rules()
  {
    return [
      [['user_id', 'target_id', 'text'], 'required'],
      [['user_id', 'target_id'], 'integer'],
      [['target_name', 'target_url'], 'string', 'max' => 255],
      [['text'], 'string', 'max' => 2000]
    ];
  }

  public function getTarget()
  {
    return $this->hasOne(User::class, ['id' => 'target_id']);
  }

  public function getUser()
  {
    return $this->hasOne(User::class, ['id' => 'user_id']);
  }

  public function beforeSave($insert)
  {
    if (!parent::beforeSave($insert)) {
      return false;
    }

    $this->text = htmlspecialchars($this->text);
    return true;
  }
}
