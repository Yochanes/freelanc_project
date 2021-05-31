<?php

namespace app\models\user;

use Yii;
use app\models\Products;

class Curs extends \yii\db\ActiveRecord
{

  public static function tableName()
  {
    return 'user_curs';
  }

  public function rules()
  {
    return [
      [['curs_values', 'curs_scales'], 'string', 'max' => 255],
      [['use_default'], 'boolean'],
    ];
  }

  public function afterFind()
  {
    parent::afterFind();
    $this->curs_values = !is_null($this->curs_values) && $this->curs_values ? json_decode($this->curs_values, true) : [];
    $this->curs_scales = !is_null($this->curs_scales) && $this->curs_scales ? json_decode($this->curs_scales, true) : [];
  }

  public function beforeSave($insert)
  {
    if (parent::beforeSave($insert)) {
      if (is_array($this->curs_values)) {
        $this->curs_values = json_encode($this->curs_values);
      }

      if (is_array($this->curs_scales)) {
        $this->curs_scales = json_encode($this->curs_scales);
      }

      $this->date_updated = date('Y-m-d H:i:s');

      return true;
    }

    return false;
  }
}
