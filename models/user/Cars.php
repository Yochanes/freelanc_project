<?php

namespace app\models\user;

use Yii;
use app\models\products\CategoryAttributes;

class Cars extends \yii\db\ActiveRecord
{
  private $_attribute_values = [];
  private $_name;

  public static function tableName()
  {
    return 'user_cars';
  }

  public function rules()
  {
    return [
      [['make', 'model', 'year'], 'required'],
      [['make', 'model', 'generation', 'year'], 'string', 'max' => 100],
      [['razborka', 'vin'], 'string', 'max' => 255],
      [['id'], 'integer']
    ];
  }

  public function getAttributesArray()
  {
    return $this ->hasMany(CarAttributes::class, ['car_id' => 'id']);
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

  public function getName()
  {
    if ($this->_name) return $this->_name;
    $name = '{{razborka}} {{make_val}} {{model_val}} {{year_val}} {{Объем двигателя}} {{Топливо}} {{Коробка}}';
    $pattern = '/\{\{([^\s]+)\}\}/s';
    $matches = array();
    $result = preg_match_all($pattern, $name, $matches);
    $attrs = $this->attributesArray;

    $attrs_arr = [];
    $matches = $matches[1];

    foreach ($attrs as $attr) {
      $name = str_replace('{{' . $attr['name'] . '}}', $attr['value'], $name);
    }

    foreach ($matches as $key) {
      if (isset($this->{$key}) && $this->{$key}) {
        $name = str_replace('{{' . $key . '}}', $this->{$key}, $name);
      }
    }

    $name = preg_replace('/\{\{\.*[^}]+\}\}\s?/', '', $name);
    $name = trim($name);

    if (mb_strrpos($name, 'к') === mb_strlen($name) - 1) {
      $name = mb_substr($name, 0, -1);
    }

    $this->_name = $name;
    return $this->_name;
  }
}
