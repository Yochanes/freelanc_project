<?php

namespace app\modules\controlpanel\models\forms\catalogs;

use app\models\catalogs\Catalog_Links;
use app\models\catalogs\Catalog_Params;
use app\models\catalogs\Catalogs;
use app\models\helpers\PageUtils;
use Yii;
use yii\base\Model;

use app\models\site\Pages;

class CatalogsForm extends Model
{
  public $id;
  public $product_group_id;
  public $param;
  public $param_title;
  public $title;
  public $url;
  public $subtitle;
  public $catalog_link_text;
  public $catalog_link_href;

  public function rules()
  {
    return [
      [['title', 'url', 'product_group_id'], 'required', 'message' => 'Эти поля должны быть обязательно заполнены'],
      [['param'], 'each', 'rule' => ['integer']],
      [['param_title', 'catalog_link_text', 'catalog_link_href'], 'each', 'rule' => ['string']],
      [['title', 'subtitle', 'url'], 'string', 'max' => 255, 'tooLong' => 'Длина этого поля не может превышать 255 символов'],
      [['id', 'product_group_id'], 'integer']
    ];
  }

  public function saveData()
  {
    $result = array();

    if ($this->validate()) {
      $item = false;

      if (!empty($this->id)) {
        $item = Catalogs::findOne(['id' => $this->id]);
      } else {
        $item = new Catalogs();

        $check = Catalogs::find()
          ->where(['url' => $this->url])
          ->count();

        if ($check) {
          $result['error'] = 'Ошибка сохранения: каталог с таким URL уже существует';
          return $result;
        }
      }

      if ($item) {
        $old_url = $item->url;

        if ($item->load(Yii::$app->request->post(), '')) {
          $item->url = PageUtils::getPageUrl($this->url);

          if ($item->id) {
            Yii::$app->db
              ->createCommand('DELETE FROM ' . Catalog_Params::tableName() . ' WHERE catalog_id=' . $item->id)
              ->execute();

            Yii::$app->db
              ->createCommand('DELETE FROM ' . Catalog_Links::tableName() . ' WHERE catalog_id=' . $item->id)
              ->execute();
          }

          if (!$item->save()) {
            $result['error'] = 'Ошибка сохранения: каталог не сохранен';
            $result['errors'] = array_merge($this->errors, $item->errors);
          } else {
            foreach ($this->param as $key => $param) {
              if (isset($this->param_title[$key]) && trim($this->param_title[$key])) {
                $cp = new Catalog_Params();
                $cp->catalog_id = $item->id;
                $cp->param_title = $this->param_title[$key];
                $cp->sort_order = $key;
                $cp->param_type = $param;
                $cp->save();
              }
            }

            foreach ($this->catalog_link_text as $key => $txt) {
              if (isset($this->catalog_link_href[$key]) && trim($this->catalog_link_href[$key])) {
                $cp = new Catalog_Links();
                $cp->catalog_id = $item->id;
                $cp->href = $this->catalog_link_href[$key];
                $cp->text = $txt;
                $cp->save();
              }
            }

            if ($old_url) {
              $page = Pages::find()
                ->where([
                  'url' => '/katalog' . $old_url,
                  'type' => Pages::PAGE_TYPE_CATALOG
                ])
                ->one();

              if (!$page) {
                $page = new Pages();
              }
            } else {
              $page = new Pages();
            }

            if (!$page->name) {
              $page->name = $item->title;
            }

            $page->type = Pages::PAGE_TYPE_CATALOG;
            $page->url = '/katalog' . $item->url;
            $page->real_url = 'catalog/index';
            $page->save();

            $result['success'] = true;
            $result['validated'] = true;
            return $result;
          }
        } else {
          $result['error'] = 'Ошибка сохранения: Неверные данные каталога';
        }
      } else {
        $result['error'] = 'Ошибка сохранения: Каталог отсутствует в базе данных';
      }
    }

    $result['validated'] = false;
    return $result;
  }
}
