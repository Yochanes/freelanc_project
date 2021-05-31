<?php

namespace app\components;

use app\models\site\SupportCategory;
use app\models\site\SupportCategoryItem;
use yii\base\BaseObject;
use yii\helpers\ArrayHelper;

/**
 * Class SupportComponent
 * @package app\components
 */
class SupportComponent extends BaseObject
{
  public $service_name = 'Вопросы и ответы';

  /**
   * Формирование хлебных крошек
   *
   * @param integer $id
   *
   * @return array
   */
  public function getBreadcrumbs($id, $last_link = false)
  {
    $categories = ArrayHelper::index(SupportCategory::find()->select(['id', 'parent_id', 'title'])->asArray()->all(), 'id');
    $breadcrumbs[] = $id ? ['label' => $this->service_name, 'url' => ['site/support', 'id' => 0]] : ['label' => $this->service_name];
    return array_merge($breadcrumbs, array_reverse( $this->getBreadcrumbsItems($categories, $id, $last_link)));
  }

  /**
   * Формирование массива категорий для хлебных крошек
   *
   * @param array $categories
   * @param integer $current_id
   * @param bool $link
   *
   * @return array
   */
  private function getBreadcrumbsItems($categories, $current_id, $last_link)
  {
    $items = [];
    foreach ($categories as $row) :
      if($row['id'] == $current_id) {
        $items[] = $last_link ? ['label' => $row['title'], 'url' => ['site/support', 'id' => $current_id]] : ['label' => $row['title']];
        $items = array_merge($items, $this->getBreadcrumbsItems($categories, $row['parent_id'], true));
      }
    endforeach;
    return $items;
  }

  /**
   * Получаем массив с данными раздела для админки
   * @return array
   */
  public function getSupportData()
  {
    return $this->getFaqTree(
      ArrayHelper::index(SupportCategory::find()->orderBy(['sort_order' => SORT_ASC])->asArray()->all(), 'id'),
      ArrayHelper::index(SupportCategoryItem::find()->orderBy(['sort_order' => SORT_ASC])->asArray()->all(), 'id', 'category_id')
      );
  }

  /**
   * Получаем многомерный массив с разделами и вопросами
   *
   * @param array $categories
   * @param array $questions
   *
   * @return array
   */
  private function getFaqTree($categories, $questions)
  {
    $tree = [];
    foreach ($categories as $key => &$item) {
      if(array_key_exists($item['id'], $questions)) $categories[$item['id']]['questions'] = $questions[$item['id']];
      if (!$item['parent_id']) {
        $tree[$item['id']] = &$item;
      } elseif(!empty($categories[$item['parent_id']])) {
        $categories[$item['parent_id']]['categories'][$item['id']] = &$item;
      }
    }
    return $tree;
  }

  public function getCategories($parent_id)
  {
    return SupportCategory::find()
      ->with(['categoryItems' => function (\yii\db\ActiveQuery $query) {
        $query->select(['id', 'category_id']);
      }])
      ->where(['parent_id' => $parent_id])
      ->orderBy(['sort_order' => SORT_ASC])
      ->asArray()->all();
  }

  public function getQuestions($category_id)
  {
    return SupportCategoryItem::find()
      ->select(['id', 'title'])
      ->where(['category_id' => $category_id])
      ->orderBy(['sort_order' => SORT_ASC])
      ->asArray()->all();
  }

  public function getAnswer($id)
  {
    return SupportCategoryItem::find()
      ->select(['id', 'category_id', 'title', 'text'])
      ->where(['id' => $id])
      ->asArray()->one();
  }

  public static function getParentUrl($data)
  {
    foreach (array_reverse($data) as $item) :
      if(isset($item['url'])) return $item['url'];
    endforeach;
    return false;
  }
}