<?php

namespace app\modules\controlpanel\models\forms\products;

use Yii;
use yii\base\Model;
use app\models\products\CategoryAttributes;
use app\models\products\CategoryAttributeGroups;
use app\models\helpers\Helpers;

class UploadAttributesForm extends Model
{

  public $attribute_group_id;
  public $values;

  public function rules()
  {
    return [
      [['attribute_group_id', 'values'], 'required', 'message' => 'Эти поля должны быть обязательно заполнены'],
      [['values'], 'string'],
      [['attribute_group_id'], 'integer'],
    ];
  }

  public function saveData()
  {
    $result = array();
    $to_insert = [];
    $values = [];

    if ($this->validate()) {
      $group = CategoryAttributeGroups::find()
        ->select('attribute_group_id')
        ->where(['attribute_group_id' => $this->attribute_group_id])
        ->one();

      if (!$group) {
        $result['error'] = 'Ошибка сохранения: Группа атрибутов отсутствует в базе данных';
        $result['validated'] = false;
        return $result;
      }

      $values = json_decode($this->values);

      if (!$values) {
        $result['error'] = 'Ошибка сохранения: Значения атрибутов не найдены';
        $result['validated'] = false;
        return $result;
      }

      CategoryAttributes::deleteAll(['attribute_group_id' => $this->attribute_group_id]);

      foreach ($values as $val) {
        $to_insert[] = [
          'attribute_group_id' => $this->attribute_group_id,
          'value' => $val,
          'url' => ($group->url_template ? $group->url_template : '') . Helpers::translaterUrl($val)
        ];
      }

      if ($to_insert) {
        Yii::$app->db->createCommand()
          ->batchInsert(
            CategoryAttributes::tableName(),
            ['attribute_group_id', 'value', 'url'],
            $to_insert
          )
          ->execute();
      }
    }

    $result['success'] = true;
    $result['validated'] = true;
    $result['to_insert'] = count($values);
    $result['inserted'] = count($to_insert);
    return $result;
  }
}
