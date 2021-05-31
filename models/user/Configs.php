<?php

namespace app\models\user;

class Configs extends \yii\db\ActiveRecord
{

  public static function tableName()
  {
    return 'user_configs';
  }

  public function rules()
  {
    return [
      [['skip_upload_errors', 'skip_upload_duplicates', 'on_message', 'on_ntf', 'on_status_change', 'requests_email'], 'boolean'],
      [['requests_makes'], 'each', 'rule' => ['string']],
      [['requests_product_groups'], 'each', 'rule' => ['integer']],
      ['template', 'string', 'max' => 50]
    ];
  }

  public function afterFind()
  {
    parent::afterFind();
    $this->requests_product_groups = !is_null($this->requests_product_groups) && $this->requests_product_groups ? json_decode($this->requests_product_groups, true) : [];
    $this->requests_makes = json_decode($this->requests_makes, true);
  }

  public function beforeSave($insert)
  {
    if (parent::beforeSave($insert)) {
      $this->requests_product_groups = json_encode($this->requests_product_groups);
      $this->requests_makes = json_encode($this->requests_makes);
      return true;
    }

    return false;
  }
}
