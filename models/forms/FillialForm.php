<?php

namespace app\models\forms;

use Yii;
use yii\base\Model;
use app\models\user\Fillial;
use app\models\Countries;
use app\models\Cities;

class FillialForm extends Model
{
  public $id;
  public $name;
  public $country;
  public $city;
  public $address;
  public $time;
  public $whatsapp;
  public $viber;
  public $telegram;

  public function rules()
  {
    return [
      [['id', 'name', 'country', 'city', 'address', 'time'], 'required', 'message' => 'Это поле должно быть заполнено обязательно'],
      [['name', 'whatsapp', 'viber', 'telegram', 'address', 'city'], 'string', 'max' => 255, 'tooLong' => 'Длина этого поля не может превышать 255 символов'],
      ['time', 'string', 'max' => 500, 'tooLong' => 'Длина этого поля не может превышать 500 символов'],
      [['id', 'country'], 'integer']
    ];
  }

  public function saveData()
  {
    $result = array('validated' => false);
    $user = Yii::$app->user->identity;

    $country = Countries::findOne(['id' => $this->country]);

    if (!$country) {
      $result['error'] = 'Ошибка добавления филлиала: такой страны не существует';
      $result['errors']['country'] = 'Неверная страна';
      return $result;
    }

    $fillial = Fillial::findOne(['id' => $this->id, 'user_id' => $user->id]);

    if (!$fillial) {
      $result['error'] = 'Ошибка сохранения филлиала: филлиал не найден';
      return $result;
    }

    if ($this->validate()) {
      if ($fillial->load(Yii::$app->request->post(), '')) {
        $fillial->country = $country->name;
        $fillial->empty = 0;

        if ($fillial->save()) {
          $result['validated'] = true;
          $result['success'] = true;
        } else {
          $result['error'] = 'Ошибка сохранения: не все поля заполнены корректно';
          foreach ($fillial->errors as $err => $v) $this->addError($err, $v);
        }
      } else {
        $result['error'] = 'Ошибка сохранения: неверные данные филлиала';
      }
    } else {
      $result['error'] = 'Ошибка сохранения: неверные данные филлиала';
    }

    return $result;
  }
}
