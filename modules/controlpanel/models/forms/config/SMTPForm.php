<?php

namespace app\modules\controlpanel\models\forms\config;

use Yii;
use yii\base\Model;

use app\models\config\SMTP;

class SMTPForm extends Model
{
	public $id;
	public $site_email;
	public $host;
	public $username;
	public $password;
	public $port;
	public $active;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['host', 'username', 'password', 'port', 'active'], 'required', 'message' => 'Это поле должно быть заполнено'],
			[['site_email', 'host', 'username', 'password'], 'string', 'max' => 100, 'tooLong' => 'Длина этого поля не может превышать 100 символов'],
			['site_email', 'email', 'message' => 'Неверный email адрес'],
			['active', 'boolean'],
            [['port', 'id'], 'integer']
        ];
    }

    public function saveData()
    {
		$result = array();
		
		if ($this->validate()) {
			$item;
		
			if (!empty($this->id)) {
				$item = SMTP::findOne(['id' => $this->id]);
			} else {
				$item = new SMTP();
			}
			
			if ($item) {	
				if ($item->load($_POST, '') && $item->save()) {
					$result['validated'] = true;
					
					if ($this->active) {
						$conf = SMTP::findOne('id != ' . $this->id . ' AND active = 1');
						
						if ($conf) {
							$conf->active = 0;
							$conf->save();
						}
					}
					
					return $result;
				} else {
					$result['error'] = 'Ошибка сохранения: настройки не сохраненs';
				}
			} else {
				$result['error'] = 'Ошибка сохранения: Настройки отсутствуют в базе данных';
			}
        }
		
		$result['validated'] = false; 
        return $result;
    }
}
