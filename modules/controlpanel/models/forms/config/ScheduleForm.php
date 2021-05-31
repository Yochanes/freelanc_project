<?php

namespace app\modules\controlpanel\models\forms\config;

use yii\base\Model;

use app\models\site\ConfigSchedule;

class ScheduleForm extends Model
{
  public $id;
  public $token;
  public $curs_schedule;
  public $request_schedule;
  public $curs_schedule_rate;
  public $request_schedule_rate;

  public function rules()
  {
    return [
      [['id', 'token', 'curs_schedule', 'request_schedule', 'curs_schedule_rate', 'request_schedule_rate'], 'required', 'message' => 'Это поле должно быть заполнено'],
      ['token', 'string', 'max' => 255, 'tooLong' => 'Длина этого поля не может превышать 255 символов'],
      [['curs_schedule', 'request_schedule'], 'boolean'],
      [['curs_schedule_rate', 'request_schedule_rate'], 'integer', 'max' => '9999'],
      ['id', 'integer'],
    ];
  }

  public function saveData()
  {
    $result = array();

    if ($this->validate()) {
      $item = ConfigSchedule::findOne(['id' => $this->id]);

      if ($item) {
        if ($item->load($_POST, '') && $item->save()) {
          $result['validated'] = true;
          $result['success'] = true;

          $post = [
            'setScheduleRates' => 1,
            'token' => '@dn0h@3,0hr@',
            'config' => json_encode($item->attributes)
          ];

          $host = 'http://localhost:8080/schedule';
          $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, $host);
          curl_setopt($ch, CURLOPT_POST, 1);
          curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
          curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
          curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
          $resp = curl_exec($ch);
          curl_close($ch);
          $result['api_response'] = $resp;

          if ($result['api_response']) {
            unset($result['errors']);
          }

          return $result;
        } else {
          $result['error'] = 'Ошибка сохранения: настройки не сохранены';
        }
      } else {
        $result['error'] = 'Ошибка сохранения: Настройки отсутствуют в базе данных';
      }
    }

    $result['validated'] = false;
    return $result;
  }
}
