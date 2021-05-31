<?php

namespace app\models\catalogs;

use app\models\makes\Generations;
use app\models\makes\MakeGroups;
use app\models\makes\Makes;
use app\models\makes\Models;
use app\models\products\CatalogCategories;
use app\models\products\Categories;
use app\models\products\CategoryAttributeGroups;
use app\models\products\ProductGroups;
use Yii;

class Catalog_Params extends \yii\db\ActiveRecord
{

  const CATALOG_PARAM_TYPE_MAKE_GROUP = 0;
  const CATALOG_PARAM_TYPE_MAKE = 1;
  const CATALOG_PARAM_TYPE_MODEL = 2;
  const CATALOG_PARAM_TYPE_GENERATION = 3;
  const CATALOG_PARAM_TYPE_YEAR = 4;
  const CATALOG_PARAM_TYPE_PARTNUM = 5;
  const CATALOG_PARAM_TYPE_SKU = 6;

  const CATALOG_PARAM_TYPE_CATALOG_CATEGORY = 7;
  const CATALOG_PARAM_TYPE_CATALOG_SUBCATEGORY = 8;
  const CATALOG_PARAM_TYPE_PRODUCT_CATEGORY = 9;

  const CATALOG_PARAM_TYPE_PRODUCT_GROUP = 10;

  const CATALOG_PARAM_TYPE_PRODUCT_ATTRIBUTE_GROUP = 100;

  public function rules()
  {
    return [
      [['catalog_id', 'param_title', 'param_type'], 'required'],
      [['param_title'], 'string', 'max' => 255],
      [['catalog_id', 'sort_order', 'param_type'], 'integer']
    ];
  }

  public static function tableName()
  {
    return 'catalog_params';
  }

  public function getIsAttribute()
  {
    return $this->param_type > self::CATALOG_PARAM_TYPE_PRODUCT_ATTRIBUTE_GROUP;
  }

  public function getAttributeId()
  {
    return $this->param_type - self::CATALOG_PARAM_TYPE_PRODUCT_ATTRIBUTE_GROUP;
  }

  public function getParameters($where_val = '')
  {
    switch ($this->param_type) {
      case self::CATALOG_PARAM_TYPE_MAKE_GROUP:
        return MakeGroups::find()
          ->all();

      case self::CATALOG_PARAM_TYPE_MAKE:
        if ($where_val) {
          return Yii::$app->db
            ->createCommand(
              'SELECT makes.* FROM makes INNER JOIN make_to_group ON make_id=id '.
              'INNER JOIN make_groups ON make_groups.make_group_id=make_to_group.make_group_id '.
              'AND make_groups.make_group_id=' . $where_val . ' GROUP BY name')
            ->queryAll();
        } else {
          return Makes::find()
            ->all();
        }

      case self::CATALOG_PARAM_TYPE_MODEL:
        return Models::find()
          ->where($where_val ? 'make_id=' . $where_val : '')
          ->all();

      case self::CATALOG_PARAM_TYPE_GENERATION:
        return Generations::find()
          ->where($where_val ? 'model_id=' . $where_val : '')
          ->all();

      case self::CATALOG_PARAM_TYPE_YEAR:
        $years = [];

        for ($i = (int)date('Y'); $i >= Products::MIN_PRODUCT_YEAR; $i--) {
          $years['year_' . $i] = $i;
        }

        return $years;

      case self::CATALOG_PARAM_TYPE_PARTNUM:
        return  [];

      case self::CATALOG_PARAM_TYPE_SKU:
        return  [];

      case self::CATALOG_PARAM_TYPE_CATALOG_CATEGORY:
        return CatalogCategories::find()
          ->where('parent_id is NULL')
          ->all();

      case self::CATALOG_PARAM_TYPE_CATALOG_SUBCATEGORY:
        return CatalogCategories::find()
          ->where($where_val ? 'paren_id=' . $where_val : 'parent_id IS NULL')
          ->all();

      case self::CATALOG_PARAM_TYPE_PRODUCT_CATEGORY:
        return Categories::find()
          ->where($where_val ? 'catalog_category_id=' . $where_val : '')
          ->all();

      case self::CATALOG_PARAM_TYPE_PRODUCT_GROUP:
        return ProductGroups::find()
          ->all();

      case self::CATALOG_PARAM_TYPE_PRODUCT_ATTRIBUTE_GROUP:
        return CategoryAttributeGroups::find()
          ->where('attribute_group_id=' . $where_val)
          ->with('attributesArray')
          ->all();
    }
  }

  public static function getParamName($type)
  {
    switch ($type) {
      case self::CATALOG_PARAM_TYPE_MAKE_GROUP:
        return 'make_group';

      case self::CATALOG_PARAM_TYPE_MAKE:
        return 'make';

      case self::CATALOG_PARAM_TYPE_MODEL:
        return 'model';

      case self::CATALOG_PARAM_TYPE_GENERATION:
        return 'generation';

      case self::CATALOG_PARAM_TYPE_YEAR:
        return 'year';

      case self::CATALOG_PARAM_TYPE_PARTNUM:
        return 'partnum';

      case self::CATALOG_PARAM_TYPE_SKU:
        return 'sku';

      case self::CATALOG_PARAM_TYPE_CATALOG_CATEGORY:
        return 'catalog_category';

      case self::CATALOG_PARAM_TYPE_CATALOG_SUBCATEGORY:
        return 'catalog_subcategory';

      case self::CATALOG_PARAM_TYPE_PRODUCT_CATEGORY:
        return 'category';

      case self::CATALOG_PARAM_TYPE_PRODUCT_GROUP:
        return 'product_group';

      case $type > self::CATALOG_PARAM_TYPE_PRODUCT_ATTRIBUTE_GROUP:
        return 'attribute_group_' . ($type - self::CATALOG_PARAM_TYPE_PRODUCT_ATTRIBUTE_GROUP);
    }
  }

  public static function getParameterDefinitions()
  {
    return [
      self::CATALOG_PARAM_TYPE_MAKE_GROUP => 'Группа марок',
      self::CATALOG_PARAM_TYPE_MAKE => 'Марки',
      self::CATALOG_PARAM_TYPE_MODEL => 'Модели',
      self::CATALOG_PARAM_TYPE_GENERATION => 'Поколения',
      self::CATALOG_PARAM_TYPE_YEAR => 'Годы',
      self::CATALOG_PARAM_TYPE_PARTNUM => 'Номер запчасти',
      self::CATALOG_PARAM_TYPE_SKU => 'Артикул',
      self::CATALOG_PARAM_TYPE_CATALOG_CATEGORY => 'Категория запчастей',
      self::CATALOG_PARAM_TYPE_CATALOG_SUBCATEGORY => 'Подкатегория запчастей',
      self::CATALOG_PARAM_TYPE_PRODUCT_CATEGORY => 'Запчасть',
      self::CATALOG_PARAM_TYPE_PRODUCT_GROUP => 'Группа товарв',
    ];
  }
}
