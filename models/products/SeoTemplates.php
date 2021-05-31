<?php

namespace app\models\products;

class SeoTemplates extends \yii\db\ActiveRecord
{

  public function rules()
  {
    return [
      [['product_group_id', 'name', 'url_template'], 'required'],
      [['name', 'title', 'meta_title', 'meta_description', 'page_content', 'meta_robots', 'url_template'], 'string'],
      [['id', 'product_group_id'], 'integer'],
    ];
  }

  public static function tableName()
  {
    return 'product_groups_seo_templates';
  }

  public function isTemplateApplied($url_params)
  {
    $url_template = explode('/', trim($this->url_template, '/'));
    $url_param_arr = [];

    foreach ($url_params as $p) {
      $url_param_arr[$p['name']] = $p;
    }

    foreach ($url_template as $p) {
      if (!isset($url_param_arr[$p])) {
        return false;
      }
    }

    return true;
  }
}
