<?php

namespace app\models\forms;

use Yii;
use yii\base\Model;
use yii\web\UploadedFile;

use yii\imagine\Image;

class UserDetailsForm extends Model
{

  public function rules()
  {
    return [];
  }

  public function saveData()
  {
    $result = array();
    $user = Yii::$app->user->identity;

    if ($this->validate()) {
      $details = $user->details;
      $det_save = false;
      $waranty = [];
      $refund = [];
      $payment = [];
      $paymcity = [];
      $paymship = [];
      $conditions = [];
      $delivery = [];
      $words = ['waranty', 'refund', 'payment', 'delivery', 'conditions', 'paymcity', 'paymship'];
      $found = [];

      foreach ($words as $word) {
        foreach (Yii::$app->request->post() as $param => $val) {
          if (strpos($param, $word) !== false) {
            $details->{$word} = [
              'checked' => 0,
              'value' => '',
              'label' => ''
            ];

            $found[$word] = $word;

            if (strpos($param, '_val') === false && strpos($param, '_label') === false ) {
              $exp = explode('_', $param);
              $code = end($exp);

              ${$word}[$code] = [
                'checked' => $val,
                'value' => htmlspecialchars(Yii::$app->request->post($param . '_val')),
                'label' => Yii::$app->request->post($param . '_label'),
              ];
            }

            $det_save = true;
          }
        }
      }

      foreach ($found as $word) {
        $details->{$word} = ${$word};
      }

      if ($details->load(Yii::$app->request->post(), '') || $det_save) {
        $details->save();
      }

      $result['validated'] = true;
      return $result;
    }

    $result['validated'] = false;
    return $result;
  }
}
