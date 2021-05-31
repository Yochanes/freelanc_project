<?php

namespace app\modules\controlpanel\controllers;

use app\models\helpers\JSONData;
use app\models\makes\Generations;
use app\models\makes\MakeGroups;
use app\models\makes\Makes;
use app\models\makes\Models;
use app\models\products\CatalogCategories;
use app\models\products\Categories;
use app\models\products\CategoryAttributeGroups;
use app\modules\controlpanel\models\forms\config\ScheduleForm;
use Yii;
use yii\web\Controller;

use app\models\User;
use app\models\helpers\Helpers;
use app\models\Products;
use app\models\Requests;

use app\modules\controlpanel\models\forms\LockForm;

use app\modules\controlpanel\models\forms\site\PagesForm;
use app\modules\controlpanel\models\forms\site\MenusForm;
use app\modules\controlpanel\models\forms\site\MenuItemsForm;

use app\modules\controlpanel\models\forms\config\SMTPForm;

use app\models\config\SMTP;

class DataController extends Controller
{

  public function actions()
  {
    return [
      'error' => [
        'class' => 'yii\web\ErrorAction',
      ],
    ];
  }

  public function beforeAction($action)
  {
    if (Yii::$app->user->isGuest) {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }

    if (!Yii::$app->user->identity->isAdmin()) {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }

    if (Yii::$app->request->isPost) {
      Yii::$app->cache->flush();
    }

    return parent::beforeAction($action);
  }

  public function actionBuildcattree()
  {
    if (\app\modules\controlpanel\models\helpers\Data::buildCategoryTree()) {
      Yii::$app->cache->flush();
      return 'ok';
    } else {
      return 'error';
    }
  }

  public function actionCreateletter()
  {
    if (Yii::$app->request->isPost) {
      $id = Yii::$app->request->post('id');

      if (!$id) {
        throw new \yii\web\HttpException(404, 'Страница не найдена');
      }

      $config = \app\models\config\SMTP::find()->where(['id' => $id])->one();

      if (!$config) {
        throw new \yii\web\HttpException(404, 'Страница не найдена');
      }

      $to = Yii::$app->request->post('to');
      $subject = Yii::$app->request->post('subject');
      $text = Yii::$app->request->post('text');
      $json = array();

      if (!$to) {
        $json['error'] = 'Ошибка выполнения запроса';
        $json['errors'] = ['to' => 'Это поле должно быть заполнено'];
      } else {
        $config->sendEmail($to, $subject, $text);
        $json['success'] = true;
      }

      return json_encode($json);
    } else {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }
  }

  public function actionRemoveduplicates()
  {
    $duplicate_gens = Yii::$app->db
      ->createCommand('SELECT name, COUNT(name) FROM make_generations GROUP BY name HAVING COUNT(name) > 1')
      ->queryAll();

    $count = 0;

    foreach ($duplicate_gens as $gen) {
      $duplicates = Yii::$app->db
        ->createCommand('SELECT * FROM make_generations WHERE name="' . $gen['name'] . '"')
        ->queryAll();

      $unique = [];

      foreach ($duplicates as $dup) {
        $key = $dup['make_id'] . '_' . $dup['model_id'];

        if (!isset($unique[$key])) {
          $unique[$key] = $dup;
        } else {
          Yii::$app->db
            ->createCommand('DELETE FROM make_generations WHERE id=' . $dup['id'])
            ->execute();

          $count++;
        }
      }
    }

    return 'Найдено повторяющихся значений ' . $count;
  }

  public function actionPublishproduct($url)
  {
    return $this->processPublishAction(Products::class, $url);
  }

  public function actionDeletecity()
  {
    return $this->processDeleteAction(\app\models\Cities::class);
  }

  public function actionDeletecatalog()
  {
    return $this->processDeleteAction(\app\models\catalogs\Catalogs::class);
  }

  public function actionDeleteuploadcolumn()
  {
    return $this->processDeleteAction(\app\models\site\UploadColumns::class);
  }

  public function actionDeletecountry()
  {
    return $this->processDeleteAction(\app\models\Countries::class);
  }

  public function actionDeletecomplaint()
  {
    return $this->processDeleteAction(\app\models\user\Complaints::class);
  }

  public function actionDeletedialog()
  {
    return $this->processDeleteAction(\app\models\user\NotificationDialogs::class);
  }

  public function actionDeletemake()
  {
    JSONData::updateMakesVersion();
    return $this->processDeleteAction(\app\models\makes\Makes::class);
  }

  public function actionDeleteproductattribute()
  {
    return $this->processDeleteAction(\app\models\products\CategoryAttributes::class, 'attribute_id');
  }

  public function actionDeleteattributegroup()
  {
    return $this->processDeleteAction(\app\models\products\CategoryAttributeGroups::class, 'attribute_group_id');
  }

  public function actionDeletemakegroup()
  {
    Yii::$app->db
      ->createCommand('DELETE FROM make_to_group WHERE make_group_id=' . Yii::$app->request->post('id'))
      ->execute();

    return $this->processDeleteAction(\app\models\makes\MakeGroups::class, 'make_group_id');
  }

  public function actionDeleteproductgroup()
  {
    return $this->processDeleteAction(\app\models\products\ProductGroups::class, 'product_group_id');
  }

  public function actionDeletecategory()
  {
    JSONData::updateCategoriesVersion();
    return $this->processDeleteAction(\app\models\products\Categories::class, 'category_id');
  }

  public function actionDeletepage()
  {
    return $this->processDeleteAction(\app\models\site\Pages::class);
  }

  public function actionDeleterobots()
  {
    return $this->processDeleteAction(\app\models\site\Robots::class);
  }

  public function actionDeletesupportcategory()
  {
    return $this->processDeleteAction(\app\models\site\SupportCategory::class);
  }

  public function actionDeletesupportcategotryitem()
  {
    return $this->processDeleteAction(\app\models\site\SupportCategoryItem::class);
  }

  public function actionDeletemenu()
  {
    return $this->processDeleteAction(\app\models\site\Menus::class);
  }

  public function actionDeletemenuitem()
  {
    return $this->processDeleteAction(\app\models\site\MenuItems::class);
  }

  public function actionDeletesmtp()
  {
    return $this->processDeleteAction(\app\models\config\SMTP::class);
  }

  public function actionGeneraterobots()
  {
    if (Yii::$app->request->isPost) {
      $countries = \app\models\Countries::find()->with('cities')->all();

      foreach ($countries as $country) {
        foreach ($country->cities as $city) {
          $url = ($city->domain ? $city->domain . '.' : '') . $country->domain;
          $count = \app\models\site\Robots::find()->where(['url' => $url])->count();

          if (!$count) {
            $item = new \app\models\site\Robots();
            $item->url = $url;
            $item->default_flag = 0;
            $item->content = Yii::$app->request->post('content');
            $item->save();
          } else {
            Yii::$app->db
              ->createCommand('UPDATE config_robots 
                SET content="' . Yii::$app->request->post('content') . '"
                WHERE url="' . $url . '"')
              ->execute();
          }
        }
      }

      return json_encode(['success' => true]);
    } else {
      return json_encode(['error' => 'Операция недоступна']);
    }
  }

  public function actionGetmakes($url)
  {
    $make_group = MakeGroups::find()
      ->where(['make_group_id' => $url])
      ->with('makes')
      ->one();

    $makes = [];

    if ($make_group) {
      foreach ($make_group->makes as $make) {
        $makes[] = $make->attributes;
      }
    }

    return json_encode($makes);
  }

  public function actionGetmodels($url)
  {
    $models = \app\models\makes\Models::find()
      ->where('make_id="' . $url . '"')
      ->orderBy('name')
      ->asArray()
      ->all();

    return json_encode($models);
  }

  public function actionGetgenerations($url)
  {
    $gens = \app\models\makes\Generations::find()
      ->where('model_id="' . $url . '"')
      ->orderBy('name')
      ->asArray()
      ->all();

    return json_encode($gens);
  }

  public function actionLockuser()
  {
    $model = new LockForm();
    $model->load(Yii::$app->request->post(), '');
    $result = $model->saveData();
    $result['error'] = isset($result['error']) ? $result['error'] : false;
    $result['errors'] = isset($result['errors']) ? $result['errors'] : $model->errors;

    return json_encode($result);
  }

  public function actionRejectproduct()
  {
    return $this->processRejectAction(Products::class, Products::STATE_REJECTED);
  }

  public function actionLockrequest()
  {
    return $this->processRejectAction(Requests::class, Products::STATE_LOCKED);
  }

  public function actionLockproduct()
  {
    return $this->processRejectAction(Products::class, Products::STATE_LOCKED);
  }

  public function actionSyncgtdcities()
  {
    $ccode = trim(strtolower(Yii::$app->request->post('code')));

    $country = \app\models\Countries::find()
      ->where('LOWER(code)="' . $ccode . '"')
      ->one();

    if (!$country) {
      return json_encode(['error' => 'Страна с выбранным кодом недоступна']);
    }

    $cities = \app\models\helpers\GTD_API::getCities($ccode);
    $results = 0;

    if ($cities && (!isset($cities['error']) || !$cities['error'])) {
      foreach ($cities as $city) {
        if (substr($city['code'], -strlen('00000')) !== '00000') continue;

        $arr = array(
          'code' => $city['code'],
          'required_pickup' => $city['required_pickup'],
          'required_delivery' => $city['required_delivery'],
          'region_code' => $city['region_code']
        );

        $city_names = explode(' ', $city['name']);

        $exists = Yii::$app->db
          ->createCommand('SELECT COUNT(*) FROM cities 
            WHERE country_id=' . $country->id . ' AND name="' . $city_names[0] . '"' . (count($city_names) >= 2 ? ' OR name="' . $city_names[1] . '"' : ''))
          ->queryScalar();

        if (!$exists) {
          $result = Yii::$app->db
            ->createCommand(
              'INSERT INTO cities (country_id,name,url,code,required_pickup,required_delivery,region_code) 
              VALUES (
                ' . $country->id . ',      
                "' . $city_names[0] . '",
                "gorod_' . Helpers::translater(mb_strtolower(trim($city_names[0])), 'ru', null, true) . '",
                "' . $city['code'] . '",
                ' . $city['required_pickup'] . ',
                ' . $city['required_delivery'] . ',
                "' . $city['region_code'] . '"
              )
          ')
            ->execute();
        } else {
          Yii::$app->db
            ->createCommand('UPDATE cities SET 
              code="' . $city['code'] . '",
              required_pickup=' . $city['required_pickup'] . ',
              required_delivery=' . $city['required_delivery'] . ',
              region_code="' . $city['region_code'] . '" 
              WHERE country_id=' . $country->id . ' AND name="' . $city_names[0] . '"' . (count($city_names) >= 2 ? ' OR name="' . $city_names[1] . '"' : ''))
            ->execute();
        }

        $results++;
      }

      if ($results) {
        return json_encode(array(
          'success' => true,
          'validated' => true,
        ));
      } else {
        return json_encode(array(
          'error' => 'Данные не синхронизированы: ответ от GTD не содержит данных'
        ));
      }
    } else {
      return json_encode(array(
        'error' => 'Данные не синхронизированы: ' . $cities['error']
      ));
    }
  }

  public function actionUnlockuser()
  {
    $result = array();
    $id = Yii::$app->request->post('user_id');

    if (!$id) {
      $result['error'] = 'Ошибка: отсутствует ID пользователя';
    } else {
      $user = User::find()->where(['id' => $id])->one();

      if (!$user) {
        $result['error'] = 'Ошибка: пользователь не найден';
      } else {
        $user->state = User::STATE_UNLOCKED;
        $user->reason = '';

        if ($user->save(false)) {
          $result['success'] = true;
          $result['validated'] = true;
        } else {
          $result['error'] = 'Ошибка: статус пользователя не сохранен';
        }
      }
    }

    return json_encode($result);
  }

  public function actionUnlockrequest()
  {
    return $this->processUnlockAction(Requests::class);
  }

  public function actionUnlockproduct()
  {
    return $this->processUnlockAction(Products::class);
  }

  public function actionUnlockcar()
  {
    return $this->processUnlockAction(Cars::class);
  }

  public function actionUpdatecategory()
  {
    JSONData::updateCategoriesVersion();
    return $this->processCreateUpdateAction(new \app\modules\controlpanel\models\forms\products\CategoriesForm());
  }

  public function actionUpdatecatalog()
  {
    return $this->processCreateUpdateAction(new \app\modules\controlpanel\models\forms\catalogs\CatalogsForm());
  }

  public function actionUploadcolumn()
  {
    return $this->processCreateUpdateAction(new \app\modules\controlpanel\models\forms\products\UploadColumnsForm());
  }

  public function actionUploadattributes()
  {
    return $this->processCreateUpdateAction(new \app\modules\controlpanel\models\forms\products\UploadAttributesForm());
  }

  public function actionUpdatesupportcategory()
  {
    return $this->processCreateUpdateAction(new \app\modules\controlpanel\models\forms\site\SupportForm());
  }

  public function actionUpdatesupportcategoryitem()
  {
    return $this->processCreateUpdateAction(new \app\modules\controlpanel\models\forms\site\SupportItemForm());
  }

  public function actionUpdateurlparams()
  {

    Yii::$app->db
      ->createCommand('DELETE FROM url_params')
      ->execute();

    $params_to_insert_names = [];
    $params_to_insert = [];

    $make_groups = MakeGroups::find()->all();

    foreach ($make_groups as $item) {
      $params_to_insert['make_group' . $item->make_group_id] = [
        'name' => 'make_group',
        'title' => $item->name,
        'url' => $item->url,
        'connected_id' => $item->make_group_id
      ];
    }

    $makes = Makes::find()->all();

    foreach ($makes as $item) {
      $params_to_insert['make' . $item->id] = [
        'name' => 'make',
        'title' => $item->name,
        'url' => $item->url,
        'connected_id' => $item->id
      ];
    }

    $models = Models::find()->all();

    foreach ($models as $item) {
      $params_to_insert['model' . $item->id] = [
        'name' => 'model',
        'title' => $item->name,
        'url' => $item->url,
        'connected_id' => $item->id
      ];
    }

    $generations = Generations::find()->all();

    foreach ($generations as $item) {
      $params_to_insert['generation' . $item->id] = [
        'name' => 'generation',
        'title' => $item->name,
        'url' => $item->url,
        'connected_id' => $item->id
      ];
    }

    $categories = CatalogCategories::find()->all();

    foreach ($categories as $item) {
      if (!$item->parent_id) {
        $params_to_insert['category_group' . $item->id] = [
          'name' => 'category_group',
          'title' => $item->name,
          'url' => $item->url,
          'connected_id' => $item->id
        ];
      } else {
        $params_to_insert['category_subgroup' . $item->id] = [
          'name' => 'category_subgroup',
          'title' => $item->name,
          'url' => $item->url,
          'connected_id' => $item->id
        ];
      }
    }

    $categories = Categories::find()->all();

    foreach ($categories as $item) {
      $params_to_insert['category' . $item->category_id] = [
        'name' => 'category',
        'title' => $item->name,
        'url' => $item->url,
        'connected_id' => $item->category_id
      ];
    }

    $attributes = CategoryAttributeGroups::find()
      ->with('attributesArray')
      ->all();

    foreach ($attributes as $attr) {
      foreach ($attr->attributesArray as $item) {
        $params_to_insert['attribute' . $item->attribute_id] = [
          'name' => 'attribute_' . $item->attribute_group_id,
          'title' => $attr->name . ' ' . $item->value,
          'url' => $item->url,
          'connected_id' => $item->attribute_id
        ];
      }
    }

    if (!empty($params_to_insert)) {
      Yii::$app->db->createCommand()
        ->batchInsert('url_params', ['name', 'title', 'url', 'connected_id'], $params_to_insert)
        ->execute();
    }

    return json_encode(['success' => true]);
  }

  public function actionUpdatecity()
  {
    return $this->processCreateUpdateAction(new \app\modules\controlpanel\models\forms\site\CitiesForm());
  }

  public function actionUpdatecountry()
  {
    return $this->processCreateUpdateAction(new \app\modules\controlpanel\models\forms\site\CountriesForm());
  }

  public function actionUpdatemake()
  {
    JSONData::updateMakesVersion();
    return $this->processCreateUpdateAction(new \app\modules\controlpanel\models\forms\makes\MakesForm());
  }

  public function actionUpdateproductattribute()
  {
    return $this->processCreateUpdateAction(new \app\modules\controlpanel\models\forms\products\AttributesForm());
  }

  public function actionUpdateattributegroup()
  {
    return $this->processCreateUpdateAction(new \app\modules\controlpanel\models\forms\products\AttributeGroupsForm());
  }

  public function actionUpdatemakegroup()
  {
    return $this->processCreateUpdateAction(new \app\modules\controlpanel\models\forms\makes\MakeGroupsForm());
  }

  public function actionUpdateproductgroup()
  {
    return $this->processCreateUpdateAction(new \app\modules\controlpanel\models\forms\products\ProductGroupsForm());
  }

  public function actionUpdateproductgrouptemp()
  {
    return $this->processCreateUpdateAction(new \app\modules\controlpanel\models\forms\products\ProductGroupsPageForm());
  }

  public function actionUpdatemodel()
  {
    JSONData::updateMakesVersion();
    return $this->processCreateUpdateAction(new \app\modules\controlpanel\models\forms\makes\ModelsForm());
  }

  public function actionUpdategeneration()
  {
    JSONData::updateMakesVersion();
    return $this->processCreateUpdateAction(new \app\modules\controlpanel\models\forms\makes\GenerationsForm());
  }

  public function actionUpdatemenu()
  {
    $model = new MenusForm();
    return $this->processCreateUpdateAction($model);
  }

  public function actionUpdatemenuitem()
  {
    $model = new MenuItemsForm();
    return $this->processCreateUpdateAction($model);
  }

  public function actionUpdatepage()
  {
    $model = new PagesForm();
    return $this->processCreateUpdateAction($model);
  }

  public function actionUpdaterobots()
  {
    return $this->processCreateUpdateAction(new \app\modules\controlpanel\models\forms\site\RobotsForm());
  }

  public function actionUpdatesmtp()
  {
    $model = new SMTPForm();
    return $this->processCreateUpdateAction($model);
  }

  public function actionUpdateschedule()
  {
    $model = new ScheduleForm();
    return $this->processCreateUpdateAction($model);
  }

  public function actionUploadmakes()
  {
    JSONData::updateMakesVersion();
    return $this->processUploadAction(new \app\modules\controlpanel\models\forms\makes\MakesUploadForm());
  }

  public function actionUploadcategories()
  {
    JSONData::updateCategoriesVersion();
    return $this->processUploadAction(new \app\modules\controlpanel\models\forms\products\CategoriesUploadForm());
  }

  public function actionUploadmakeswithgroups()
  {
    JSONData::updateMakesVersion();
    return $this->processUploadAction(new \app\modules\controlpanel\models\forms\makes\MakesWithGroupsUploadForm());
  }

  protected function processPublishAction($class, $id)
  {
    $className = explode('\\', get_class(new $class()));
    $className = end($className);

    if (empty($id)) {
      return $this->redirect(Yii::$app->request->referrer ?: '/controlpanel/dashboard/' . strtolower($className));
    }

    Yii::$app->db
      ->createCommand('UPDATE ' . strtolower($className) . ' SET status = 0 WHERE id = ' . $id)
      ->execute();

    return $this->redirect(Yii::$app->request->referrer ?: '/controlpanel/dashboard/' . strtolower($className));
  }

  protected function processCreateUpdateAction($model)
  {
    if (Yii::$app->request->isPost) {
      $json = array();

      if ($model->load(Yii::$app->request->post(), '')) {
        $result = $model->saveData();
        foreach ($result as $key => $val) $json[$key] = $val;

        if (!isset($result['validated']) || !$result['validated']) {
          $json['error'] = empty($result['error']) ? 'Не все поля заполнены верно' : $result['error'];
          $json['errors'] = $model->errors;
        } else {
          $json['errors'] = $model->errors;
          $json['success'] = true;
        }
      } else {
        $json['error'] = 'Ошибка выполнения запроса';
      }

      return json_encode($json);
    } else {
      return json_encode(['error' => 'Операция недоступна']);
    }
  }

  protected function processDeleteAction($model, $field = 'id', $produce_json = true)
  {
    if (Yii::$app->request->isPost) {
      $id = Yii::$app->request->post('id');

      if (empty($id)) {
        return $produce_json ?
          json_encode(['error' => 'Ошибка удаления: неверные данные запроса']) :
          ['error' => 'Ошибка удаления: неверные данные запроса'];
      }

      $json = array();
      $item = $model::find()->where([$field => $id])->one();

      if ($item && $item->delete() > 0) {
        $json['success'] = true;

        if (isset($item->url) && !isset($item->real_url)) {
          Yii::$app->db
            ->createCommand('DELETE FROM pages WHERE url LIKE "%' . $item->url . '%"')
            ->execute();
        }
      } else {
        $json['error'] = 'Ошибка выполнения запроса';
      }

      return $produce_json ? json_encode($json) : $json;
    } else {
      return $produce_json ? json_encode(['error' => 'Операция недоступна']) : ['error' => 'Операция недоступна'];
    }
  }

  protected function processRejectAction($model, $state)
  {
    if (Yii::$app->request->isPost) {
      $id = Yii::$app->request->post('id');

      if (empty($id)) {
        return json_encode(['error' => 'Ошибка запроса: неверные данные запроса']);
      }

      $json = array();

      $item = $model::find()
        ->select('id, user_id, category, model, make, year')
        ->where('id = ' . $id)
        ->one();

      if ($item) {
        $item->status = $state;
        $item->reason = Yii::$app->request->post('reason');

        if ($item->save(false)) {
          $json['success'] = true;

          $notify = Yii::$app->db
            ->createCommand('SELECT on_status_change FROM user_configs WHERE user_id=' . $item->user_id . ' LIMIT 1')
            ->queryAll();

          if ($notify && $notify[0]) {
            $cfg = SMTP::find()
              ->where('active=1')
              ->one();

            try {
              if ($cfg) {
                $username = Yii::$app->db->createCommand('SELECT username FROM users WHERE id=' . $item->user_id . ' LIMIT 1')->queryAll();

                if ($username) {
                  $to = $username[0]['username'];
                  $subject = 'Ваше объявление' . ($state == Products::STATE_REJECTED ? ' отклонено' : ' заблокировано');
                  $text = '<p>Ваше объявление' . ($state == Products::STATE_REJECTED ? ' отклонено' : ' заблокировано') . '</p>
										<p>Объявление <a href="' . $item->url . '">' . $item->name . '</a>' . ($state == Products::STATE_REJECTED ? ' отклонено' : ' заблокировано') . ' по причине:</p>
										<p>' . $item->reason ?: '[Причина не указана]' . '</p>';

                  $cfg->sendEmail($to, $subject, '', $text);
                }
              }
            } catch (\Exception $e) {
            }
          }
        } else {
          $json['error'] = 'Ошибка запроса: Товар не найден';
        }
      } else {
        $json['error'] = 'Ошибка выполнения запроса';
      }

      return json_encode($json);
    } else {
      return json_encode(['error' => 'Операция недоступна']);
    }
  }

  protected function processUnlockAction($model)
  {
    if (Yii::$app->request->isPost) {
      $id = Yii::$app->request->post('id');

      if (empty($id)) {
        return json_encode(['error' => 'Ошибка запроса: неверные данные запроса']);
      }

      $json = array();
      $item = $model::find()->select('id')->where('id = ' . $id)->one();

      if ($item) {
        $item->status = Products::STATE_INACTIVE;
        $item->reason = '';

        if ($item->save(false)) {
          $json['success'] = true;
        } else {
          $json['error'] = 'Ошибка запроса: Товар не найден';
        }
      } else {
        $json['error'] = 'Ошибка выполнения запроса';
      }

      return json_encode($json);
    } else {
      return json_encode(['error' => 'Операция недоступна']);
    }
  }

  protected function processUploadAction($model)
  {
    if (Yii::$app->request->isPost) {
      $json = array();

      if ($model->load(Yii::$app->request->post(), '')) {
        $result = $model->saveData();
        foreach ($result as $key => $val) $json[$key] = $val;

        if (!isset($result['validated']) || !$result['validated']) {
          $json['error'] = empty($result['error']) ? 'отсутствуют необходимые данные' : $result['error'];
          $json['errors'] = $model->errors;
        } else {
          $json['errors'] = $model->errors;
          $json['success'] = true;
        }
      } else {
        $json['error'] = 'Ошибка выполнения запроса';
      }

      return json_encode($json);
    } else {
      return json_encode(['error' => 'Операция недоступна']);
    }
  }
}
