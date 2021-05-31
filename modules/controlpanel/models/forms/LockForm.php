<?php

namespace app\modules\controlpanel\models\forms;

use Yii;
use yii\base\Model;
use app\models\User;

/**
 * LoginForm is the model behind the login form.
 *
 * @property User|null $user This property is read-only.
 *
 */
class LockForm extends Model
{
    public $user_id;
    public $reason;


    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
			[['user_id'], 'required'],	
			[['reason'], 'string', 'max'=>1000]
        ];
    }

    public function saveData()
    {
		$result = array();
		$result['validated'] = false;
		
		if ($this->validate()) {
			$item = User::find()->where(['id' => $this->user_id])->one();
			
			if ($item) {	
				$item->state = User::STATE_LOCKED;
				$item->reason = $this->reason;
				
				if ($item->save(false)) {
					$result['validated'] = true;
					$result['success'] = true;
				} else {
					$result['error'] = 'Ошибка блокировки пользователя: статус не сохранен';
				}
			} else {
				$result['error'] = 'Ошибка блокировки пользователя: пользователь не найден';
			}
        }
		
        return $result;
    }
}
