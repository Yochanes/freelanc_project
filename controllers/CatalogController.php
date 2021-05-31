<?php

namespace app\controllers;

use app\models\catalogs\Catalog_Params;
use app\models\catalogs\Catalogs;
use app\models\helpers\Lists;
use app\models\makes\MakeGroups;
use app\models\products\CategoryAttributeGroups;
use app\models\site\Pages;
use Yii;
use app\models\products\CatalogCategories;
use app\models\helpers\PageUtils;
use yii\web\Controller;


class CatalogController extends Controller
{

  public function actionIndex($url)
  {

    $data = Catalogs::find()
      ->where([
        'url' => PageUtils::getPageUrl($url)
      ])
      ->with(['productGroup', 'paramsArray', 'linksArray'])
      ->one();

    if (!$data) {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }

    $attr_where = '';

    foreach ($data->paramsArray as $param) {
      if ($param->attributeId) {
        $attr_where .= $param->attributeId . ',';
      }
    }

    $page = Pages::find()
      ->where([
        'url' => '/katalog' . PageUtils::getPageUrl($url),
        'type' => Pages::PAGE_TYPE_CATALOG
      ])
      ->one();

    PageUtils::registerPageData($page);

    return $this->render('index', [
      'data' => $data,

      'attributes' => $attr_where ? CategoryAttributeGroups::find()
        ->with('attributesArray')
        ->where('attribute_group_id IN (' . substr($attr_where, 0, -1) . ')')
        ->all() : []
    ]);
  }

  public function actionJson()
  {
    $param = Yii::$app->request->post('param');
    $val = Yii::$app->request->post('val');
    $next_param = Yii::$app->request->post('next_param');

    $result = [
      'options' => ''
    ];

    if ($next_param) {
      if ($next_param == 'category') {
        if (!is_numeric($val)) {
          $val = CatalogCategories::find()
            ->select('id')
            ->where(['url' => $val])
            ->one();

          if ($val) $val = $val->id;
        }

        $result['options'] = Lists::getOptionCategoryListBySection(false, $val)['options_category'];
      } else if ($next_param == 'catalog_subcategory') {
        if (!is_numeric($val)) {
          $val = CatalogCategories::find()
            ->select('id')
            ->where(['url' => $val])
            ->one();

          if ($val) $val = $val->id;
        }

        $result['options'] = Lists::getOptionCategorySubgroupList(false, $val)['options_category_subgroup'];
      } else if ($next_param == 'catalog_category') {
        $result['options'] = Lists::getOptionCategoryGroupList()['options_category_group'];
      } else if ($next_param == 'make') {
        if ($param == 'make_group' && !is_numeric($val)) {
          $val = MakeGroups::fing()
            ->where(['url' => $val])
            ->one();

          if ($val) $val = $val->id;
        }

        if ($val && $param == 'make_group') {
          $result['options'] = Lists::getOptionMakeList('make_groups.make_group_id=' . $val)['options_make'];
        } else {
          $result['options'] = Lists::getOptionMakeList()['options_make'];
        }
      } else if ($next_param == 'model' && $val) {
        $result['options'] = Lists::getOptionModelList($val)['options_model'];
      } else if ($next_param == 'generation' && $val) {
        $result['options'] = Lists::getOptionGenerationlList($val)['options_generation'];
      }
    }

    return json_encode($result);
  }

  public function beforeAction($action)
  {
    $this->enableCsrfValidation = true;
    PageUtils::getMenus();

    $this->view->params['page_name'] = '';
    $this->view->params['page_content'] = '';

    $host = explode('.', Yii::$app->request->hostName);

    if (sizeof($host) >= 2) {
      Yii::$app->view->params['site_city'] = \app\models\Cities::find()
        ->where(['domain' => $host[0]])
        ->one();
    }

    return parent::beforeAction($action);
  }
}
