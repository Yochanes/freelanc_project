<?php

namespace app\models;

use app\models\user\Contacts;
use app\models\products\ProductAttributes;

class Products extends \yii\db\ActiveRecord
{

  /*
    status available options
    - 0 - Активно
    - 1 - Неактивно
    - 2 - Черновик
    - 3 - Отклонено
    - 4 - На проверке
    - 6 - Заблокировано
    - 7 - Удалено
  */

  private $_name = false;
  private $_attributes = [];
  private $_attribute_values = [];

  const STATE_ACTIVE = '0';
  const STATE_INACTIVE = '1';
  const STATE_DRAFT = '2';
  const STATE_REJECTED = '3';
  const STATE_UNCHECKED = '4';
  const STATE_LOCKED = '6';
  const STATE_DELETED = '7';

  const MIN_PRODUCT_YEAR = 1980;

  public static function tableName()
  {
    return 'products';
  }

  public function init()
  {
    parent::init();
    $this->status = Products::STATE_ACTIVE;
    $this->sale = 0;
    $this->currency = '';
    $this->quantity = 1;
    $this->available = 1;
    $this->views = 0;
    $this->phone_views = 0;
    $this->favourites = 0;
    $this->date_created = date('Y-m-d H:i:s');
    $this->date_updated = date('Y-m-d H:i:s');
  }

  public function rules()
  {
    return [
      [['city', 'seller_phone'], 'required'],
      [['make_val', 'model_val', 'generation_val', 'year_val', 'category_val'], 'string'],
      [['sku', 'partnum', 'make', 'model', 'city', 'generation', 'category'], 'string', 'max' => 100],
      [['currency', 'year'], 'string', 'max' => 30],
      [['short_description', 'years', 'seller_name', 'address'], 'string', 'max' => 255],
      ['seller_email', 'email'],
      [['seller_phone', 'contact_type'], 'string', 'max' => 20],
      [['text'], 'string', 'max' => 1000],
      [['available'], 'boolean'],
      ['upload_key', 'string', 'max' => 16],
      [['quantity'], 'integer', 'min' => 0, 'max' => 999999],
      [['sale'], 'integer', 'min' => 0, 'max' => 100],
      [['price'], 'number', 'min' => 0, 'max' => 9999999999],
      [['status'], 'integer', 'min' => 0, 'max' => 7],
      [['width', 'height', 'length', 'weight'], 'double', 'min' => 0, 'max' => 999999],
    ];
  }

  public function getAttributesArray()
  {
    return $this->hasMany(ProductAttributes::class, ['product_id' => 'id']);
  }

  public function getAttributeIds()
  {
    if ($this->_attributes) return $this->_attributes;

    if (is_array($this->attributesArray)) {
      foreach ($this->attributesArray as $attr) {
        $this->_attributes['attribute_' . $attr['attribute_id']] = $attr['url'];
      }
    }

    return $this->_attributes;
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

  public function getName()
  {
    if ($this->_name) return $this->_name;
    $name = $this->name_template;
    $pattern = '/\{\{([^\s]+)\}\}/s';
    $matches = array();
    preg_match_all($pattern, $name, $matches);
    $attrs = $this->attributesArray;
    $matches = $matches[1];

    foreach ($attrs as $attr) {
      $name = str_replace('{{' . $attr['name'] . '}}', $attr['value'], $name);
    }

    foreach ($matches as $key) {
      if (isset($this->{$key}) && $this->{$key}) {
        $name = str_replace('{{' . $key . '}}', $this->{$key}, $name);
      } else if ($this->status == self::STATE_DRAFT) {
        $name = str_replace('{{' . $key . '}}', '***', $name);
      }
    }

    $name = preg_replace('/({{[^}}]+}}\s?)+/', '', $name);

    $name = trim($name);

    if (mb_strrpos($name, 'к') === mb_strlen($name) - 1) {
      $name = mb_substr($name, 0, -1);
    }

    if (mb_strlen($name) < 3 && $this->status == self::STATE_DRAFT) {
      $this->_name = '[Черновик]';
      return $this->_name;
    }

    $this->_name = $name;
    return $this->_name;
  }

  public function getUrl($suf = '', $edit = false)
  {
    if ($edit && $this->status != self::STATE_ACTIVE) return $this->getEditUrl($edit);
    if ($this->status == self::STATE_DRAFT) return '';
    $scheme = !empty($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] . '://' : 'http://';

    $url = $suf;
    $exp = explode('/', $this->url_template);

    $attrs = $this->attributesArray;
    $attrs_arr = [];

    foreach ($attrs as $attr) {
      $attrs_arr['attribute_' . mb_strtolower($attr['name'])] = $attr['url'];
    }


    foreach ($exp as $par) {
      if (strpos($par, 'group__') !== false) {
        $url .= '/' . str_replace('group__','', $par);
      }

      if (strpos($par, 'attribute_') === false && isset($this->{$par})) {
        $url .= '/' . $this->{$par};
      } else if (isset($attrs_arr[mb_strtolower($par)])) {
        $url .= '/' . $attrs_arr[mb_strtolower($par)];
      }
    }

    return rtrim($scheme . $_SERVER['HTTP_HOST'] . $url, '/') . '/';
  }

  public function getEditUrl($product_group)
  {
    return '/personal/sell/' . $product_group['url'] . '/' . $this->id . '/';
  }

  public function getUser()
  {
    return $this
      ->hasOne(User::class, ['id' => 'user_id'])
      ->with('country');
  }

  public function afterFind()
  {
    parent::afterFind();
    $this->images = !is_null($this->images) && !empty($this->images) ? json_decode($this->images) : [];
    $this->error_images = !is_null($this->error_images) && !empty($this->error_images) ? json_decode($this->error_images) : [];
    $this->remote_images = !is_null($this->remote_images) && !empty($this->remote_images) ? json_decode($this->remote_images) : (object)array('remote' => [], 'local' => []);
  }

  public function beforeSave($insert)
  {
    if (!parent::beforeSave($insert)) {
      return false;
    }

    $this->text = htmlspecialchars($this->text);

    $this->images = isset($this->images) && !empty($this->images) ? json_encode($this->images) : null;
    $this->error_images = isset($this->error_images) && !empty($this->error_images) ? json_encode($this->error_images) : null;
    $this->remote_images = isset($this->remote_images) && !empty($this->remote_images) ? json_encode($this->remote_images) : null;
    return true;
  }
}
