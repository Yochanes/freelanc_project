<?php

namespace app\models\forms;

use Yii;
use yii\base\Model;

class RequestConfigForm extends Model
{
  public $requests_product_groups;
  public $requests_makes;
  public $requests_email;
  public $product_groups_form;

  public function rules()
  {
    return [
      [['requests_email'], 'boolean', 'message' => 'Неверный параметр'],
      [['requests_makes'], 'each', 'rule' => ['string']],
      [['requests_product_groups'], 'each', 'rule' => ['integer']],
      ['product_groups_form', 'boolean']
    ];
  }

  public function saveData()
  {
    $result = ['success' => false, 'validated' => false];
    $user = Yii::$app->user->identity;

    if ($this->validate()) {
      $config = $user->config;

      if ($config->load(Yii::$app->request->post(), '')) {
        if ($this->product_groups_form && !$this->requests_product_groups) {
          $config->requests_product_groups = null;
        }

        $config->save();
        $result['success'] = true;
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
