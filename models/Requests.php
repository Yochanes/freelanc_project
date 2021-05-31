<?php

namespace app\models;

use app\models\user\Contacts;
use app\models\products\RequestAttributes;

class Requests extends \yii\db\ActiveRecord
{
  private $_attribute_values = [];

  public static function tableName()
  {
    return 'requests';
  }

  public function rules()
  {
    return [
      [['make', 'model', 'year', 'year', 'seller_phone'], 'required'],
      [['make', 'model', 'generation', 'city', 'sku', 'number', 'year', 'category', 'seller_name'], 'string', 'max' => 255],
      ['seller_email', 'email'],
      [['seller_phone', 'contact_type'], 'string', 'max' => 20],
      [['text'], 'string', 'max' => 1000],
      [['preorder', 'inform'], 'boolean'],
      [['id', 'target_user_id'], 'integer']
    ];
  }

  public function getActive()
  {
    return strtotime($this->date_updated) >= strtotime('-7 days');
  }

  public function getAttributesArray()
  {
    return $this->hasMany(RequestAttributes::class, ['request_id' => 'id']);
  }

  public function getAttributeValues()
  {
    if ($this->_attribute_values) return $this->_attribute_values;

    if (is_array($this->attributesArray)) {
      foreach ($this->attributesArray as $attr) {
        $this->_attribute_values[$attr['url']] = $attr['value'];
      }
    }

    return $this->_attribute_values;
  }

  public function getContacts()
  {
    return $this->hasOne(Contacts::class, ['user_id' => 'user_id']);
  }

  public function getCountry()
  {
    return $this->hasOne(Countries::class, ['id' => 'country_id']);
  }

  public function getEditUrl($product_group)
  {
    return '/personal/requestadd/' . $product_group['url'] . '/' . $this->id . '/';
  }

  public function getName()
  {
    return $this->make_val . ' ' . $this->model_val . ' ' .  $this->year_val . ' ' . $this->generation_val . ' ' . $this->category_val;
  }

  public function getUrl()
  {
    return 'javascript:void(0)';
  }

  public function getUser()
  {
    return $this->hasOne(User::class, ['id' => 'user_id']);
  }

  public function afterFind()
  {
    parent::afterFind();
    $this->images = !is_null($this->images) && !empty($this->images) ? json_decode($this->images) : [];
  }

  public function beforeSave($insert)
  {
    if (!parent::beforeSave($insert)) {
      return false;
    }

    $this->text = htmlspecialchars($this->text);

    $this->images = isset($this->images) && !empty($this->images) ? json_encode($this->images) : null;
    return true;
  }
}
