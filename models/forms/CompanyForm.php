<?php

namespace app\models\forms;

use Yii;
use yii\base\Model;

class CompanyForm extends Model
{
  public $address;
  public $inn;
  public $ogrn;
  public $name;
  public $info;
  public $is_visible;

  public function rules()
  {
    return [
      [['address', 'inn', 'ogrn', 'name', 'is_visible'], 'required', 'message' => Yii::t('app', 'required_field')],
      [['address', 'name'], 'string', 'max' => 255, 'tooLong' => 'Длина этого поля не может превышать 255 символов'],
      [['inn', 'ogrn'], 'string', 'max' => 100, 'tooLong' => 'Длина этого поля не может превышать 100 символов'],
      [['info'], 'string', 'max' => 1000, 'tooLong' => 'Длина этого поля не может превышать 1000 символов'],
      [['is_visible'], 'boolean'],
    ];
  }

  public function saveData()
  {
    $result = array();
    $user = Yii::$app->user->identity;
    $c = $user->company;

    if ($this->validate() && $c->load(Yii::$app->request->post(), '')) {
      $c->is_valid = true;
      $c->save();
      $result['validated'] = true;
      $result['success'] = true;
      return $result;
    }

    $result['validated'] = false;
    return $result;
  }
}
