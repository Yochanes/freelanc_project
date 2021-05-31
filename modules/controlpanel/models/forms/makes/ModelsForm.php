<?php

namespace app\modules\controlpanel\models\forms\makes;

use Yii;
use yii\base\Model;

use app\models\makes\Makes;
use app\models\makes\Models;
use app\models\site\Pages;
use app\models\helpers\Helpers;

class ModelsForm extends Model
{
  public $id;
  public $make_id;
  public $name;

  public function rules()
  {
    return [
      [['name', 'make_id'], 'required', 'message' => 'Эти поля должны быть обязательно заполнены'],
      ['name', 'string', 'max' => 255, 'tooLong' => 'Длина этого поля не должна превышать 255 символов'],
      [['id', 'make_id'], 'integer'],
    ];
  }

  public function saveData()
  {
    $result = array();

    if ($this->validate()) {
      $item = false;

      if (!empty($this->id)) {
        $item = Models::findOne(['id' => $this->id]);
      } else {
        $check = Models::find()
          ->where(['name' => $this->name, 'make_id' => $this->make_id])
          ->count();

        if ($check) {
          $result['error'] = 'Ошибка сохранения: подобная модель уже существует';
          return $result;
        }

        $item = new Models();
      }

      if ($item && $item->load(Yii::$app->request->post(), '')) {
        $make = Makes::findOne(['id' => $this->make_id]);

        if (!$make) {
          $result['error'] = 'Ошибка сохранения: марка не найдена';
          return $result;
        }

        if ($item->url) {
          Yii::$app->db
            ->createCommand('DELETE FROM url_params WHERE name="model" AND url="' . $item->url . '" AND name="model"')
            ->execute();
        }

        $new_url = 'mod-' . Helpers::translaterUrl($item->name);
        $item->make_url = $make->url;
        $item->url = $new_url;

        if ($item->save()) {
          Yii::$app->db
            ->createCommand('INSERT INTO url_params (`name`, `title`, `url`, `connected_id`) VALUES
              ("model","' . $item->name . '","' . $item->url . '","' . $item->id . '")')
            ->execute();

          $result['validated'] = true;
          return $result;
        } else {
          $result['error'] = 'Ошибка сохранения: марка не сохранена';
        }
      } else {
        $result['error'] = 'Ошибка сохранения: марка отсутствует в базе данных';
      }
    }

    $result['validated'] = false;
    return $result;
  }
}
