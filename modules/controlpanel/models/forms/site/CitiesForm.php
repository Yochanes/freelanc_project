<?php

namespace app\modules\controlpanel\models\forms\site;

use Yii;
use yii\base\Model;

use app\models\Cities;
use app\models\helpers\Helpers;

class CitiesForm extends Model
{
  public $id;
  public $name;
  public $region;
  public $domain;
  public $country_id;

  /**
   * @return array the validation rules.
   */
  public function rules()
  {
    return [
      [['name', 'country_id'], 'required', 'message' => Yii::t('app', 'required_field')],
      [['name', 'region', 'domain'], 'string', 'max' => 255, 'tooLong' => 'Длина поля должна быть не более 255 символов'],
      [['id', 'country_id'], 'integer'],
    ];
  }

  public function saveData()
  {
    $result = array();

    if ($this->validate()) {
      $item = false;

      if (!empty($this->id)) {
        $item = Cities::findOne(['id' => $this->id]);
      } else {
        $check = Cities::find()->where(['name' => $this->name, 'country_id' => $this->country_id])->count();

        if ($check) {
          $result['error'] = 'Ошибка сохранения: такой город уже существует';
          return $result;
        }

        $item = new Cities();
      }

      if ($item) {
        $item->url = 'gorod_' . Helpers::translater(mb_strtolower(trim($this->name)), 'ru', null, true);

        if ($item->load(Yii::$app->request->post(), '') && $item->save()) {
          $result['validated'] = true;
          return $result;
        } else {
          $result['error'] = 'Ошибка сохранения: город не сохранено';
        }
      } else {
        $result['error'] = 'Ошибка сохранения: город отсутствует в базе данных';
      }
    }

    $result['validated'] = false;
    return $result;
  }
}
