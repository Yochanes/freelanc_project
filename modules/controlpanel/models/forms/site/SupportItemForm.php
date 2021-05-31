<?php

namespace app\modules\controlpanel\models\forms\site;

use app\models\site\SupportCategoryItem;
use Yii;
use yii\base\Model;

class SupportItemForm extends Model
{
  public $id;
  public $title;
  public $text;
  public $category_id;
  public $sort_order;

  public function rules()
  {
    return [
      [['title', 'text', 'category_id'], 'required', 'message' => Yii::t('app', 'required_field')],
      [['title'], 'string', 'max' => 255, 'tooLong' => 'Длина поля должна быть не более 255 символов'],
      [['text'], 'string'],
      [['sort_order', 'category_id', 'id'], 'integer']
    ];
  }

  public function saveData()
  {
    $result = array();

    if ($this->validate()) {
      $item = false;

      if (!empty($this->id)) {
        $item = SupportCategoryItem::findOne(['id' => $this->id]);
      } else {
        $item = new SupportCategoryItem();
      }

      if ($item) {
        if ($item->load($_POST, '') && $item->save(false)) {
          $result['validated'] = true;
          return $result;
        } else {
          $result['error'] = 'Ошибка сохранения: вопрос не сохранен';
        }
      } else {
        $result['error'] = 'Ошибка сохранения: Вопрос отсутствует в базе данных';
      }
    }

    $result['validated'] = false;
    return $result;
  }
}
