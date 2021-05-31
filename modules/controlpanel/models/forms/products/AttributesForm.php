<?php

namespace app\modules\controlpanel\models\forms\products;

use Yii;
use yii\base\Model;

use app\models\helpers\Helpers;
use app\models\products\CategoryAttributes;
use app\models\products\CategoryAttributeGroups;

class AttributesForm extends Model
{

  public $value;
  public $attribute_id;
  public $attribute_group_id;
  public $alt_values;
  public $catalog_text;

  public function rules()
  {
    return [
      [['value', 'attribute_group_id'], 'required', 'message' => 'Эти поля должны быть обязательно заполнены'],
      [['value', 'catalog_text'], 'string', 'max' => 255, 'tooLong' => 'Длина этого поля не должна превышать 255 символов'],
      [['alt_values'], 'each', 'rule' => ['string']],
      [['attribute_id', 'attribute_group_id'], 'integer'],
    ];
  }

  public function saveData()
  {
    $result = array();
    $user = Yii::$app->user->identity;

    if ($this->validate()) {
      $item = false;

      if (!empty($this->attribute_id)) {
        $item = CategoryAttributes::findOne(['attribute_id' => $this->attribute_id]);
      } else {
        $item = new CategoryAttributes();

        $check = CategoryAttributes::find()
          ->where(['value' => $this->value, 'attribute_group_id' => $this->attribute_group_id])
          ->count();

        if ($check) {
          $result['error'] = 'Ошибка сохранения: подобный аттрибут уже существует';
          return $result;
        }
      }

      if ($item) {
        $search = '';

        foreach ($this->alt_values as $str) {
          if (trim($str)) $search .= $str . ';';
        }

        $search = mb_substr($search, 0, -1);
        $item->alt_values = $search;

        if ($item->url) {
          Yii::$app->db
            ->createCommand('DELETE FROM url_params WHERE name LIKE "attribute_" AND url="' . $item->url . '" AND name="make"')
            ->execute();
        }

        $group = CategoryAttributeGroups::findOne(['attribute_group_id' => $this->attribute_group_id]);

        if (!$group) {
          $result['error'] = 'Ошибка сохранения: группа аттрибутов не найдена';
          return $result;
        }

        $old_template = $item->url;
        $item->url = ($group->url_template ? $group->url_template : '') . Helpers::translaterUrl($this->value);
        $new_template = $item->url;

        if ($item->load(Yii::$app->request->post(), '') && $item->save()) {
          Yii::$app->db
            ->createCommand('UPDATE product_attributes SET url="'.$new_template.'" WHERE url="'.$old_template.'"')
            ->execute();

          Yii::$app->db
            ->createCommand('UPDATE user_cars_attributes SET url="'.$new_template.'" WHERE url="'.$old_template.'"')
            ->execute();

          Yii::$app->db
            ->createCommand('UPDATE request_attributes SET url="'.$new_template.'" WHERE url="'.$old_template.'"')
            ->execute();

          Yii::$app->db
            ->createCommand('INSERT INTO url_params (`name`, `title`, `url`, `connected_id`) VALUES
              ("attribute_' . $item->attribute_group_id . '","' . $group->name . ' ' . $item->value . '","' . $item->url . '","' . $item->attribute_id . '")')
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
    }

    $result['validated'] = false;
    return $result;
  }
}
