<?php

namespace app\modules\controlpanel\models\forms\site;

use app\models\site\Menus;
use Yii;
use yii\base\Model;

class MenusForm extends Model
{
  public $id;
  public $name;
  public $position;
  public $active;

  public function rules()
  {
    return [
      [['position', 'name', 'active'], 'required', 'message' => Yii::t('app', 'required_field')],
      [['name'], 'string', 'max' => 100, 'tooLong' => 'Длина поля должна быть не более 100 символов'],
      [['position'], 'string', 'max' => 20, 'tooLong' => 'Длина поля должна быть не более 20 символов'],
      [['active'], 'boolean'],
      [['id'], 'integer'],
    ];
  }

  public function saveData()
  {
    $result = array();

    if ($this->validate()) {
      $item = false;

      if (!empty($this->id)) {
        $item = Menus::findOne(['id' => $this->id]);
      } else {
        $item = new Menus();
      }

      if ($item) {
        if ($item->load($_POST, '') && $item->save(false)) {
          if ($this->active) {
            $menu = Menus::updateAll(['active' => 0], 'active=1 AND position="' . $item->position . '" AND id !=' . $item->id);
          }

          $result['validated'] = true;
          return $result;
        } else {
          $result['error'] = 'Ошибка сохранения: меню не сохранено';
        }
      } else {
        $result['error'] = 'Ошибка сохранения: Меню отсутствует в базе данных';
      }
    }

    $result['validated'] = false;
    return $result;
  }
}
