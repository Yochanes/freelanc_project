<?php

namespace app\modules\controlpanel\models\forms\site;

use Yii;
use app\models\site\SupportCategory;
use yii\web\UploadedFile;
use yii\base\Model;

class SupportForm extends Model
{
  public $id;
  public $title;
  public $image;
  public $sort_order;
  public $parent_id;

  public function rules()
  {
    return [
      [['title'], 'required', 'message' => Yii::t('app', 'required_field')],
      [['title'], 'string', 'max' => 255, 'tooLong' => 'Длина поля должна быть не более 255 символов'],
      [['image'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpeg, jpg, gif'],
      [['sort_order', 'id', 'parent_id'], 'integer']
    ];
  }

  public function saveData()
  {
    $result = array();
    $this->image = UploadedFile::getInstanceByName('image');

    if ($this->validate()) {
      $item = false;

      if (!empty($this->id)) {
        $item = SupportCategory::findOne(['id' => $this->id]);
      } else {
        $item = new SupportCategory();
      }

      if ($item) {
        if ($item->load($_POST, '')) {
          if ($item->image) {
            if (file_exists(Yii::$app->basePath . $item->image)) {
              unlink(Yii::$app->basePath . $item->image);
            }
          }

          $img_name = Yii::$app->security->generateRandomString(10);
          if ($item->image && $this->image->saveAs(Yii::$app->basePath . '/web/support/' . $img_name . '.' . $this->image->extension, true)) {
            $item->image = '/web/support/' . $img_name . '.' . $this->image->extension;
          }

          if ($item->save(false)) {
            $result['validated'] = true;
            return $result;
          } else {
            $result['error'] = 'Ошибка сохранения: категория не сохранена';
          }
        } else {
          $result['error'] = 'Ошибка сохранения: категория не сохранена';
        }
      } else {
        $result['error'] = 'Ошибка сохранения: Категория отсутствует в базе данных';
      }
    }

    $result['validated'] = false;
    return $result;
  }
}
