<?php

namespace app\models\forms;

use Yii;
use yii\base\Model;

class ConfigForm extends Model
{
  public $on_message;
  public $on_status_change;
  public $on_ntf;

  /**
   * @return array the validation rules.
   */
  public function rules()
  {
    return [
      [['on_message', 'on_status_change', 'on_ntf'], 'boolean', 'message' => 'Неверный параметр'],
    ];
  }

  public function saveData()
  {
    $result = array();
    $user = Yii::$app->user->identity;

    if ($this->validate()) {
      $config = $user->config;

      if ($config->load(Yii::$app->request->post(), '')) {
        $config->save();
        $result['validated'] = true;
      } else {
        $result['error'] = 'Возникла ошибка: неверные параметры запроса';
      }

      return $result;
    }

    $result['validated'] = false;
    return $result;
  }
}
