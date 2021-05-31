<?php

namespace app\modules\controlpanel\models\forms\site;

use app\models\helpers\PageUtils;
use yii\base\Model;

use app\models\site\Pages;
use \app\models\site\URLs;
use Yii;

class PagesForm extends Model
{
  public $id;
  public $name;
  public $title;
  public $meta_keywords;
  public $meta_title;
  public $meta_description;
  public $meta_robots;
  public $meta_author;
  public $content;
  public $url;
  public $real_url;
  public $product_group_id;
  public $informational;
  public $relative;

  public function rules()
  {
    return [
      [['url'], 'required', 'message' => Yii::t('app', 'required_field')],
      [['name', 'title', 'url'], 'string', 'max' => 200],
      [['meta_robots'], 'string', 'max' => 100],
      [['real_url'], 'string', 'max' => 255],
      [['meta_keywords', 'meta_title', 'meta_description', 'meta_author', 'content'], 'string'],
      [['informational', 'relative'], 'boolean'],
      [['id', 'product_group_id'], 'integer'],
    ];
  }

  public function saveData()
  {
    $result = array();

    if ($this->validate()) {
      $item = false;

      $this->url = PageUtils::getPageUrl($this->url);

      if (!empty($this->id)) {
        $item = Pages::findOne(['id' => $this->id]);
      } else {
        $check = Pages::find()->where(['url' => $this->url])->count();

        if ($check) {
          $result['error'] = 'Ошибка сохранения: страница с таким URL уже существует';
          return $result;
        }

        $item = new Pages();
      }

      $old_url = $item->url;

      if ($item) {
        if ($item->load($this->attributes, '') && $item->save(false)) {
          $result['validated'] = true;
          $addr = substr($old_url ? $old_url : $this->url, 1);

          $url = URLs::find()
            ->where('url="' . $addr . '"')
            ->one();

          if (!$url) {
            $url = new URLs();
            $url->action = 'site/page';
          }

          $url->url = substr($item->url, 1);
          $url->save();

          return $result;
        } else {
          $result['error'] = 'Ошибка сохранения: страница не сохранена';
        }
      } else {
        $result['error'] = 'Ошибка сохранения: Страница отсутствует в базе данных';
      }
    }

    $result['validated'] = false;
    return $result;
  }

  public function beforeValidate()
  {
    if (parent::beforeValidate()) {
      $this->content = htmlentities($this->content);
      if (empty($this->real_url)) $this->real_url = 'page';
      return true;
    } else return false;
  }
}
