<?php

namespace app\models\forms;

use Yii;
use yii\base\Model;

use app\models\user\RateComments;
use app\models\user\Rates;

use app\models\helpers\Helpers;

class CommentForm extends Model
{
    public $sender_id;
	public $rate_id;
	public $receiver_id;
	public $text;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
			[['receiver_id', 'rate_id', 'text'], 'required', 'message' => 'Необходимые данные отсутствуют'],
			[['receiver_id', 'rate_id'], 'integer'],
			[['text'], 'string', 'max' => 1000, 'tooLong' => 'Значение этого поля не может превышать 1000 символов'],
        ];
    }

    public function saveData()
    {
		$result = array();
		$user = Yii::$app->user->identity;
		
		if ($this->receiver_id == $user->id) {
			$result['error'] = 'Ошибка';
			return $result;
		}
		
		if ($this->validate()) {	
			$rate = Rates::find()->where('id=' . $this->rate_id . ' AND (sender_id=' . $user->id . ' OR receiver_id=' . $user->id . ')')->one();
			
			if ($rate) {
				$message = new RateComments();
				$message->sender_id = $user->id; 
				
				if ($message->load(Yii::$app->request->post(), '') && $message->save()) {
					$rate->date_updated = date('Y-m-d H:i:s');
					$rate->save();

					$result['error'] = false;
					$result['validated'] = true;
					$result['success'] = true;				
					return $result;
				} else {
					$result['errors'] = $message->errors;
					$result['error'] = 'Ошибка сохранения: некорректные данные запроса';
				}
			} else {
				$result['error'] = 'Ошибка сохранения: некорректные данные запроса';
			}
		} else {
			$result['error'] = 'Ошибка сохранения: некорректные данные запроса';
		}
		
		return $result;
    }
}
