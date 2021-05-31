<?php

namespace app\modules\controlpanel\models\forms\makes;

use Yii;
use yii\base\Model;

use app\models\makes\Makes;
use app\models\makes\Models;
use app\models\makes\Generations;
use app\models\site\Pages;
use app\models\helpers\Helpers;

class GenerationsForm extends Model
{
  public $id;
  public $model_id;
  public $name;
  public $alt_name;
  public $years;

  public function rules()
  {
    return [
      [['name', 'model_id'], 'required', 'message' => 'Эти поля должны быть обязательно заполнены'],
      [['name', 'alt_name', 'years'], 'string', 'max' => 255, 'tooLong' => 'Длина этого поля не должна превышать 255 символов'],
      [['id', 'model_id'], 'integer'],
    ];
  }

  public function saveData()
  {
    $result = array();

    if ($this->validate()) {
      $item = false;

      if (!empty($this->id)) {
        $item = Generations::findOne(['id' => $this->id]);
      } else {
        $check = Generations::find()->where(['name' => $this->name, 'model_id' => $this->model_id])->count();

        if ($check) {
          $result['error'] = 'Ошибка сохранения: подобное поколение уже существует';
          return $result;
        }

        $item = new Generations();
      }

      if ($item && $item->load(Yii::$app->request->post(), '')) {
        $model = Models::findOne(['id' => $this->model_id]);

        if (!$model) {
          $result['error'] = 'Ошибка сохранения: марка не найдена';
          return $result;
        }

        if ($item->url) {
          Yii::$app->db
            ->createCommand('DELETE FROM url_params WHERE name="generation" AND url="' . $item->url . '" AND name="generation"')
            ->execute();
        }

        $new_url = 'gen-' . Helpers::translaterUrl($item->name);
        $new_url = str_replace('[', '', $new_url);
        $new_url = str_replace(']', '', $new_url);
        $item->url = $new_url;

        if ($item->save()) {
          Yii::$app->db
            ->createCommand('INSERT INTO url_params (`name`, `title`, `url`, `connected_id`) VALUES
              ("generation","' . $item->name . '","' . $item->url . '","' . $item->id . '")')
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
