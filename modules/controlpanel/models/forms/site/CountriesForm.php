<?php

namespace app\modules\controlpanel\models\forms\site;

use Yii;
use yii\base\Model;

use app\models\Countries;
use app\models\helpers\Helpers;

class CountriesForm extends Model
{
	public $id;
	public $name;
	public $domain;
	public $code;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
			[['name'], 'required', 'message' => Yii::t('app', 'required_field')],
			[['name', 'domain'], 'string', 'max'=>255, 'tooLong'=>'Длина поля должна быть не более 255 символов'],
            ['code', 'string', 'max'=>2, 'tooLong'=>'Длина поля должна быть не более 2 символов'],
			[['id'], 'integer'],
        ];
    }

    public function saveData()
    {
		$result = array();
		
		if ($this->validate()) {
			$item = false;
		
			if (!empty($this->id)) {
				$item = Countries::findOne(['id' => $this->id]);
			} else {
				$check = Countries::find()->where(['name' => $this->name])->count();
				
				if ($check) {
					$result['error'] = 'Ошибка сохранения: такая страна уже существует';
					return $result;
				}
				
				$item = new Countries();
			}
			
			if ($item) {			
				if ($item->load(Yii::$app->request->post(), '') && $item->save()) {					
					$result['validated'] = true;
					return $result;
				} else {
					$result['error'] = 'Ошибка сохранения: страна не сохранена';
				}
			} else {
				$result['error'] = 'Ошибка сохранения: страна отсутствует в базе данных';
			}
        }
		
		$result['validated'] = false; 
        return $result;
    }
}
