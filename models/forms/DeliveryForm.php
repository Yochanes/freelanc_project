<?php

namespace app\models\forms;

use app\models\helpers\GTD_API;
use Yii;
use yii\base\Model;

class DeliveryForm extends Model
{
  public $from;
  public $to;
  public $currency;
  public $price;
  public $width;
  public $height;
  public $length;
  public $weight;
  public $quantity;
  public $pick_up;
  public $delivery;

  public function rules()
  {
    return [
      [['from', 'currency', 'price', 'width', 'height', 'length', 'weight'], 'required', 'message' => Yii::t('app', 'required_field')],
      ['to', 'required', 'message' => 'Выберите город в который осуществляется доставка'],
      ['currency', 'string', 'max' => 3, 'tooLong' => 'Длина этого поля не может превышать 100 символов'],
      [['price', 'width', 'height', 'length', 'weight', 'quantity'], 'integer'],
      [['from', 'to'], 'string'],
      [['pick_up', 'delivery'], 'boolean']
    ];
  }

  public function calculate()
  {
    $result = array(
      'errors' => [],
      'error' => true
    );

    if ($this->validate()) {
      $result = array();

      $data = array(
        'city_pickup_code' => $this->from,
        'city_delivery_code' => $this->to,
        'declared_price' => $this->price,
        'currency_code' => [$this->currency],
        'pick_up' => $this->pick_up,
        'delivery' => $this->delivery,
        'places' => array([
          'count_place' => !$this->quantity ? 1 : $this->quantity,
          'weight' => $this->weight,
          'width' => $this->width,
          'height' => $this->height,
          'length' => $this->length
        ])
      );

      $calc = GTD_API::calculateDelivery($data);

      if ($calc && (!isset($calc['error']) || !$calc['error'])) {
        $result['success'] = true;
        $result['data'] = $calc;
        return $result;
      } else {
        $result['error'] = $calc['error'];
      }
    } else $result['errors']['general'] = 'Ошибка расчета стоимости: не все поля заполнены верно';

    return $result;
  }
}
