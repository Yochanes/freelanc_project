<?php

namespace app\models\site;

use app\models\products\ProductGroups;

class Pages extends \yii\db\ActiveRecord
{

  const PAGE_TYPE_CATALOG = 1;
  const PAGE_TYPE_CATEGORY = 2;

  public static function tableName()
  {
    return 'pages';
  }

  public function init()
  {
    parent::init();
    $this->real_url = 'page';
    $this->content = '';
    $this->meta_author = '';
    $this->relative = 1;
    $this->informational = 0;
  }

  public function rules()
  {
    return [
      [['url'], 'required'],
      [['name', 'title', 'url'], 'string', 'max' => 200],
      [['meta_robots'], 'string', 'max' => 100],
      [['real_url'], 'string', 'max' => 255],
      [['meta_keywords', 'meta_title', 'meta_description', 'meta_author', 'content'], 'string'],
      [['informational', 'relative'], 'boolean'],
      [['id'], 'integer'],
    ];
  }

  public function getProductGroup()
  {
    return $this->hasOne(ProductGroups::class, ['url' => 'url']);
  }
}
