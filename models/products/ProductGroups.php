<?php

namespace app\models\products;

use app\models\makes\MakeGroups;
use Yii;

class ProductGroups extends \yii\db\ActiveRecord
{

  private $_make_groups = false;
  private $_make_group_ids = [];
  private $_attribute_groups = false;
  private $_attribute_group_ids = [];

  public function rules()
  {
    return [
      [['name'], 'required'],
      [['name', 'url', 'catalog_parameters', 'image', 'hint_link', 'main_attribute'], 'string', 'max' => 255],
      ['product_categories', 'string', 'max' => 20],
      ['sort_order', 'integer', 'min' => 0, 'max' => 999],
      [['is_default', 'use_hint'], 'boolean'],
      [['title', 'meta_description', 'meta_title', 'page_content'], 'string'],
      [['product_group_id'], 'integer'],
    ];
  }

  public static function tableName()
  {
    return 'product_groups';
  }

  public function getId()
  {
    return $this->product_group_id;
  }

  public function getMakeGroupsArray()
  {
    if ($this->_make_groups) {
      return $this->_make_groups;
    }

    $this->_make_groups = $this->make_groups ? MakeGroups::find()
      ->where('make_group_id IN (' . $this->make_groups . ')')
      ->all() : [];

    return $this->_make_groups;
  }

  public function getMakeGroupIds()
  {
    return $this->_make_group_ids;
  }

  public function getAttributeGroupsArray()
  {
    if ($this->_attribute_groups) {
      return $this->_attribute_groups;
    }

    $attr_ids = '';

    foreach ($this->attribute_groups as $key => $attr) {
      if (strpos($key, 'attribute_') === false) {
        continue;
      }

      $split = explode('_', $key);
      $id = end($split);

      if (is_numeric($id)) {
        $attr_ids .= $id . ',';
      }
    }

    $this->_attribute_groups = $attr_ids ? CategoryAttributeGroups::find()
      ->where('attribute_group_id IN (' . substr($attr_ids, 0, -1) . ')')
      ->orderBy('sort_order DESC')
      ->all() : [];

    return $this->_attribute_groups;
  }

  public function getAttributeGroupIds()
  {
    return $this->_attribute_group_ids;
  }

  public function getSeoTemplates()
  {
    return $this->hasMany(SeoTemplates::class, ['product_group_id' => 'product_group_id']);
  }

  public function getAppliedSeoTemplate($url_params)
  {
    $template = false;

    foreach ($this->seoTemplates as $tmp) {
      if ($tmp->isTemplateApplied($url_params)) {
        $template = $tmp;
      } else {
        break;
      }
    }

    return $template;
  }

  public function validateData($data, $filter = false, $ignore_attrs = false)
  {
    $result = ['validated' => true, 'errors' => []];

    foreach ($this->attribute_groups as $key => $attr) {
      if ($attr['is_required'] && (!isset($data[$key]) || empty($data[$key]))) {
        if ($ignore_attrs && strpos($key, 'attribute_') !== false) {
          continue;
        }

        if ($filter && (is_array($filter) && isset($filter[$key])) || (is_string($filter) && $key == $filter)) {
          continue;
        }

        $result['validated'] = false;
        $result['error'] = 'Не все поля заполнены верно';
        $result['errors'][$key] = Yii::t('app', 'Это поле не может быть пустым');
      }
    }

    return $result;
  }

  public function validateUpload($data)
  {
    $result = ['validated' => true, 'errors' => []];

    foreach ($this->attribute_groups as $key => $attr) {
      if ($attr['upload'] && (!isset($data[$key]) || empty($data[$key]))) {
        $result['validated'] = false;
        $result['error'] = 'Не все поля заполнены верно';
        $result['errors'][$key] =  Yii::t('app', 'Это поле не может быть пустым');
      }
    }

    return $result;
  }

  public function afterFind()
  {
    parent::afterFind();

    $this->attribute_groups = !is_null($this->attribute_groups) && !empty($this->attribute_groups) ? json_decode($this->attribute_groups, true) : [];

    $arr = explode(',', $this->make_groups);

    foreach ($arr as $v) {
      $_make_group_ids[] = $v;
    }
  }

  public function beforeSave($insert)
  {
    if (!parent::beforeSave($insert)) {
      return false;
    }

    $this->attribute_groups = isset($this->attribute_groups) && !empty($this->attribute_groups) ? json_encode($this->attribute_groups) : null;
    return true;
  }
}
