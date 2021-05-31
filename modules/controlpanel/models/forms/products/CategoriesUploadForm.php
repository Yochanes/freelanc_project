<?php

namespace app\modules\controlpanel\models\forms\products;

use Yii;
use yii\base\Model;

use app\models\products\Categories;
use app\models\products\CatalogCategories;

use app\models\site\Pages;
use app\models\helpers\Helpers;

class CategoriesUploadForm extends Model
{
  public $data;
  public $clear;
  public $current;

  public function rules()
  {
    return [
      ['data', 'required'],
      ['data', 'string'],
      ['clear', 'boolean', 'skipOnEmpty' => true],
      ['current', 'integer']
    ];
  }

  public function saveData()
  {
    $result = array();
    $user = Yii::$app->user->identity;

    if ($this->validate()) {
      $data = json_decode($this->data);

      if ($data && is_array($data)) {
        $result['validated'] = true;

        if ($this->current == 0) {
          Yii::$app->session->remove('upload_categories');
        }

        if ($this->clear && $this->current == 0) {
          CatalogCategories::deleteAll();

          Yii::$app->db
            ->createCommand('DELETE FROM url_params WHERE name="category" OR name="category_group" OR name="category_subgroup"')
            ->execute();

          Yii::$app->db
            ->createCommand('DELETE FROM product_categories')
            ->execute();
        }

        $cat_arr = array();
        $params_to_insert = [];

        if (!$this->clear && !Yii::$app->session->has('upload_categories')) {
          $cat_tarr = Categories::find()->all();

          foreach ($cat_tarr as $row) {
            $cat_arr[mb_strtolower(trim($row->name))] = $row;
          }

          Yii::$app->session->set('upload_categories', $cat_tarr);
        } else {
          $cat_arr = Yii::$app->session->get('upload_categories');
        }

        $pages_to_insert = array();
        $urls_to_insert = array();

        $to_insert = 0;
        $to_update = 0;
        $inserted = 0;
        $updated = 0;

        foreach ($data as $row) {
          $item = false;
          $item_is_new = false;
          if (!isset($row->name)) continue;

          if (!$this->clear && isset($cat_arr[mb_strtolower(trim($row->name))])) {
            $item = $cat_arr[mb_strtolower(trim($row->name))];
            $to_update++;
          } else {
            $to_insert++;
            if (Categories::find()->where(['name' => $row->name])->exists()) continue;

            $item = new Categories();
            $item->name = $row->name;
            $item_is_new = true;
          }

          $search = '';
          $search_url = '';

          if (isset($row->synonym)) {
            foreach ($row->synonym as $str) {
              if (trim($str)) {
                $search .= $str . ';';
                $search_url .= Categories::createUrlFromCyrillic($str) . ';';
              }
            }

            $search = mb_substr($search, 0, -1);
            $search_url = substr($search_url, 0, -1);
          }

          $item->connected_category = '';
          $item->connected_category_url = '';
          $item->synonym = $search;
          $item->synonym_url = $search_url;
          $item->attributes_required = isset($row->attributes_required) ? $row->attributes_required : null;
          $item->generation_required = isset($row->generation_required) ? $row->generation_required : 0;
          $item->partnum_required = isset($row->partnum_required) ? $row->partnum_required : 0;

          if (isset($row->parent_name) && trim($row->parent_name)) {
            $item->parent_name = $row->parent_name;
            $item->parent_url = Helpers::translaterUrl($row->parent_name);
          } else {
            $item->parent_name = null;
            $item->parent_url = null;
          }

          if (isset($row->connected_attributes)) {
            $item->connected_attributes = $row->connected_attributes;
          } else {
            $item->connected_attributes = null;
          }

          $url = Helpers::translaterUrl($item->name);
          $item->url = $url;

          $params_to_insert['category_' . $item->category_id] = [
            'name' => 'category',
            'title' => $item->name,
            'url' => $item->url,
            'connected_id' => $item->category_id
          ];

          $catalog_main = false;
          $catalog = false;

          if (isset($row->catalog_category) && $row->catalog_category) {
            $catalog_main = CatalogCategories::find()
              ->where(['name' => $row->catalog_category])
              ->one();

            if (!$catalog_main) {
              $catalog_main = new CatalogCategories();
              $catalog_main->name = $row->catalog_category;
              $catalog_main->url = 'r-' . Helpers::translaterUrl($row->catalog_category);
              $catalog_main->save();
            }

            $params_to_insert['category_group' . $catalog_main->id] = [
              'name' => 'category_group',
              'title' => $catalog_main->name,
              'url' => $catalog_main->url,
              'connected_id' => $catalog_main->id
            ];
          }

          if ($catalog_main && $catalog_main->id && isset($row->catalog_subcategory) && $row->catalog_subcategory) {
            $catalog = CatalogCategories::find()
              ->where('name="' . $row->catalog_subcategory . '" AND parent_id=' . $catalog_main->id)
              ->one();

            if (!$catalog) {
              $catalog = new CatalogCategories();
              $catalog->parent_id = $catalog_main->id;
              $catalog->name = $row->catalog_subcategory;
              $catalog->url =  'pr-' . Helpers::translaterUrl($row->catalog_subcategory);
              $catalog->save();
            }

            $params_to_insert['category_subgroup' . $catalog->id] = [
              'name' => 'category_subgroup',
              'title' => $catalog->name,
              'url' => $catalog->url,
              'connected_id' => $catalog->id
            ];
          }

          if (is_object($catalog_main) && $catalog_main->id) {
            $item->catalog_category_id = $catalog_main->id;
          }

          if (is_object($catalog) && isset($catalog->id)) {
            $item->catalog_category_id = $catalog->id;
          }

          if ($item->save()) {
            if ($item_is_new) {
              $inserted++;
            } else {
              $updated++;
            }
          } else {
            $result['product_errors'][$item->name] = $item->errors;
          }
        }

        if (!empty($params_to_insert)) {
          Yii::$app->db->createCommand()
            ->batchInsert('url_params', ['name', 'title', 'url', 'connected_id'], $params_to_insert)
            ->execute();
        }

        $result['to_insert'] = $to_insert;
        $result['to_update'] = $to_update;
        $result['inserted'] = $inserted;
        $result['updated'] = $updated;
        $result['success'] = true;
        return $result;
      } else {
        $this->addError('upload_file', 'failed to save file');
      }
    }

    $result['validated'] = false;
    return $result;
  }
}
