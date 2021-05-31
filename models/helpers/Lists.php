<?php

namespace app\models\helpers;

use app\models\Products;
use Yii;

use app\models\Countries;
use app\models\Cities;

class Lists
{

  public static function getOptions($data = [], $group = false, $values = [], $make_groups = '', $with_sections = false, $empty_options = false) {
    $attribute_ids = '';
    $keys = [0 => 'main_params', 1 => 'additional_params', 2 => 'after_params'];
    $data['main_params'] = [];
    $data['additional_params'] = [];
    $data['after_params'] = [];

    $country = isset($values['country']) ? $values['country'] : false;
    $city = isset($values['city']) ? $values['city'] : false;

    $data['options_country'] = Lists::getOptionCountryList($country, isset($values['no_empty_values']) ? $values['no_empty_values'] : false);
    $data['options_city'] = $empty_options ? '' : Lists::getOptionCityList($city, $country, isset($values['no_empty_values']) ? $values['no_empty_values'] : false);

    if ($group) {
      foreach ($group->attribute_groups as $key => $attr) {
        if (!isset($keys[$attr['position']])) continue;
        $data[$keys[$attr['position']]][$key] = $attr['sort_order'];

        if ($key == 'make') {
          $make = isset($values['make']) ? $values['make'] : false;
          $model = isset($values['model']) ? $values['model'] : false;
          $generation = isset($values['generation']) ? $values['generation'] : false;

          if ($empty_options) {
              $data = array_merge($data, array(
                  'options_make' => '',
                  'options_model' => false,
                  'options_generation' => false,
                  // or count?
                  'make_has_models' => 1,
                  'makes' => [],
                  'models' => false,
                  'generations' => false
              ));
          } else {
              $data = array_merge(
                  $data,
                  Lists::getOptionMakeList(
                      !$make_groups ? $group['make_groups'] ? 'make_to_group.make_group_id IN (' . $group['make_groups'] . ')' : '' : $make_groups,
                      $make,
                      $model,
                      $generation
                  ));
          }

        } else if ($key == 'category') {
          $category = isset($values['category']) ? $values['category'] : false;

          $data['options_category'] = $empty_options ? '' : Lists::getOptionCategoryList(
            $category,
            $group['product_categories'],
            $with_sections
          )['options_category'];
        } else if ($key == 'year') {
          $year = isset($values['year']) ? $values['year'] : false;
          $data['options_year'] = $empty_options ? '' : Lists::getOptionYearList($year);
        } else if (strpos($key, 'attribute_') !== false) {
          $exp = explode('_', $key);
          $attribute_ids .= ',' . $exp[1];
        }
      }

      if ($attribute_ids) {
        $attribute_ids = substr($attribute_ids, 1);
        $attrs = isset($values['attrs']) ? $values['attrs'] : false;
        $data = array_merge($data, Lists::getOptionAttributeList($attribute_ids, $attrs));
      }
    } else {
      $attrs = isset($values['attrs']) ? $values['attrs'] : false;
      $data = array_merge($data, Lists::getOptionAttributeList('', $attrs, true));
    }

    return $data;
  }

  public static function getAttributesByGroup($data = [], $group, $values = []) {
    $attribute_ids = '';
    $keys = [0 => 'main_params', 1 => 'additional_params', 2 => 'after_params'];

    foreach ($group->attribute_groups as $key => $attr) {
      if (!isset($keys[$attr['position']])) continue;
      $data[$keys[$attr['position']]][$key] = $attr['sort_order'];

      if (strpos($key, 'attribute_') !== false) {
        $exp = explode('_', $key);
        $attribute_ids .= ',' . $exp[1];
      }
    }

    if ($attribute_ids) {
      $attribute_ids = substr($attribute_ids, 1);
      $attrs = isset($values['attrs']) ? $values['attrs'] : false;
      $data = array_merge($data, Lists::getOptionAttributeList($attribute_ids, $attrs));
    }

    return $data;
  }

  public static function getOptionAttributeList($ids = '', $vals = [], $select_all = false)
  {
    $attrs = [];
    $attributes = [];
    $attributes_arr = [];

    if ($ids || $select_all) {
      $items = Yii::$app->db->createCommand('SELECT product_category_attribute_groups.filter_name, product_category_attribute_groups.name,
        product_category_attribute_groups.attribute_group_id, product_category_attribute_groups.important, product_category_attribute_groups.catalog_prefix, product_category_attributes.catalog_text, product_category_attribute_groups.catalog_suffix,
        product_category_attribute_groups.required, product_category_attributes.value, product_category_attributes.url, 
        product_category_attributes.attribute_id, product_category_attribute_groups.alt_names, product_category_attributes.alt_values
        FROM product_category_attribute_groups
			  LEFT JOIN product_category_attributes ON product_category_attributes.attribute_group_id = product_category_attribute_groups.attribute_group_id' .
        ($ids ? ' WHERE product_category_attribute_groups.attribute_group_id IN (' . $ids . ') ' : '') .
        ' ORDER BY product_category_attribute_groups.sort_order ASC')
        ->cache(3600)
        ->queryAll();

      foreach ($items as $item) {
        $opt_key = 'attribute_' . $item['attribute_group_id'];

        if (!isset($attrs[$opt_key])) {
          $attrs[$opt_key] = [
            'options' => '<option></option>',
            'name' => $item['filter_name'],
            'group_id' => $item['attribute_group_id']
          ];
        }

        if (!isset($attributes[$item['filter_name']])) $attributes[$item['filter_name']] = [];
        if (!isset($attributes_arr[$item['filter_name']])) $attributes_arr[$item['filter_name']] = [];
        $attributes[$item['filter_name']][$item['url']] = $item;
        $attributes_arr[$item['filter_name']][] = $item;
        $attrs[$opt_key]['options'] .= '<option data-prefix="' . $item['catalog_prefix']. '" data-suffix="' . $item['catalog_suffix']. '" value="' . $item['url'] . '"' . ($vals && in_array($item['url'], $vals) ? ' selected' : '') . '>' . $item['value'] . '</option>';
        //$attrs[$opt_key]['attribute_group_id'] = $item['attribute_group_id'];
      }
    }

    return array(
      'options_attribute' => $attrs,
      'attributes' => $attributes,
      'attributes_array' => $attributes_arr,
      'list' => $items
    );
  }

  public static function getOptionAttributeListByItems($items, $vals = [])
  {
    $attrs = [];
    $attributes = [];
    $attributes_arr = [];

    foreach ($items as $item) {
      $opt_key = 'attribute_' . $item['attribute_group_id'];

      if (!isset($attrs[$opt_key])) {
        $attrs[$opt_key] = [
          'options' => '<option></option>',
          'name' => $item['filter_name'],
          'group_id' => $item['attribute_group_id']
        ];
      }

      if (!isset($attributes[$item['filter_name']])) $attributes[$item['filter_name']] = [];
      if (!isset($attributes_arr[$item['filter_name']])) $attributes_arr[$item['filter_name']] = [];

      foreach ($item->attributesArray as $attr) {
        $attributes[$item['filter_name']][$attr['url']] = $attr;
        $attributes_arr[$item['filter_name']][] = $attr;
        $attrs[$opt_key]['options'] .= '<option data-prefix="' . $item['catalog_prefix']. '" data-suffix="' . $item['catalog_suffix']. '" value="' . $attr['url'] . '"' . ($vals && in_array($attr['url'], $vals) ? ' selected' : '') . '>' . $attr['value'] . '</option>';
        $attrs[$opt_key]['attribute_group_id'] = $attr['attribute_group_id'];
      }
    }

    return array(
      'options_attribute' => $attrs,
      'attributes' => $attributes,
      'attributes_array' => $attributes_arr,
      'list' => $items
    );
  }

  public static function getOptionCountryList($val = false, $no_empty_value = false)
  {
    $select_country = !$no_empty_value ? '<option></option><option '.(!$val||$val=='all'?'selected':'').' value="all">Искать везде</option>' : '<option></option>';
    $req = Yii::$app->request;

    $countries = Yii::$app->cache->getOrSet('countries', function () {
      return Countries::find()
        ->orderBy('name')
        ->all();
    }, 3600);

    foreach ($countries as $item) {
      $select_country .= '<option value="' . $item->id . '" data-cname="' . $item->name . '"' .
        ($item->code ? 'data-code="' . $item->code . '"' : '') .
        ($item->id == $val || $item->name == $val || (!empty($item->domain) && $item->domain == $val) ? ' selected' : '') .
        ($item->domain ? ' data-domain="' . $item->domain . '"' : '') . '>' . $item->name . '</option>';
    }

    return $select_country;
  }

  public static function getOptionCityList($val = false, $country = false, $no_empty_value = false)
  {

    $select_city = !$no_empty_value ? '<option></option><option '.(!$val||$val=='all'?'selected':'').' value="all">Искать везде</option>' : '<option></option>';
    $req = Yii::$app->request;
    $country_id = is_object($country) ? $country['id'] : (is_string($country && $country) != 'all' ? $country : false);
    $cities = [];

    if ($country_id) {
      $cities = Yii::$app->cache->getOrSet('cities_' . $country_id, function () use ($country_id) {
        return Cities::find()
          ->where('country_id="' . $country_id . '"')
          ->orderBy('name')
          ->all();
      }, 3600);
    } else {
      $cities = Yii::$app->cache->getOrSet('cities', function () {
        return Cities::find()
          ->orderBy('name')
          ->all();
      }, 3600);
    }

    $val = $val ? $val : $req->get('city');
    $site_city = isset(Yii::$app->view->params['site_city']) ? Yii::$app->view->params['site_city'] : '';
    $selected = false;
    $found = false;

    foreach ($cities as $item) {
      $selected = ($item->id == $val || $item->name == $val || (!empty($item->domain) && $item->domain == $val) || (!$val && $item->name == $site_city));
      if ($selected) $found = true;

      $select_city .= '<option value="' . $item->name . '" data-cname="' . $item->name . '"' .
        ($item->code ? 'data-code="' . $item->code . '"' : '') .
        ($selected ? ' selected' : '') .
        ($item->domain ? ' data-domain="' . $item->domain . '"' : '') . '>' . $item->name . '</option>';
    }

    if (is_string($val) && $val != 'all' && !$found) {
      $select_city .= '<option selected value="' . $val . '">' . $val . '</option>';
    }

    return $select_city;
  }

  public static function getOptionMakeList($make_groups_where = '', $make_val = false, $model_val = false, $generation_val = false, $items_arr = false, $popular = 1)
  {
    $options = '';
    $models = false;
    $generations = false;
    $make_selected = false;
    $ids = '';
    $has_models = true;

    $options = '<option></option>';
    $items = $items_arr && isset($items_arr['makes']) ? $items_arr['makes'] : [];

    if ($popular && $popular > 0 && Yii::$app->db->createCommand('SELECT COUNT(*) FROM makes WHERE is_popular=0')->queryScalar()) {
      $options .= '<option value="_load_popular_">Показать все марки</option>';
    }

    if (!$items) {
      $items = Yii::$app->db
        ->createCommand(
          'SELECT makes.* FROM makes INNER JOIN make_to_group ON make_id=id 
             INNER JOIN make_groups ON make_groups.make_group_id=make_to_group.make_group_id' .
          ($popular ? ' WHERE is_popular=' . $popular : ' WHERE is_popular IN (0,1)') .
          ($make_groups_where ? ' AND ' . $make_groups_where : '') . ' GROUP BY name')
        ->queryAll();
    }

    foreach ($items as $item) {
      $ids .= ',' . $item['id'];
      if (!$make_selected && $item['url'] == $make_val) $make_selected = $item['id'];
      $options .= '<option data-url="' . $item['url'] . '" data-id="' . $item['id'] . '" value="' . $item['url'] . '"' . ($make_selected && $item['url'] == $make_val ? ' selected' : '') . '>' . $item['name'] . '</option>';
    }

    $has_models = $ids ? Yii::$app->db
      ->createCommand('SELECT COUNT(*) FROM make_models WHERE make_id IN (' . substr($ids, 1) . ')')
      ->queryScalar() : false;

    $result = array(
      'options_make' => $options,
      'options_model' => false,
      'options_generation' => false,
      'make_has_models' => $has_models,
      'makes' => $items,
      'models' => $models,
      'generations' => $generations
    );

    if ($has_models && $make_selected) {
      $result = array_merge(
        $result,
        Lists::getOptionModelList(
          $make_selected,
          $model_val,
          $items_arr && isset($items_arr['models']) ? $items_arr['models'] : false,
          $popular
        )
      );

      if ($result['model_selected']) {
        $result = array_merge(
          $result,
          Lists::getOptionGenerationlList(
            $result['model_selected'],
            $generation_val,
            $items_arr && isset($items_arr['generations']) ? $items_arr['generations'] : false)
        );
      }
    }

    return $result;
  }

  public static function getOptionModelList($make_selected, $model_val = false, $models = false, $popular = 1)
  {
    $model_selected = false;
    $options = '<option></option>';

    if ($popular && $popular > 0 && Yii::$app->db->createCommand('SELECT COUNT(*) FROM make_models WHERE is_popular=0')->queryScalar()) {
      $options .= '<option value="_load_popular_">Показать все модели</option>';
    }

    $items = !$models ? Yii::$app->db
      ->createCommand('SELECT id, url, name FROM make_models  
				WHERE (make_id="' . $make_selected . '" OR make_url="' . $make_selected . '") '/* . ($popular ? 'AND is_popular=' . $popular : '')*/)
      ->cache(3600)
      ->queryAll() : $models;

    foreach ($items as $item) {
      if (!$model_selected && ($item['url'] == $model_val || $item['id'] == $model_val)) $model_selected = $item['id'];

      $options .= '<option data-url="' . $item['url'] . '" data-id="' . $item['id'] . '" value="' . $item['url'] . '"' . ($item['url'] == $model_val || $item['id'] == $model_val ? ' selected' : '') . '>' . $item['name'] . '</option>';
    }

    return array(
      'options_model' => $options,
      'model_selected' => $model_selected,
      'models' => $items
    );
  }

  public static function getOptionGenerationlList($model_selected, $generation_val = false, $generations = false)
  {
    $options = '<option></option>';

    $items = !$generations ? Yii::$app->db
      ->createCommand('SELECT id, url, name, years FROM make_generations  
				WHERE model_id="' . $model_selected . '" OR model_url="' . $model_selected . '"')
      ->cache(3600)
      ->queryAll() : $generations;

    foreach ($items as $item) {
      $years = '';

      if (isset($item['years']) && $item['years']) {
        $years = explode('–', $item['years']);
        $years = (isset($years[0]) ? ' data-year_min="' . trim($years[0]) . '" ' : '') . (isset($years[1]) ? ' data-year_max="' . trim($years[1]) . '" ' : '');
      }

      $options .= '<option ' . ($years ? $years : '') . ' data-url="' . $item['url'] . '" value="' . $item['url'] . '"' . ($item['url'] == $generation_val ? ' selected' : '') . '>' . $item['name'] . '</option>';
    }

    return array(
      'options_generation' => $options,
      'generations' => $items
    );
  }

    /**
     * @param false  $val
     * @param string $categories
     * @param false  $with_sections
     *
     * @return array
     * @throws \yii\db\Exception
     */
    public static function getOptionCategoryList($val = false, $categories = 'all', $with_sections = false)
  {
    $options = '<option></option>';
    $items = [];

    if ($categories && !is_null($categories)) {
      $items = Yii::$app->db
        ->createCommand('SELECT * FROM product_categories ' . ($categories == 'all' ? '' : ' WHERE category_id IN (' . $categories . ')') . ' ORDER BY name ASC')
        ->cache(3600)
        ->queryAll();

      foreach ($items as $item) {
        $connected = '';

        if ($item['connected_attributes']) {
          $con_arr = json_decode($item['connected_attributes']);

          if ($con_arr) {
            $attr_gr = '';
            $attr_val = '';

            foreach ($con_arr as $con_attr) {
              $attr_gr .= $con_attr->attribute_group_id . ';';
              $attr_val .= $con_attr->value . ';';
            }

            $attr_gr = substr($attr_gr, 0, -1);
            $attr_val = substr($attr_val, 0, -1);
            $connected = 'data-attributes_connected="' . $attr_gr . '" data-attributes_connected_vals="' . $attr_val . '"';
          }
        }

        $cat_url = $item['parent_url'] ? $item['parent_url'] : $item['url'];
        $selected_condition = Helpers::isCyrillic($val) ? $item['name'] == $val : $item['url'] == $val;
        $options .= '<option data-name="' . $item['name'] . '" data-url="' . $item['url'] . '" ' . $connected . ' data-generation_required="' . $item['generation_required'] . '" data-partnum_required="' . $item['partnum_required'] . '" ' . ($item['attributes_required'] ? 'data-attributes_required="' . $item['attributes_required'] . '"' : '') . ' value="' . $cat_url . '"' . ($selected_condition ? ' selected' : '') . '>' . $item['name'] . '</option>';

        if ($item['synonym']) {
          $synonyms = explode(';', $item['synonym']);

          foreach ($synonyms as $s) {
            if (!$s) continue;
            $selected_condition = Helpers::isCyrillic($val) ? $s == $val : $item['url'] == $val;
            $options .= '<option data-name="' . $s . '" data-url="' . $item['url'] . '" ' . $connected . ' data-generation_required="' . $item['generation_required'] . '" data-partnum_required="' . $item['partnum_required'] . '" ' . ($item['attributes_required'] ? 'data-attributes_required="' . $item['attributes_required'] . '"' : ''). ($selected_condition ? ' selected' : '') . ' value="' . $cat_url . '">' . $s . '</option>';
          }
        }
      }

      if ($with_sections) {
        $items = Yii::$app->db
          ->createCommand('SELECT * FROM product_catalog_categories')
          ->cache(3600)
          ->queryAll();

        foreach ($items as $item) {
          $selected_condition = Helpers::isCyrillic($val) ? $item['name'] == $val : $item['url'] == $val;

          $options .= '<option data-name="' . $item['name'] . '" data-url="' . $item['url'] . '" data-generation_required="0" data-partnum_required="0"  value="' . $item['url'] . '"' . ($selected_condition ? ' selected' : '') . '>' . $item['name'] . '</option>';
        }
      }
    }

    return array(
      'options_category' => $options,
      'categories' => $items
    );
  }

  public static function getOptionCategoryListBySection($val = false, $section = false)
  {
    $options = '<option></option>';

    $items = Yii::$app->db
      ->createCommand('SELECT * FROM product_categories ' . (!$section ? '' : ' WHERE catalog_category_id="' . $section . '"') . ' ORDER BY name ASC')
      ->cache(3600)
      ->queryAll();

    foreach ($items as $item) {
      $connected = '';

      if ($item['connected_attributes']) {
        $con_arr = json_decode($item['connected_attributes']);

        if ($con_arr) {
          $attr_gr = '';
          $attr_val = '';

          foreach ($con_arr as $con_attr) {
            $attr_gr .= $con_attr->attribute_group_id . ';';
            $attr_val .= $con_attr->value . ';';
          }

          $attr_gr = substr($attr_gr, 0, -1);
          $attr_val = substr($attr_val, 0, -1);
          $connected = 'data-attributes_connected="' . $attr_gr . '" data-attributes_connected_vals="' . $attr_val . '"';
        }
      }

      $options .= '<option data-url="' . $item['url'] . '"' . $connected . ' data-generation_required="' . $item['generation_required'] . '" data-partnum_required="' . $item['partnum_required'] . '" ' . ($item['attributes_required'] ? 'data-attributes_required="' . $item['attributes_required'] . '"' : '') . ' value="' . $item['url']. '"' . ($item['url'] == $val ? ' selected' : '') . '>' . $item['name'] . '</option>';
    }

    return array(
      'options_category' => $options,
      'categories' => $items
    );
  }

  public static function getOptionCategoryGroupList($val = false)
  {
    $options = '<option></option>';
    $items = [];

    $items = Yii::$app->db
      ->createCommand('SELECT * FROM product_catalog_categories WHERE parent_id IS NULL')
      ->cache(3600)
      ->queryAll();

    foreach ($items as $item) {
      $options .= '<option data-generation_required="0"  data-url="' . $item['url'] . '" data-partnum_required="0"  value="' . $item['url'] . '"' . ($item['url'] == $val ? ' selected' : '') . '>' . $item['name'] . '</option>';
    }

    return array(
      'options_category_group' => $options,
      'categories' => $items
    );
  }

  public static function getOptionCategorySubgroupList($val = false, $section = false)
  {
    $options = '<option></option>';
    $items = [];

    $items = Yii::$app->db
      ->createCommand('SELECT * FROM product_catalog_categories' . ($section ? ' WHERE parent_id="' . $section . '"' : ''))
      ->cache(3600)
      ->queryAll();

    foreach ($items as $item) {
      $options .= '<option data-generation_required="0" data-partnum_required="0" data-url="' . $item['url'] . '" value="' . $item['url'] . '"' . ($item['url'] == $val ? ' selected' : '') . '>' . $item['name'] . '</option>';
    }

    return array(
      'options_category_subgroup' => $options,
      'categories' => $items
    );
  }

    /**
     * @param false $val
     *
     * @return string
     */
    public static function getOptionYearList($val = false)
  {
    $select_year = '<option></option>';
    $req = Yii::$app->request;
    $val = $val ? $val : $req->get('year');

    for ($i = (int)date('Y'); $i >= Products::MIN_PRODUCT_YEAR; $i--) {
      $select_year .= '<option value="year_' . $i . '"' . ($i == $val || 'year_' . $i == $val ? ' selected' : '') . '>' . $i . '</option>';
    }

    return $select_year;
  }

  public static function getViewed($type)
  {
    $viewed = array();

    if (isset(Yii::$app->request->cookies['viewed'])) {
      $varr = Yii::$app->request->cookies->getValue('viewed');
      $varr = unserialize($varr);

      if (!isset($varr[$type])) $varr[$type] = array();
      $ids = $varr[$type];
      $ids_list = '';
      $columns = [];
      $additional_select = '';

      if ($type == 'product') {
        $columns = ['sku', 'make', 'model', 'generation', 'parttype', 'year', 'engine_volume', 'fuel', 'transmission', 'body'];
        $additional_select = '(SELECT product_categories.important FROM product_categories WHERE product_categories.name = products.parttype) AS important';
      } else if ($type == 'car') {
        $columns = ['sku', 'make', 'model', 'generation', 'year', 'engine_volume', 'fuel', 'transmission', 'body'];
      } else if ($type == 'tire') {
        $columns = ['make', 'sku', 'radius', 'width', 'height', 'season'];
      } else if ($type == 'wheel') {
        $columns = ['make', 'sku', 'model', 'generation', 'year', 'screw', 'radius', 'fit', 'type'];
      }

      if (is_array($ids) && !empty($ids)) {
        foreach ($ids as $k => $v) $ids_list .= ',' . $v;
        $where = $type . '.id IN (' . substr($ids_list, 1) . ')';

        $viewed = Yii::$app->db
          ->createCommand(Lists::buildProductSQL($type, $columns, $additional_select, $where))
          ->queryAll();
      }
    }

    return $viewed;
  }

  public static function buildProductSQL($tableName, $additional = array(), $additional_select = '', $where = '')
  {
    $add = '';

    foreach ($additional as $ad) {
      $add .= $tableName . '.' . $ad . ',';
    }

    return 'SELECT ' . $tableName . '.id,' . $tableName . '.user_id,' . $tableName . '.status,' . $tableName . '.city,' . $add . '
			' . $tableName . '.short_description, ' . $tableName . '.preorder, ' . $tableName . '.available, ' . $tableName . '.date_created, ' . $tableName . '.date_updated,
			' . $tableName . '.price,' . $tableName . '.sale,' . $tableName . '.currency,' . $tableName . '.images,' . $tableName . '.quantity,
			(SELECT users.id FROM users WHERE users.id = ' . $tableName . '.user_id) AS seller_id,' .
      (Yii::$app->user->isGuest ? '' : '(SELECT user_favourites.obj_id FROM user_favourites WHERE user_favourites.user_id = ' . Yii::$app->user->identity->id . ' AND user_favourites.obj_id = ' . $tableName . '.id AND user_favourites.obj_type = "' . substr($tableName, 0, -1) . '") AS fav_id,') . '
			(SELECT users.display_name FROM users WHERE users.id = ' . $tableName . '.user_id) AS seller_name,
			(SELECT users.rating / users.rate_num FROM users WHERE users.id = ' . $tableName . '.user_id) AS seller_rating,
			(SELECT user_contacts.phone FROM user_contacts WHERE user_contacts.user_id = ' . $tableName . '.user_id) AS seller_contact_phone' .
      (!empty($additional_select) ? ',' . $additional_select : '') . '
			FROM ' . $tableName . (!empty($where) ? ' WHERE ' . $where : '');
  }
}
