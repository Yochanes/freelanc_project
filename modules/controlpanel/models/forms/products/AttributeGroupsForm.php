<?php

namespace app\modules\controlpanel\models\forms\products;

use Yii;
use yii\base\Model;
use app\models\helpers\Helpers;

use app\models\products\CategoryAttributeGroups;

class AttributeGroupsForm extends Model
{

  public $name;
  public $filter_name;
  public $important;
  public $attribute_group_id;
  public $use_in_car_form;
  public $use_in_category;
  public $alt_names;
  public $url_template;
  public $catalog_suffix;
  public $catalog_prefix;

  public function rules()
  {
    return [
      [['name', 'filter_name', 'important', 'use_in_car_form'], 'required', 'message' => 'Эти поля должны быть обязательно заполнены'],
      [['name', 'filter_name', 'catalog_suffix', 'catalog_prefix'], 'string', 'max' => 255, 'tooLong' => 'Длина этого поля не должна превышать 255 символов'],
      ['url_template', 'string', 'max' => 100, 'tooLong' => 'Длина этого поля не должна превышать 100 символов'],
      [['important', 'use_in_car_form', 'use_in_category'], 'boolean'],
      [['alt_names'], 'each', 'rule' => ['string']],
      [['attribute_group_id'], 'integer'],
    ];
  }

  public function saveData()
  {
    $result = array();
    $user = Yii::$app->user->identity;

    if ($this->validate()) {
      $item = false;

      if (!empty($this->attribute_group_id)) {
        $item = CategoryAttributeGroups::findOne(['attribute_group_id' => $this->attribute_group_id]);
      } else {
        $item = new CategoryAttributeGroups();

        $check = CategoryAttributeGroups::find()
          ->where('name="' . $this->name . '"')
          ->count();

        if ($check) {
          $result['error'] = 'Ошибка сохранения: подобная группа уже существует';
          return $result;
        }
      }

      if ($item) {
        $search = '';

        foreach ($this->alt_names as $str) {
          if (trim($str)) $search .= $str . ';';
        }

        $search = mb_substr($search, 0, -1);
        $item->alt_names = $search;

        if ($item->load(Yii::$app->request->post(), '') && $item->save()) {
          $template = ($item->url_template ? $item->url_template : '');

          foreach ($item->attributesArray as $a) {
            $new_template = $template . Helpers::translaterUrl($a->value);
            $old_template = $a->url;
            $a->url = $new_template;

            if ($a->save()) {
              Yii::$app->db
                ->createCommand('UPDATE product_attributes SET name="' . $item->filter_name . '", url="'.$new_template.'" WHERE url="'.$old_template.'"')
                ->execute();

              Yii::$app->db
                ->createCommand('UPDATE user_cars_attributes SET name="' . $item->filter_name . '", url="'.$new_template.'" WHERE url="'.$old_template.'"')
                ->execute();

              Yii::$app->db
                ->createCommand('UPDATE request_attributes SET name="' . $item->filter_name . '", url="'.$new_template.'" WHERE url="'.$old_template.'"')
                ->execute();

              Yii::$app->db
                ->createCommand('UPDATE products SET attributes_list=REPLACE(attributes_list,"'.$old_template.'","'.$new_template.'")')
                ->execute();
            }
          }

          $result['validated'] = true;
          $result['success'] = true;
          return $result;
        } else {
          $result['error'] = 'Ошибка сохранения: данные не сохранены';
        }
      } else {
        $result['error'] = 'Ошибка сохранения: элемент отсутствует в базе данных';
      }
    }

    $result['validated'] = false;
    return $result;
  }
}
