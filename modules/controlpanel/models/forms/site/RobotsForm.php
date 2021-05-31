<?php

namespace app\modules\controlpanel\models\forms\site;

use Yii;
use yii\base\Model;

use app\models\site\Robots;

class RobotsForm extends Model
{
	public $id;
	public $url;
	public $content;
	public $default_flag;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            ['content', 'required', 'message' => Yii::t('app', 'required_field')],
            ['content', 'string'],
            ['url', 'string', 'max' => 255],
            ['default_flag', 'boolean'],
            ['id', 'integer'],
        ];
    }

    public function saveData()
    {
		$result = array();
		
		if ($this->validate()) {
			$item = false;
		
			if (!empty($this->id)) {
				$item = Robots::findOne(['id' => $this->id]);
			} else {
			    $item = new Robots();

			    $check = Robots::find()->where(['url' => $this->url])->count();

			    if ($check) {
                    $result['validated'] = false;
                    $result['error'] = 'Ошибка сохранения: robots.txt с таким url уже существует в базе';
                    return $result;
                }
            }
			
			if ($item) {
				if ($item->load($_POST, '') && $item->save()) {
                    if ($this->default_flag) {
                        Yii::$app->db->createCommand('UPDATE config_robots SET default_flag=0 
                                                          WHERE default_flag=1 AND id!=' . $item->id)->execute();
                    }

					$result['validated'] = true;
					return $result;
				} else {
					$result['error'] = 'Ошибка сохранения: robots.txt не сохранено';
				}
			} else {
				$result['error'] = 'Ошибка сохранения: robots.txt отсутствует в базе данных';
			}
        }
		
		$result['validated'] = false; 
        return $result;
    }
}
