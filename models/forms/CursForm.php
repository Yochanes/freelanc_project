<?php

namespace app\models\forms;

use Yii;
use yii\base\Model;

class CursForm extends Model
{
  public $curs_rub;
  public $curs_eur;
  public $curs_usd;
  public $curs_rub_scale;
  public $curs_eur_scale;
  public $curs_usd_scale;
  public $use_default;

  public function rules()
  {
    return [
      [['curs_rub', 'curs_eur', 'curs_usd'], 'string', 'max' => 80, 'tooLong' => 'Длина этого поля не может превышать 100 символов'],
      [['curs_rub_scale', 'curs_eur_scale', 'curs_usd_scale'], 'integer', 'min' => 1],
      [['use_default'], 'boolean']
    ];
  }

  public function saveData()
  {

    $result = array();

    if ($this->validate()) {
      $curs = Yii::$app->user->identity->curs;
      $vals = $curs->curs_values;
      $scales = $curs->curs_scales;

      if ($this->curs_rub) {
        $vals['RUB'] = $this->curs_rub;
        $scales['RUB'] = $this->curs_rub_scale;
      }

      if ($this->curs_eur) {
        $vals['EUR'] = $this->curs_eur;
        $scales['EUR'] = $this->curs_eur_scale;
      }

      if ($this->curs_usd) {
        $vals['USD'] = $this->curs_usd;
        $scales['USD'] = $this->curs_usd_scale;
      }

      $curs->curs_values = json_encode($vals);
      $curs->curs_scales = json_encode($scales);
      $curs->use_default = $this->use_default;

      if ($curs->save()) {
        $result['validated'] = true;
        $result['success'] = true;

        \app\models\helpers\Helpers::recalcProductPrices($vals, $scales, Yii::$app->user->identity->id);
      } else {
        $result['error'] = $curs->getErrors();
      }
    } else $result['errors']['general'] = 'Ошибка сохранения: не все поля заполнены верно';

    return $result;
  }
}
