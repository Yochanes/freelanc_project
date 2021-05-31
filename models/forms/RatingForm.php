<?php

namespace app\models\forms;

use app\models\user\RateAwait;
use app\models\user\Rates;
use Yii;
use yii\base\Model;

class RatingForm extends Model
{
  public $sender_id;
  public $dialog_id;
  public $receiver_id;
  public $item_id;
  public $item_name;
  public $item_type;
  public $item_url;
  public $text;
  public $rate;

  public function rules()
  {
    return [
      [['receiver_id', 'rate', 'item_name', 'item_id', 'item_type'], 'required'],
      [['sender_id', 'receiver_id', 'item_id'], 'integer'],
      ['rate', 'integer', 'min' => 1, 'max' => 5],
      [['item_name', 'item_url'], 'string', 'max' => 255],
      ['item_type', 'string', 'max' => 11],
      [['text'], 'string', 'max' => 1000, 'tooLong' => 'Длина этого поля не должна превышать 1000 символов']
    ];
  }

  public function saveData()
  {
    $result = array();
    $user = Yii::$app->user->identity;
    $rate = new Rates();
    $rate->sender_id = $user->id;

    if ($this->validate() && $rate->load(Yii::$app->request->post(), '')) {
      $user_id = Yii::$app->user->identity->id;

      $check = Yii::$app->db
        ->createCommand('SELECT ura.id AS await_id, 
          (SELECT ur.id FROM user_rates ur WHERE ur.sender_id=' . $user_id . '
				  AND item_id=' . $this->item_id . ' AND item_type="' . $this->item_type . '") AS rate_id 
				  FROM user_rate_await ura WHERE ura.user_id=' . $user_id . ' 
				  AND ura.obj_id=' . $this->item_id . ' AND ura.obj_type="' . $this->item_type . '" AND ura.state=' . RateAwait::STATE_USED)
        ->queryAll();

      if (!$check && $rate->save()) {
        Yii::$app->db
          ->createCommand('UPDATE user_rate_await SET state=' . RateAwait::STATE_USED .
          ' WHERE user_id=' . $user_id . ' AND obj_id=' . $this->item_id .
          ' AND obj_type="' . $this->item_type . '"')
          ->execute();

        Yii::$app->db
          ->createCommand('UPDATE users SET rating=rating+' . $rate->rate .
          ' WHERE id=' . $rate->receiver_id)
          ->execute();

        Yii::$app->db
          ->createCommand('UPDATE users SET rate_num=rate_num+1 WHERE id=' . $rate->receiver_id)
          ->execute();

        $result['success'] = true;
      } else {
        $result['error'] = 'Ошибка сохранения: некорректные данные запроса';
      }
    } else {
      $result['error'] = 'Ошибка сохранения: некорректные данные запроса';
    }

    return $result;
  }
}
