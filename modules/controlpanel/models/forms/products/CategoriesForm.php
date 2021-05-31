<?php

namespace app\modules\controlpanel\models\forms\products;

use Yii;
use yii\base\Model;

use app\models\site\Pages;
use app\models\helpers\Helpers;
use app\models\products\Categories;

class CategoriesForm extends Model
{
  public $category_id;
  public $name;
  public $synonym;
  public $connected_category;
  public $partnum_required;
  public $attributes_required;

  public function rules()
  {
    return [
      ['name', 'required', 'message' => 'Эти поля должны быть обязательно заполнены'],
      ['name', 'string', 'max' => 255, 'tooLong' => 'Длина этого поля не может превышать 255 символов'],
      [['synonym', 'connected_category'], 'each', 'rule' => ['string']],
      ['attributes_required', 'each', 'rule' => ['integer']],
      ['generation_required', 'boolean'],
      ['partnum_required', 'boolean'],
      [['category_id', 'catalog_category_id'], 'integer'],
    ];
  }

  public function saveData()
  {
    $result = array();

    if ($this->validate()) {
      $item = false;

      if (!empty($this->category_id)) {
        $item = Categories::findOne(['category_id' => $this->category_id]);
      } else {
        $item = new Categories();
        $check = Categories::find()->where(['name' => $this->name])->count();

        if ($check) {
          $result['error'] = 'Ошибка сохранения: подобная категория уже существует';
          return $result;
        }
      }

      $search = '';
      $search_url = '';

      foreach ($this->synonym as $str) {
        if (trim($str)) {
          $search .= $str . ';';
          $search_url .= Categories::createUrlFromCyrillic($str);
        }
      }

      $search = mb_substr($search, 0, -1);
      $search_url = substr($search_url, 0, -1);

      $connected = '';
      $url_connected = '';

      foreach ($this->connected_category as $str) {
        if (trim($str)) {
          $connected .= $str . ';';
          $url_connected .= Categories::createUrlFromCyrillic($str);
        }
      }

      $connected = mb_substr($connected, 0, -1);
      $url_connected = substr($url_connected, 0, -1);

      if ($item) {
        if ($item->url) {
          Yii::$app->db
            ->createCommand('DELETE FROM url_params WHERE url="' . $item->url . '" AND name="category"')
            ->execute();
        }

        $item->synonym = $search;
        $item->synonym_url = $search_url;
        $item->connected_category = $connected;
        $item->connected_category_url = $url_connected;

        if ($item->load(Yii::$app->request->post(), '')) {
          $url = Categories::createUrlFromCyrillic($item->name);
          $item->url = $url;

          if ($item->save()) {
            $result['validated'] = true;
            return $result;
          } else {
            $result['error'] = 'Ошибка сохранения: тип запчасти не сохранен';
          }
        } else {
          $result['error'] = 'Ошибка сохранения: неверные данные';
        }
      } else {
        $result['error'] = 'Ошибка сохранения: Тип запчасти отсутствует в базе данных';
      }
    }

    $result['validated'] = false;
    return $result;
  }
}
