<?php

namespace app\models\user;

use Yii;

class Statistics extends \yii\db\ActiveRecord
{

  public function rules()
  {
    return [
      [['user_id', 'group_id', 'date'], 'required'],
      ['date', 'string'],
      [['user_id', 'group_id', 'clicks', 'views'], 'integer']
    ];
  }

  public static function tableName()
  {
    return 'user_statistics';
  }

  public function addClick($product_id)
  {
    $arr = $this->products;

    if (!isset($arr[$product_id])) {
      $arr[$product_id] = array(
        'clicks' => 0,
        'views' => 0
      );
    } else if (!isset($arr[$product_id]['clicks'])) $arr[$product_id]['clicks'] = 0;

    $arr[$product_id]['clicks']++;
    $this->products = $arr;
  }

  public function addView($product_id)
  {
    $arr = $this->products;

    if (!isset($arr[$product_id])) {
      $arr[$product_id] = array(
        'clicks' => 0,
        'views' => 0
      );
    } else if (!isset($arr[$product_id]['views'])) $arr[$product_id]['views'] = 0;

    $arr[$product_id]['views']++;
    $this->products = $arr;
  }

  public function afterFind()
  {
    parent::afterFind();
    $this->products = !is_null($this->products) && $this->products ? json_decode($this->products, true) : [];
  }

  public function beforeSave($insert)
  {
    if (!parent::beforeSave($insert)) {
      return false;
    }

    $this->products = json_encode($this->products);
    return true;
  }
}
