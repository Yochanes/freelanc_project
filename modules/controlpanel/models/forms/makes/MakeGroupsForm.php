<?php

namespace app\modules\controlpanel\models\forms\makes;

use Yii;
use yii\base\Model;

use app\models\makes\MakeGroups;

class MakeGroupsForm extends Model
{

  public $name;
  public $filter_name;
  public $import_filter;
  public $make_group_id;
  public $use_in_car_form;
  public $url;

  public function rules()
  {
    return [
      [['name', 'filter_name', 'use_in_car_form', 'url'], 'required', 'message' => 'Эти поля должны быть обязательно заполнены'],
      [['name', 'filter_name', 'import_filter', 'url'], 'string', 'max' => 255, 'tooLong' => 'Длина этого поля не должна превышать 255 символов'],
      ['make_group_id', 'integer'],
      [['use_in_car_form'], 'boolean']
    ];
  }

  public function saveData()
  {
    $result = array();
    $user = Yii::$app->user->identity;

    if ($this->validate()) {
      $item = false;
      $old_url = false;

      if (!empty($this->make_group_id)) {
        $item = MakeGroups::find()->where(['make_group_id' => $this->make_group_id])->one();
        if ($item) $old_url = $item->url;
      } else {
        $item = new MakeGroups();

        $check = MakeGroups::find()
          ->where('name="' . $this->name . ($this->import_filter ? '" OR (import_filter IS NOT NULL AND import_filter="' . $this->import_filter . '")' : ''))
          ->count();

        if ($check) {
          $result['error'] = 'Ошибка сохранения: подобная группа уже существует';
          return $result;
        }
      }

      if ($item) {
        $group_id = $item->make_group_id;

        if ($item->load(Yii::$app->request->post(), '') && $item->save()) {
          if ($old_url) {
            Yii::$app->db
              ->createCommand('DELETE FROM urls WHERE url="' . $old_url . '"')
              ->execute();
          }

          Yii::$app->db
            ->createCommand('DELETE FROM url_params WHERE name="make_group" AND url="' . $old_url . '"')
            ->execute();

          Yii::$app->db
            ->createCommand('INSERT INTO urls (url, action, parameters) VALUES ("' . $item->url . '", "products/products", "make_group")')
            ->execute();

          Yii::$app->db
            ->createCommand('INSERT INTO url_params (`name`, `title`, `url`, `connected_id`) VALUES
              ("make_group","' . $item->name . '","' . $item->url . '","' . $item->make_group_id . '")')
            ->execute();

          $result['validated'] = true;
          $result['success'] = true;
          return $result;
        } else {
          $result['error'] = 'Ошибка сохранения: данные не сохранены';
        }
      } else {
        $result['error'] = 'Ошибка сохранения: элемент отсутствует в базе данных';
      }
    }

    $result['validated'] = false;
    return $result;
  }
}
