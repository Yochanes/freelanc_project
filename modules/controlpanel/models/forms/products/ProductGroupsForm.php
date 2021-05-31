<?php

namespace app\modules\controlpanel\models\forms\products;

use app\models\helpers\PageUtils;
use app\models\products\CategoryAttributeGroups;
use app\models\site\Pages;
use Yii;
use yii\base\Model;
use yii\web\UploadedFile;
use app\models\helpers\Helpers;
use app\models\products\ProductGroups;

class ProductGroupsForm extends Model
{

  public $name;
  public $url;
  public $image;
  public $product_categories;
  public $attribute_groups;
  public $attribute_groups_sort;
  public $attribute_groups_position;
  public $attribute_groups_required;
  public $attribute_groups_upload;
  public $make_groups;
  public $product_group_id;
  public $is_default;
  public $use_hint;
  public $hint_link;
  public $main_attribute;
  public $title;
  public $meta_description;
  public $sort_order;

  public function rules()
  {
    return [
      [['name'], 'required', 'message' => 'Эти поля должны быть обязательно заполнены'],
      [['name', 'url', 'hint_link', 'main_attribute'], 'string', 'max' => 255, 'tooLong' => 'Длина этого поля не должна превышать 255 символов'],
      ['product_categories', 'string', 'max' => 20, 'tooLong' => 'Длина этого поля не должна превышать 20 символов'],
      [['make_groups', 'attribute_groups_sort', 'attribute_groups_position'], 'each', 'rule' => ['integer']],
      [['attribute_groups', 'attribute_groups_required', 'attribute_groups_upload'], 'each', 'rule' => ['string']],
      [['is_default', 'use_hint'], 'boolean'],
      ['sort_order', 'integer', 'min' => 0, 'max' => 999],
      [['title', 'meta_description'], 'string'],
      [['image'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpeg, jpg, gif'],
      [['product_group_id'], 'integer']
    ];
  }

  public function saveData()
  {
    $result = array();
    $user = Yii::$app->user->identity;
    $this->image = UploadedFile::getInstanceByName('image');
    $this->url = trim($this->url, '/');

    if ($this->validate()) {
      $item = false;

      if (!empty($this->product_group_id)) {
        $item = ProductGroups::findOne(['product_group_id' => $this->product_group_id]);
      } else {
        $item = new ProductGroups();

        $check = ProductGroups::find()
          ->where('name="' . $this->name . '"')
          ->count();

        if ($check) {
          $result['error'] = 'Ошибка сохранения: подобная группа уже существует';
          return $result;
        }
      }

      if ($item) {
        $make_groups = '';
        $arr = [];

        foreach ($this->make_groups as $v) {
          if (!$v || in_array($v, $arr)) continue;
          $arr[] = $v;
          $make_groups .= ',' . $v;
        }

        $make_groups = substr($make_groups, 1);

        $attribute_groups = [];
        $required_attrs = [];
        $required_sort = [];
        $arr = [];
        $catalog_parameters = '';
        $n = 0;
        $sort = [];
        $params = [];

        foreach ($this->attribute_groups as $v) {
          if (!$v) continue;
          if (!$v || isset($attribute_groups[$v])) continue;
          if (!isset($this->attribute_groups_sort[$n])) continue;
          if (!isset($this->attribute_groups_position[$n])) continue;

          $attribute_groups[$v] = [
            'sort_order' => $this->attribute_groups_sort[$n],
            'position' => $this->attribute_groups_position[$n],
            'is_required' => 0,
            'upload' => 0
          ];

          $params[$v] = $attribute_groups[$v];
          $sort[$v] = $attribute_groups[$v]['sort_order'];

          foreach ($this->attribute_groups_required as $req) {
            if (strpos($req, $v) !== false) {
              $attribute_groups[$v]['is_required'] = explode('_', $req)[1];
              break;
            }
          }

          foreach ($this->attribute_groups_upload as $req) {
            if (strpos($req, $v) !== false) {
              $attribute_groups[$v]['upload'] = explode('_', $req)[1];
              break;
            }
          }

          if ($attribute_groups[$v]['is_required']) {
            $required_attrs[$v] = $attribute_groups[$v];
            $required_sort[$v] = $attribute_groups[$v]['sort_order'];
          }

          $n++;
        }

        $item->make_groups = $make_groups;
        $item->attribute_groups = $attribute_groups;
        $is_default = $this->is_default;

        if ($this->is_default && !$item->is_default) {
          Yii::$app->db
            ->createCommand('UPDATE product_groups SET is_default=0 WHERE is_default=1')
            ->execute();
        } else if (!$this->is_default && $item->is_default) {
          $is_default = 1;
        }

        array_multisort($sort, SORT_ASC, $params);

        foreach ($params as $k => $v) {
          if ($k != 'category') {
            $catalog_parameters .= $k . '/';
          } else {
            $catalog_parameters .= 'category_group/category_subgroup/' . $k . '/';
          }
        }

        $old_url = $item->url;
        $new_url = '';

        if ($item->load(Yii::$app->request->post(), '')) {
          if ($catalog_parameters) {
            $catalog_parameters = substr($catalog_parameters, 0, -1);
            $item->catalog_parameters = $catalog_parameters;
          } else {
            $item->catalog_parameters = '';
          }

          $item->is_default = $this->is_default;

          if (!$this->url) {
            $item->url = Helpers::translaterUrl($this->name);
          }

          if (!empty($this->image)) {
            if ($item->image) {
              if (file_exists(Yii::$app->basePath . $item->image)) {
                unlink(Yii::$app->basePath . $item->image);
              }
            }

            $img_name = Helpers::translaterUrl($item->name);

            if ($this->image->saveAs(Yii::$app->basePath . '/web/productgroupicons/' . $img_name . '.' . $this->image->extension, true)) {
              $item->image = '/web/productgroupicons/' . $img_name . '.' . $this->image->extension;
            }
          }

          array_multisort($sort, SORT_ASC, $params);

          $template = '';
          $template_url = 'group__' . trim($item->url, '/') . '/';

          if ($item->main_attribute) {
            if (strpos($item->main_attribute, 'attribute;') !== false) {
              $exp = explode(';', $item->main_attribute);
              $template = $item->name;

              if (count($exp) >= 3) {
                $attr_id = $exp[2];
                $attr = CategoryAttributeGroups::find()
                  ->where(['attribute_group_id' => $attr_id])
                  ->one();

                if ($attr) {
                  if (strpos($template, '{{' . $attr->name . '}}') === false) {
                    $template .= ' {{' . $attr->name . '}}';
                  }
                }
              }
            } else if ($item->main_attribute == 'category') {
              if (strpos($template, '{{category_val}}') === false) {
                $template = '{{category_val}}';
              }
            } else if ($item->main_attribute == 'sku') {
              if (strpos($template, '{{sku}}') === false) {
                $template = '{{sku}}';
              }
            }
          } else {
            $template = $item->name;
          }

          array_multisort($required_sort, SORT_ASC, $required_attrs);

          foreach ($required_attrs as $key => $val) {
            if (strpos($key, 'attribute') === false && $key != 'partnum' && $key != 'sku') {
              if (strpos($template, '{{' . $key . '_val}}') === false) {
                $template .= ' {{' . $key . '_val}}';
              }

              $template_url .= $key . '/';
            } else if ($key == 'partnum' || $key == 'sku') {
              if (strpos($template, '{{' . $key . '}}') === false) {
                $template .= ' {{' . $key . '}}';
              }

              $template_url .= $key . '/';
            } else if (strpos($key, 'attribute') !== false) {
              $exp = explode('_', $key);

              if (count($exp) >= 1) {
                $attr = CategoryAttributeGroups::find()
                  ->where(['attribute_group_id' => $exp[1]])
                  ->one();

                if ($attr) {
                  if (strpos($template, '{{' . $attr->name . '}}') === false) {
                    $template .= ' {{' . $attr->filter_name . '}}';
                    $template_url .= 'attribute_' . mb_strtolower($attr->filter_name) . '/';
                  }
                }
              }
            }
          }

          $template_url = trim($template_url, '/');
          $template_url .= '/id';

          $item->product_url_template = $template_url;
          $item->product_name_template = $template;

          if ($item->save()) {
            Yii::$app->db
              ->createCommand()
              ->update('products',
                ['name_template' => $item->product_name_template, 'url_template' => $item->product_url_template],
                ['group_id' => $item->product_group_id])
              ->execute();

            $old_url = $old_url ? $old_url : $new_url;

            $page = Pages::find()
              ->where([
                'type' => Pages::PAGE_TYPE_CATEGORY,
                'url' => PageUtils::getPageUrl($old_url)
              ])
              ->one();

            if (!$page) {
              $page = new Pages();
            }

            if (!$page->name) {
              $page->name = $item->name;
            }

            if (!$page->title) {
              $page->title = $item->name;
            }

            if (!$page->meta_title) {}
            $page->meta_title = $item->name;

            $page->type = Pages::PAGE_TYPE_CATEGORY;
            $page->url = PageUtils::getPageUrl($item->url);
            $page->real_url = 'products/index';
            $page->save();

            Yii::$app->db
              ->createCommand('DELETE FROM urls WHERE url="' . $old_url . '"')
              ->execute();

            Yii::$app->db
              ->createCommand('INSERT INTO urls (url, action, parameters) VALUES ("' . $item->url . '", "products/products", "group_url")')
              ->execute();

            $result['validated'] = true;
            $result['success'] = true;
            return $result;
          } else {
            $result['error'] = 'Ошибка сохранения: данные не сохранены';
          }
        } else {
          $result['error'] = 'Ошибка сохранения: элемент отсутствует в базе данных';
        }
      } else {
        $result['error'] = 'Ошибка сохранения: элемент отсутствует в базе данных';
      }
    }

    $result['validated'] = false;
    return $result;
  }
}
