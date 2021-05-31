<?php

namespace app\models\products;

use app\models\helpers\Helpers;

class Categories extends \yii\db\ActiveRecord
{

  public function rules()
  {
    return [
      ['name', 'required'],
      ['name', 'string', 'max' => 255],
      [['category_id', 'catalog_category_id'], 'integer'],
      ['attributes_required', 'each', 'rule' => ['integer']],
      [['generation_required', 'partnum_required'], 'boolean']
    ];
  }

  public static function tableName()
  {
    return 'product_categories';
  }

  public static function createUrl($name)
  {
    if (substr($name, 0, 3) !== 'zp-') {
      return 'zp-' . $name;
    }

    return $name;
  }

  public static function createUrlFromCyrillic($name)
  {
    return self::createUrl(Helpers::translaterUrl($name));
  }

  public function getId()
  {
    return $this->category_id;
  }

  public function validateData($req, $key_suf = 'attribute_', $ignore_attrs = false)
  {
    $result = [
      'validated' => true,
      'errors' => []
    ];

    foreach ($this->attributes_required as $attr_id) {
      if (!isset($req["$key_suf$attr_id"]) || empty($req["$key_suf$attr_id"])) {
        if ($ignore_attrs && strpos($key_suf, 'attribute_') !== false) continue;
        $result['validated'] = false;
        $result['errors']["$key_suf$attr_id"] = 'Этот параметр должен быть выбран';
      }
    }

    return $result;
  }

  public function afterFind()
  {
    parent::afterFind();
    $this->attributes_required = !is_null($this->attributes_required) && !empty($this->attributes_required) ? json_decode($this->attributes_required) : [];
    $this->connected_attributes = !is_null($this->connected_attributes) && !empty($this->connected_attributes) ? json_decode($this->connected_attributes) : [];
  }

  public function beforeSave($insert)
  {
    if (!parent::beforeSave($insert)) {
      return false;
    }

    $this->url = self::createUrl($this->url);

    if ($this->parent_url) {
      $this->parent_url = self::createUrl($this->parent_url);
    }

    if (isset($this->connected_attributes) && $this->connected_attributes && !is_string($this->connected_attributes)) {
      $this->connected_attributes = json_encode($this->connected_attributes);
    } else {
      $this->connected_attributes = null;
    }

    $this->attributes_required = isset($this->attributes_required) && !empty($this->attributes_required) ? json_encode($this->attributes_required) : null;
    return true;
  }
}
