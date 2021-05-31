<?php

namespace app\modules\controlpanel\models\forms\site;

use Yii;
use yii\base\Model;
use yii\web\UploadedFile;
use app\models\site\Menus;
use app\models\site\MenuItems;

class MenuItemsForm extends Model
{
  public $id;
  public $label;
  public $menu_id;
  public $parent_id;
  public $sort_order;
  public $not_loggedin_url;
  public $url;
  public $classname;
  public $info;
  public $icon;

  public function rules()
  {
    return [
      [['label', 'menu_id', 'sort_order'], 'required', 'message' => Yii::t('app', 'required_field')],
      [['label', 'classname'], 'string', 'max' => 100, 'tooLong' => 'Длина поля должна быть не более 100 символов'],
      [['url', 'not_loggedin_url', 'info'], 'string', 'max' => 255, 'tooLong' => 'Длина поля должна быть не более 255 символов'],
      [['icon'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpeg, jpg, gif'],
      [['menu_id', 'parent_id', 'id', 'sort_order'], 'integer']
    ];
  }

  public function saveData()
  {
    $result = array();
    $this->icon = UploadedFile::getInstanceByName('icon');

    if ($this->validate()) {
      $item = false;

      if (!empty($this->id)) {
        $item = MenuItems::findOne(['id' => $this->id]);
      } else {
        $item = new MenuItems();
        $menu = Menus::findOne(['id' => $this->menu_id]);

        if (!$menu) {
          $result['error'] = 'Ошибка сохранения: Меню отсутствует в базе данных';
          return $result;
        }
      }

      if ($item) {
        if ($item->load($_POST, '')) {
          if (!empty($this->icon)) {
            if ($item->icon) {
              if (file_exists(Yii::$app->basePath . $item->icon)) {
                unlink(Yii::$app->basePath . $item->icon);
              }
            }

            $img_name = Yii::$app->security->generateRandomString(10);

            if ($this->icon->saveAs(Yii::$app->basePath . '/web/menuicons/' . $img_name . '.' . $this->icon->extension, true)) {
              $item->icon = '/web/menuicons/' . $img_name . '.' . $this->icon->extension;
            }
          }

          if ($item->save(false)) {
            $result['validated'] = true;
            return $result;
          } else {
            $result['error'] = 'Ошибка сохранения: пункт меню не сохранен';
          }
        } else {
          $result['error'] = 'Ошибка сохранения: пункт меню не сохранен';
        }
      } else {
        $result['error'] = 'Ошибка сохранения: пункт меню отсутствует в базе данных';
      }
    }

    $result['validated'] = false;
    return $result;
  }
}
