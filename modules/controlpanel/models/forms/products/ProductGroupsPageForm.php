<?php

namespace app\modules\controlpanel\models\forms\products;

use app\models\products\SeoTemplates;
use Yii;
use yii\base\Model;
use app\models\products\ProductGroups;

class ProductGroupsPageForm extends Model
{
  public $product_group_id;
  public $title;
  public $meta_title;
  public $page_content;
  public $meta_description;
  public $url_template;
  public $url_template_on;
  public $url_template_name;
  public $url_template_title;
  public $url_template_meta_title;
  public $url_template_meta_description;
  public $url_template_page_content;
  public $url_template_meta_robots;

  public function rules()
  {
    return [
      [['product_group_id'], 'required', 'message' => 'Эти поля должны быть обязательно заполнены'],
      [['title', 'meta_description', 'meta_title', 'page_content'], 'string'],

      ['url_template_on', 'each', 'rule' => ['boolean']],

      [[
        'url_template',
        'url_template_title',
        'url_template_meta_title',
        'url_template_meta_description',
        'url_template_page_content',
        'url_template_name',
        'url_template_meta_robots'
      ], 'each', 'rule' => ['string']],

      [['product_group_id'], 'integer']
    ];
  }

  public function saveData()
  {
    $result = array();

    if ($this->validate()) {
      $item = ProductGroups::findOne(['product_group_id' => $this->product_group_id]);

      if ($item) {
        if ($item->load(Yii::$app->request->post(), '')) {
          if ($item->save()) {
            Yii::$app->db
              ->createCommand('DELETE FROM ' . SeoTemplates::tableName() . ' WHERE product_group_id="' . $item->id . '"')
              ->execute();

            foreach ($this->url_template_on as $key => $val) {
              if ($val) {
                $array = [
                  'product_group_id' => $item->id,
                  'name' => $this->url_template_name[$key],
                  'title' => $this->url_template_title[$key],
                  'url_template' => $this->url_template[$key],
                  'meta_title' => $this->url_template_meta_title[$key],
                  'meta_description' => $this->url_template_meta_description[$key],
                  'page_content' => $this->url_template_page_content[$key],
                  'meta_robots' => $this->url_template_meta_robots[$key]
                ];

                $template = new SeoTemplates();

                if ($template->load($array, '')) {
                  $template->save();
                }
              }
            }

            $result['validated'] = true;
            $result['success'] = true;
            return $result;
          } else {
            $result['error'] = 'Ошибка сохранения';
            $result['errors'] = $item->errors;
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
