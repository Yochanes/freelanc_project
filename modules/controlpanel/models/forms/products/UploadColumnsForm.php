<?php

namespace app\modules\controlpanel\models\forms\products;

use Yii;
use yii\base\Model;
use app\models\site\UploadColumns;

class UploadColumnsForm extends Model
{

  public $group_id;
  public $param_name;
  public $text_value;
  public $id;

  public function rules()
  {
    return [
      [['param_name', 'text_value'], 'required', 'message' => 'Эти поля должны быть обязательно заполнены'],
      [['param_name', 'text_value'], 'string', 'max' => 255, 'tooLong' => 'Длина этого поля не должна превышать 255 символов'],
      [['id', 'group_id'], 'integer'],
    ];
  }

  public function saveData()
  {
    $result = array();

    if ($this->validate()) {
      $item = false;

      if (!empty($this->id)) {
        $item = UploadColumns::findOne(['id' => $this->id]);
      } else {
        $check = UploadColumns::find()
          ->where([
            'param_name' => $this->param_name,
            'text_value' => trim(mb_strtolower($this->text_value)),
            'group_id' => $this->group_id ? $this->group_id : null
          ])
          ->count();

        if ($check) {
          $result['error'] = 'Ошибка сохранения: такой параметр уже существуют';
          return $result;
        }

        $item = new UploadColumns();
      }

      if ($item) {
        if ($item->load(Yii::$app->request->post(), '')) {
          if ($item->save()) {
            $result['validated'] = true;
            $result['success'] = true;
            return $result;
          } else {
            $result['error'] = 'Ошибка сохранения: данные не сохранены';
          }
        } else {
          $result['error'] = 'Ошибка сохранения: элемент отсутствует в базе данных';
        }
      } else {
        $result['error'] = 'Ошибка сохранения: элемент отсутствует в базе данных';
      }
    }

    $result['validated'] = false;
    return $result;
  }
}
