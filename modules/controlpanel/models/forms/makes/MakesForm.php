<?php

namespace app\modules\controlpanel\models\forms\makes;

use Yii;
use yii\base\Model;
use yii\web\UploadedFile;

use app\models\helpers\Helpers;
use app\models\makes\Makes;

class MakesForm extends Model
{
  public $id;
  public $make_group_id;
  public $name;
  public $image;

  public function rules()
  {
    return [
      [['name', 'make_group_id'], 'required', 'message' => 'Эти поля должны быть обязательно заполнены'],
      [['name'], 'string', 'max' => 255, 'tooLong' => 'Длина этого поля не должна превышать 255 символов'],
      [['image'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpeg, jpg, gif'],
      ['make_group_id', 'each', 'rule' => ['integer']],
      ['id', 'integer']
    ];
  }

  public function saveData()
  {
    $result = array();
    $user = Yii::$app->user->identity;
    $this->image = UploadedFile::getInstanceByName('image');

    if ($this->validate()) {
      $item = false;
      $old_url = false;

      if (!empty($this->id)) {
        $item = Makes::findOne(['id' => $this->id]);
        $old_url = $item->url;

        if ($item) {
          Yii::$app->db
            ->createCommand('DELETE FROM make_to_group WHERE make_id="' . $item->id . '"')
            ->execute();

          Yii::$app->db
            ->createCommand('DELETE FROM url_params WHERE name="make" AND url="' . $item->url . '" AND name="make"')
            ->execute();
        }
      } else {
        $check = Makes::find()->where(['name' => $this->name])->count();

        if ($check) {
          $result['error'] = 'Ошибка сохранения: марка с таким именем уже существует';
          return $result;
        }

        $item = new Makes();
      }

      if ($item && $item->load(Yii::$app->request->post(), '')) {
        if (!empty($this->image)) {
          if ($item->image) {
            if (file_exists(Yii::$app->basePath . $item->image)) {
              unlink(Yii::$app->basePath . $item->image);
            }
          }

          $img_name = Helpers::translaterUrl($item->name);

          if ($this->image->saveAs(Yii::$app->basePath . '/web/makeicons/' . $img_name . '.' . $this->image->extension, true)) {
            $item->image = '/web/makeicons/' . $img_name . '.' . $this->image->extension;
          }
        }

        $new_url = 'm-' . Helpers::translaterUrl($item->name);
        $item->url = $new_url;

        if ($item->save()) {
          $make_to_groups = [];

          foreach ($this->make_group_id as $mg) {
            if ($mg) {
              $make_to_groups[] = array($mg, $item->id);
            }
          }

          if ($make_to_groups) {
            Yii::$app->db
              ->createCommand()
              ->batchInsert('make_to_group', ['make_group_id', 'make_id'], $make_to_groups)
              ->execute();
          }

          $result['validated'] = true;

          if ($old_url != $new_url) {
            Yii::$app->db
              ->createCommand('UPDATE make_models SET make_url="' . $new_url . '" WHERE make_id=' . $item->id)
              ->execute();
          }

          Yii::$app->db
            ->createCommand('INSERT INTO url_params (`name`, `title`, `url`, `connected_id`) VALUES
              ("make","' . $item->name . '","' . $item->url . '","' . $item->id . '")')
            ->execute();

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
