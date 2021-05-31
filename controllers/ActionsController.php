<?php

namespace app\controllers;

use app\models\user\ImagesHash;
use Yii;
use app\models\User;
use app\models\Products;
use app\models\Requests;
use app\models\Favourites;
use app\models\products\ProductGroups;
use app\models\user\Statistics;
use app\models\user\ProfileStatistics;
use app\models\user\Dialogs;
use app\models\user\DialogMessages;
use app\models\products\ProductUploads;
use app\models\products\ProductUploadRules;
use app\models\products\ProductUploadRuleValues;

use app\models\helpers\Lists;
use app\models\user\RateAwait;
use app\models\helpers\Helpers;

use app\components\XLSExporter;

use yii\web\HttpException;

use moonland\phpexcel\Excel;

class ActionsController extends \yii\web\Controller
{

  public function actionSetonline()
  {
    if (Yii::$app->user->isGuest) {
      return '';
    }

    $user = Yii::$app->user->identity;

    if ($user->online === 0) {
      $user->online = 1;
      $user->date_online = date('Y-m-d H:i:s');
      $user->save();
    }

    return '';
  }

  public function actionSetoffline()
  {
    if (Yii::$app->user->isGuest) {
      return '';
    }

    $user = Yii::$app->user->identity;

    if ($user->online === 1) {
      $user->online = 0;
      $user->date_online = date('Y-m-d H:i:s');
      $user->save();
    }

    return '';
  }

  public function actionTest()
  {
    $ids = '2395,2396,2397,2398,2399,2400,2401,2402,2403,2404,2405,2406,2407,2408,2408,2409,2410,2411,2412,2413,2414,2415,2416,2417,2418,2419,2420,2421,2422,2423,2424,2425,2426,2427,2428,2429,2430,2431,2432,2433,2434,2435,2436,2437,2438,2439,2440,2441,2442,2443,2444,2445,2446,2447,2448,2449,2450,2451,2452,2453,2454,2455,2456,2457,2458,2459,2460,2461,2462,2463,2464,2465,2466,2467,2468,2469,2470,2471,2472,2473,2474,2475,2476,2477,2478,2479,2480,2481,2482,2483,2484,2485,2486,2487,2488,2489,2490,2491,2492,2493';
    $ids .= ',';
    $ids .= '2494,2495,2496,2497,2498,2499,2500,2501,2502,2503,2504,2505,2506,2507,2508,2509,2510,2511,2512,2513,2514,2515,2516,2517,2518,2519,2520,2521,2522,2523,2524,2525,2526,2527,2528,2529,2530,2531,2465,2532,2533,2534,2534,2535,2536,2537,2538,2539,2527,2540,2541,2542,2543,2544,2545,2546,2547,2527,2548,2549,2550,2551,2552,2553,2554,2555,2522,2556,2557,2558,2559,2560,2561,2562,2563,2564,2565,2566,2567,2568,2569,2570,2571,2572,2573,2574,2575,2576,2577,2578,2579,2580,2581,2582,2583,2584,2585,2586,2587,2588';
    $ids .= ',';
    $ids .= '2589,2590,2591,2592,2593,2594,2594,2595,2596,2597,2598,2599,2600,2601,2602,2603,2604,2605,2606,2607,2608,2609,2610,2611,2612,2613,2614,2615,2616,2617,2618,2619,2620,2619,2621,2622,2623,2624,2625,2626,2627,2628,2629,2630,2631,2632,2633,2634,2635,2635,2636,2636,2637,2637,2638,2639,2640,2641,2642,2643,2644,2645,2646,2647,2648,2649,2650,2651,2652,2653,2654,2655,2656,2657,2658,2659,2660,2661,2662,2663,2664,2665,2666,2667,2668,2669,2670,2671,2672,2673,2674,2675,2676,2677,2678,2679,2680,2681,2682,2683';
    $ids .= ',';
    $ids .= '2684,2685,2686,2687,2688,2689,2690,2691,2692,2693,2691,2694,2695,2696,2697,2698,2698,2699,2700,2701,2702,2702,2703,2704,2704,2705,2706,2707,2708,2709,2710,2711,2712,2713,2714,2715,2716,2717,2718,2719,2720,2721,2722,2723,2724,2725,2726,2727,2728,2729,2730,2731,2732,2733,2734,2735,2736,2737,2738,2739,2740,2741,2742,2743,2744,2745,2746,2747,2748,2451,2749,2627,2750,2751,2752,2753,2754,2755,2756,2757,2758,2759,2760,2761,2762,2763,2764,2765,2766,2767,2768,2769,2769,2770,2771,2772,2772,2773,2774,2775';
    $ids .= ',';
    $ids .= '2776,2777,2778';

    $exp = explode(',', $ids);
    $arr = [];
    echo 'Проверка ' . (count($exp) - 1) . ' ID<br>';

    foreach ($exp as $id) {
      if (!$id) {
        echo 'Пустой ID<br>';
        continue;
      }

      if (in_array($id, $arr)) {
        echo 'Дублирующийся ID ' . $id . '<br>';
      }

      $arr[] = $id;

      $p =Yii::$app->db
        ->createCommand('SELECT id FROM products WHERE id=' . $id)
        ->queryOne();

      if (!$p) {
        echo 'Товар ID ' . $id . ' отсутствует в базе<br>';
      }
    }
  }

  public function actionValidate($url = '')
  {
    if (!$url) {
      return $this->redirect(Yii::$app->homeUrl . '?msg=' . urlencode('Адрес электронной почты не подтвержден'));
    }

    $contacts = \app\models\user\Contacts::find()
      ->where(['hash' => urldecode($url)])
      ->one();

    if ($contacts) {
      if (!$contacts->email_approved) {
        $contacts->email_approved = 1;
        $contacts->save(false);
        return $this->redirect('/personal/profile?msg=' . urlencode('Адрес электронной почты подтвержден'));
      } else {
        return $this->redirect('/personal/profile?msg=' . urlencode('Вы уже подтвердили свой адрес электронной почты'));
      }
    } else {
      return $this->redirect('/personal/profile?msg=' . urlencode('Пользователь не найден'));
    }
  }

  public function actionAddprodtofav($url, $id)
  {
    if (Yii::$app->user->isGuest) {
      return json_encode(array(
        'error' => 'Вы не можете совершать данную операцию'
      ));
    }

    $product = Products::find()
      ->where('id=' . $id . ' AND user_id!=' . Yii::$app->user->identity->id)
      ->count();

    $result = array();

    if ($product) {
      $user = Yii::$app->user->identity;

      $fav = Favourites::find()
        ->where('user_id = ' . $user->id . ' AND product_id = ' . $id . ' AND group_id = "' . $url . '"')
        ->count();

      if (!$fav) {
        $fav = new Favourites();
        $fav->user_id = $user->id;
        $fav->product_id = $id;
        $fav->group_id = $url;
        $fav->save();
      }

      $result['success'] = true;
    } else $result['error'] = 'Товар не добавлен в избранные: товар отсутствует в базе данных';

    return json_encode($result);
  }

  public function actionActivateproduct()
  {
    return $this->activateItem(Products::class, Yii::$app->request->post('id'), false);
  }

  public function actionActivaterequest()
  {
    $result = $this->getItemsForAction(Requests::class, Yii::$app->request->post('id'));
    $products = $result['products'];

    if ($products) {
      foreach ($products as $product) {
        $product->date_updated = date("Y-m-d H:i:s");
        $product->save();
      }

      return json_encode(['success' => true]);
    } else {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }
  }

  public function actionLogin()
  {
    if (Yii::$app->user->isGuest) {
      $model = new \app\models\forms\LoginForm();

      if ($model->load(Yii::$app->request->post(), '') && $model->login()) {
        return json_encode([
          'success' => true,
        ]);
      } else {
        return json_encode([
          'success' => false,
          'error' => $model->errors ? $model->errors : 'Ошибка авторизации'
        ]);
      }
    } else {
      return json_encode([
        'success' => true,
      ]);
    }
  }

  public function actionDelfavourites($url, $id)
  {
    $this->checkAccess($id);

    Yii::$app->db
      ->createCommand('DELETE FROM user_favourites WHERE product_id = ' . $id .
      ' AND user_id = ' . Yii::$app->user->identity->id . ' AND group_id ="' . $url . '"')
      ->execute();

    return 'ok';
  }

  public function actionDelfavouritesarr()
  {
    $ids = Yii::$app->request->post('id');
    $this->checkAccess($ids);
    $str = '';
    foreach ($ids as $id) $str .= ',' . $id;

    if ($str) {
      $str = substr($str, 1);

      Yii::$app->db
        ->createCommand('DELETE FROM user_favourites WHERE product_id IN (' . $str .
          ') AND user_id = ' . Yii::$app->user->identity->id)
        ->execute();
    }

    return json_encode(['success' => true]);
  }

  public function actionDelproduct()
  {
    $result = $this->getItemsForAction(Products::class, Yii::$app->request->post('id'));
    $products = $result['products'];
    $where = $result['where'];

    if ($products) {
      $user = Yii::$app->user->identity;
      $ids = '';
      $non_deletable_ids = '';
      $images = [];
      $last_product_id = Yii::$app->session->get('last_product_id');
      $non_deletable = [];

      $dms = DialogMessages::find()
        ->select('item_id')
        ->where(
          'item_type="product" AND (sender_id=' . $user->id . ' OR receiver_id=' . $user->id . ')'
        )
        ->groupBy('item_id')
        ->all();

      foreach ($dms as $dm) {
        $non_deletable[] = $dm->item_id;
      }

      foreach ($products as $product) {
        if ($last_product_id && $last_product_id == $product->id) {
          Yii::$app->session->remove('last_product_id');
          Yii::$app->session->remove('draft');
        }

        if (in_array($product->id, $non_deletable)) {
          $non_deletable_ids .= $product->id . ',';
        } else {
          $ids .= $product->id . ',';
          $images = array_merge($images, $product->images);
        }
      }

      $where = substr($non_deletable_ids, 0, -1);

      if ($where) {
        Products::updateAll(
          ['status' => Products::STATE_DELETED],
          'id IN (' . $where . ') AND user_id=' . $user->id
        );
      }

      $where = substr($ids, 0, -1);

      if ($where) {
        Products::deleteAll('id IN (' . $where . ') AND user_id=' . $user->id);
      }

      foreach ($images as $image) {
        if (file_exists(Yii::$app->basePath . $image)) {
          unlink(Yii::$app->basePath . $image);
        }

        $dirname = Yii::$app->basePath . '/web/gallery/tmp/products';
        $image_name = explode('.', basename($image));
        $image_name = $image_name[0];

        if (file_exists($dirname)) {
          array_map('unlink', glob($dirname . '/' . $image_name . '*'));
        }

        $dirname = Yii::$app->basePath . '/web/gallery/tmp/images';

        if (file_exists($dirname)) {
          array_map('unlink', glob($dirname . '/' . $image_name . '*'));
        }
      }

      ImagesHash::deleteAll(['user_id' => $user->id]);

      return json_encode(['success' => true]);
    } else {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }
  }

  public function actionDelmycar($url)
  {
    return $this->deleteItem(\app\models\user\Cars::class, $url, '/personal/mycars');
  }

  public function actionDelrequest()
  {
    return $this->deleteItem(Requests::class, Yii::$app->request->post('id'), false);
  }

  public function actionDelfillial($url)
  {
    return $this->deleteItem(\app\models\user\Fillial::class, $url, false);
  }

  public function actionDeactivateproduct()
  {
    return $this->deactivateItem(Products::class, Yii::$app->request->post('id'), false);
  }

  public function actionSavepartdraft($url, $id = '')
  {
    if (Yii::$app->user->isGuest) {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }

    $result = array();
    $group = ProductGroups::find()->where(['product_group_id' => $url])->one();

    if (!$group) {
      $result['validated'] = false;
      $result['error'] = 'Ошибка сохранения: группа товаров не существует';
      return $result;
    }

    $product = Products::findOne(['id' => $id, 'user_id' => Yii::$app->user->identity->id]);
    if (!$product) $product = new Products();

    if ($product) {
      $product->group_id = $url;
      $product->status = Products::STATE_DRAFT;
      $product->load(Yii::$app->request->post(), '');
      $product->user_id = Yii::$app->user->identity->id;

      $attributes_arr = [];
      $target_attributes = [];
      $make = false;
      $model = false;
      $generation = false;
      $category = false;
      $year = false;
      $city = false;

      if ($product->make) {
        $make = Yii::$app->db->createCommand('SELECT name FROM makes WHERE url="' . $product->make . '"')->queryOne();
        if ($make) $product->make_val = $make['name'];
      }

      if ($product->model && $make) {
        $model = Yii::$app->db->createCommand('SELECT name FROM make_models WHERE url="' . $product->model . '" AND make_url="' . $product->make . '"')->queryOne();
        if ($model) $product->model_val = $model['name'];
      }

      if ($product->generation && $model) {
        $generation = Yii::$app->db->createCommand('SELECT name FROM make_generations WHERE url="' . $product->generation . '" AND model_url="' . $product->model . '"')->queryOne();
        if ($generation) $product->generation_val = $generation['name'];
      }

      if ($product->year) {
        $year = explode('_', $product->year);
        $year = end($year);
        if ($year) $product->year_val = $year;

        if ($generation && $generation['years']) {
          $split = explode('-', $generation['years']);

          if (isset($split[0]) && isset($split[1])) {
            if (intval($split[0]) && intval($split[1])) {
              $product->years = '';

              for ($i = intval($split[1]); $i >= intval($split[0]); $i--) {
                $product->years .= ',' . $i;
              }

              $product->years = substr($product->years, 1);
            }
          }
        }
      }

      if ($product->city) {
        $city = Yii::$app->db->createCommand('SELECT name FROM cities WHERE url="' . $product->city . '"')->queryOne();
        if ($city) $product->city = $city['name'];
      }

      if ($product->category) {
        $category = Yii::$app->db->createCommand('SELECT url, name FROM product_categories WHERE category_id=' . $product->category . ' OR url="' . $product->category . '"')->queryOne();

        if ($category) {
          $product->category = $category['url'];
          $product->category_val = $category['name'];
        }
      }

      $product_attributes = Yii::$app->db->createCommand('SELECT product_category_attributes.*, 
					product_category_attribute_groups.name, product_category_attribute_groups.required
					FROM product_category_attributes LEFT JOIN product_category_attribute_groups 
					ON product_category_attribute_groups.attribute_group_id = product_category_attributes.attribute_group_id
					WHERE product_category_attribute_groups.attribute_group_id IN (' . $group->attribute_groups . ')')->queryAll();

      foreach ($product_attributes as $attr) {
        if (!isset($attributes_arr[$attr['attribute_group_id']])) $attributes_arr[$attr['attribute_group_id']] = [];
        $attributes_arr[$attr['attribute_group_id']][] = $attr;
      }

      foreach (Yii::$app->request->post() as $k => $param) {
        if (strpos($k, 'attribute_') !== false) {
          $attr_id = explode('_', $k);
          $attr_id = end($attr_id);
          if (!isset($attributes_arr[$attr_id]) || empty($attributes_arr[$attr_id])) continue;
          $attr_val = false;

          foreach ($attributes_arr[$attr_id] as $attr) {
            if ($attr['url'] == $param) {
              $attr_val = $attr;
              break;
            }
          }

          if (!$attr_val) continue;

          $pa = new ProductAttributes();

          if ($pa->load(array(
            'product_id' => $product->id,
            'name' => $attr_val['name'],
            'value' => $attr_val['value'],
            'url' => $attr_val['url']
          ), '')) {
            $target_attributes[] = $pa;
          }
        }
      }

      if ($product->save(false)) {
        Yii::$app->db->createCommand('DELETE FROM product_attributes WHERE product_id=' . $product->id)->execute();
        foreach ($target_attributes as $pa) $pa->save();
        $result['url'] = '/personal/sell/' . $url . '/' . $product->id;
        return json_encode($result);
      } else {
        $result['validated'] = false;
        $result['error'] = 'Ошибка сохранения';
        return json_encode($result);
      }
    } else {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }
  }

  public function actionAddphone()
  {
    if (!Yii::$app->user->isGuest && Yii::$app->request->isPost) {
      $phone = Yii::$app->request->post('phone');

      if (!$phone) {
        return json_encode(['error' => true]);
      }

      $contacts = Yii::$app->user->identity->contacts;

      $check = Yii::$app->db
        ->createCommand('SELECT COUNT(*) FROM user_contacts WHERE phone LIKE "%' . $phone . '%"')
        ->queryScalar();

      if ($check) {
        return json_encode(['error' => 'Вы не можете добавить этот номер.<br>он уже используется на сайте.']);
      }

      $phones = $contacts->phones;

      if (is_array($phones) && in_array($phone, $phones)) {
        return json_encode(['error' => 'Вы не можете добавить этот номер.<br>он уже используется на сайте.']);
      }

      $res = Helpers::sendSMSCode($phone, 'Проверочный код');

      if (!$res['success']) {
        return json_encode($res);
      } else {
        Yii::$app->session->set('phone_to_check', $phone);
        Yii::$app->session->set('phone_test_code', $res['code']);
        return json_encode(['success' => true]);
      }
    } else {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }
  }

  public function actionActivateemail()
  {
    if (Yii::$app->user->isGuest && !Yii::$app->request->isPost) {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }

    $contacts = Yii::$app->user->identity->contacts;

    if (!$contacts->email) {
      return json_encode(['error' => true]);
    }

    if ($contacts->email_approved) {
      return json_encode(['success' => true]);
    }

    $hash = str_replace('-', '_', Yii::$app->security->generateRandomString(20));
    $config = \app\models\config\SMTP::find()->where('active=1')->one();

    try {
      if ($config) {
        $to = $contacts->email;
        $contacts->hash = $hash;
        $contacts->save();
        $subject = 'Подтверждение адреса';

        $text = '<p>Подтвердите ваш адрес электронной почты перейдя по ссылке ниже:</p>
								<p><a href="http://' . $_SERVER['HTTP_HOST'] . '/actions/validate/' . urlencode($hash) . '/">
								<strong>Подтвердить адрес</strong></a></p>';

        $config->sendEmail($to, $subject, '', $text);
        return json_encode(['success' => 'На ваш адрес почты отправлено письмо для подтверждения.<br>Пожалуйста, нажмите на ссылку в письме,<br>и ваш адрес будет подтвержден автоматически.']);
      }
    } catch (\Exception $e) {
      return json_encode(['error' => true]);
    }
  }

  public function actionDelphone()
  {
    if (!Yii::$app->user->isGuest && Yii::$app->request->isPost) {
      $phone = Yii::$app->request->post('phone');

      if (!$phone) {
        return json_encode(['error' => true]);
      }

      $user = Yii::$app->user->identity;

      if (!Yii::$app->request->post('fillial_id')) {
        $contacts = $user->contacts;
        $phones = $contacts->phones;

        if (count($phones) == 1) {
          return json_encode(['error' => 'Для удаления этого номера телефона вам необходимо добавить другой номер']);
        }

        foreach ($phones as $n => $ph) {
          if ($ph == $phone) {
            array_splice($phones, $n, 1);
            break;
          }
        }

        $success = true;

        if ($phone == $user->username) {
          $new_login = $phones[0];

          $check = Yii::$app->db
            ->createCommand('SELECT COUNT(*) FROM users WHERE username="' . $phones[0] . '" AND id!=' . $user->id)
            ->queryScalar();

          if ($check) {
            return json_encode(['error' => 'Удаление этого номера телефона невозможно,<br>поскольку номер <b>' . $new_login . '</b> не может быть использован<br>в качестве основного номера телефона - пользователь<br>с таким номером уже зарегистрирован в системе']);
          }

          $user->username = $new_login;
          $user->save();
          $success = 'Ваш логин был успешно изменен на <b>' . $new_login . '</b>';
        }

        $contacts->phone = $phones;
        $contacts->save();
      } else {
        $fid = Yii::$app->request->post('fillial_id');
        $user = Yii::$app->user->identity;

        $fillial = \app\models\user\Fillial::find()
          ->where(['user_id' => $user->id, 'id' => $fid])
          ->one();

        if (!$fillial) {
          throw new \yii\web\HttpException(404, 'Страница не найдена');
        }

        $phones = $fillial->phones;

        if (count($phones) == 1 && !$fillial->empty) {
          return json_encode(['error' => 'Необходимо указать хотя-бы один телефон для филлиала']);
        }

        foreach ($phones as $n => $ph) {
          if ($ph == $phone) {
            array_splice($phones, $n, 1);
            break;
          }
        }

        $success = true;
        $fillial->phone = $phones;
        $fillial->save();
      }

      return json_encode(['success' => $success]);
    } else {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }
  }

  public function actionValidatephone()
  {
    if (!Yii::$app->user->isGuest && Yii::$app->request->isPost) {
      $need_check = true;
      $phone = false;

      if (Yii::$app->request->post('fillial_id') && Yii::$app->request->post('skip')) {
        $need_check = false;
      }

      if ($need_check) {
        $code = Yii::$app->request->post('code');

        if (!$code) {
          return json_encode(['error' => true]);
        }

        $check = Yii::$app->session->get('phone_test_code');

        if (!$check) {
          return json_encode(['error' => true]);
        }

        if ($code != $check) {
          return json_encode(['error' => true, 'errors' => ['code' => 'Вы ввели неверный код']]);
        }

        $phone = Yii::$app->session->get('phone_to_check');
      } else {
        $phone = Yii::$app->request->post('skip');
      }

      if (!$phone) {
        return json_encode(['error' => true]);
      }

      if (!Yii::$app->request->post('fillial_id')) {
        if (!Yii::$app->request->post('no_save')) {
          $user = Yii::$app->user->identity;
          $contacts = $user->contacts;

          $arr = array();

          if (is_array($contacts->phones)) {
            $arr = $contacts->phones;
          }

          if (!in_array($phone, $arr) && !empty(trim($phone))) {
            $arr[] = trim($phone);
          }

          $contacts->phone = $arr;
          $contacts->save();
        }

        return json_encode(['success' => true]);
      } else {
        $fid = Yii::$app->request->post('fillial_id');
        $user = Yii::$app->user->identity;

        $fillial = \app\models\user\Fillial::find()
          ->where(['user_id' => $user->id, 'id' => $fid])
          ->one();

        if (!$fillial) {
          throw new \yii\web\HttpException(404, 'Страница не найдена');
        }

        $arr = array();

        if (is_array($fillial->phones)) {
          $arr = $fillial->phones;
        }

        if (!in_array($phone, $arr) && !empty(trim($phone))) {
          $arr[] = trim($phone);
        }

        $fillial->phone = $arr;
        $fillial->save(false);
        return json_encode(['success' => true]);
      }
    } else {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }
  }

  public function actionCalcdelivery()
  {
    if (Yii::$app->request->isPost) {
      $model = new \app\models\forms\DeliveryForm();

      if ($model->load(Yii::$app->request->post(), '')) {
        $result = $model->calculate();
        if ($model->errors) $result['errors'] = $model->errors;
        return json_encode($result);
      } else {
        throw new \yii\web\HttpException(404, 'Неверные данные запроса');
      }
    } else {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }
  }

  public function actionCheckname()
  {
    if (Yii::$app->request->isPost && !Yii::$app->user->isGuest) {
      $display_name = Yii::$app->request->post('name');

      if (!$display_name) {
        return json_encode([
          'error' => true,
          'errors' => ['display_name' => Yii::t('app', 'required_field')]
        ]);
      }

      $user = Yii::$app->user->identity;

      $check = Yii::$app->db
        ->createCommand('SELECT COUNT(*) FROM users WHERE LOWER(display_name)="' . trim(mb_strtolower($display_name)) . '" AND id!=' . $user->id)
        ->queryScalar();

      if ($check) {
        return json_encode([
          'error' => true,
          'used' => true
        ]);
      } else {
        return json_encode(['success' => true]);
      }
    } else {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }
  }

  public function actionSavename()
  {
    if (Yii::$app->request->isPost && !Yii::$app->user->isGuest) {
      $display_name = Yii::$app->request->post('name');

      if (!$display_name) {
        return json_encode([
          'error' => true,
          'errors' => ['display_name' => Yii::t('app', 'required_field')]
        ]);
      }

      $user = Yii::$app->user->identity;

      $check = Yii::$app->db
        ->createCommand('SELECT COUNT(*) FROM users WHERE LOWER(display_name)="' . trim(mb_strtolower($display_name)) . '" AND id!=' . $user->id)
        ->queryScalar();

      if ($check) {
        return json_encode([
          'error' => true,
          'errors' => ['display_name' => 'Это имя уже используется другим пользователем']
        ]);
      } else {
        Yii::$app->user->identity->display_name = $display_name;
        Yii::$app->user->identity->save();
        return json_encode(['success' => true]);
      }
    } else {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }
  }

  public function actionChangestatus()
  {
    $this->checkAccess('1');
    $user = Yii::$app->user->identity;
    $role = Yii::$app->request->post('role');
    $result = [];

    if ($role == User::ROLE_CLIENT || $role == User::ROLE_COMPANY) {
      if ($user->role != $role) {
        $user->role = $role;

        if ($user->save()) {
          if (!$user->company) {
            $company = new \app\models\user\Companies();
            $company->user_id = $user->id;
            $company->save(false);
          }
        }
      }

      $result['success'] = true;
      $result['validated'] = true;
    } else {
      $result['error'] = 'Ошибка смены статуса: недопустимый статус';
    }

    return json_encode($result);
  }

  public function actionCurs()
  {
    if (!Yii::$app->user->isGuest) {
      $curs = Yii::$app->user->identity->curs;
      $vals = $curs->curs_values;
      $scales = $curs->curs_scales;
      $req = Yii::$app->request;

      if ($curs->use_default) {
        if ($req->post('curs_rub')) {
          $vals['RUB'] = $req->post('curs_rub');
          $scales['RUB'] = $req->post('curs_rub_scale');
        }

        if ($req->post('curs_eur')) {
          $vals['EUR'] = $req->post('curs_eur');
          $scales['EUR'] = $req->post('curs_eur_scale');
        }

        if ($req->post('curs_usd')) {
          $vals['USD'] = $req->post('curs_usd');
          $scales['USD'] = $req->post('curs_usd_scale');
        }

        $curs->curs_values = json_encode($vals);
        $curs->curs_scales = json_encode($scales);

        if ($curs->save()) {
          Helpers::recalcProductPrices($vals, $scales, Yii::$app->user->identity->id);

          return json_encode(['success' => true]);
        } else {
          return json_encode(['error' => $curs->getErrors()]);
        }
      } else {
        return json_encode(['success' => true]);
      }
    } else {
      $vals = [];
      $scales = [];
      $req = Yii::$app->request;

      if ($req->post('curs_rub')) {
        $vals['RUB'] = $req->post('curs_rub');
        $scales['RUB'] = $req->post('curs_rub_scale');
      }

      if ($req->post('curs_eur')) {
        $vals['EUR'] = $req->post('curs_eur');
        $scales['EUR'] = $req->post('curs_eur_scale');
      }

      if ($req->post('curs_usd')) {
        $vals['USD'] = $req->post('curs_usd');
        $scales['USD'] = $req->post('curs_usd_scale');
      }

      Yii::$app->session->set('curs_values', [
        'vals' => $vals,
        'scales' => $scales
      ]);

      return json_encode(['error' => 'не авторизован']);
    }
  }

  public function actionEditfillial($url)
  {
    if ($this->checkAccess($url, false)) {
      $user = Yii::$app->user->identity;

      if (Yii::$app->user->identity->role == User::ROLE_CLIENT) {
        throw new HttpException('Страница не найдена');
      }

      $fillial = \app\models\user\Fillial::findOne(['user_id' => $user->id, 'id' => $url]);
      $country_id = $user->country_id;

      if ($fillial) {
        $country_id = \app\models\Countries::find()->where(['name' => $fillial->country])->one();
        if ($country_id) $country_id = $country_id['id'];
      }

      return $this->renderPartial('//layouts/parts/fillial_form', [
        'user' => $user,
        'options_country' => Lists::getOptionCountryList($country_id, true),
        'options_city' => Lists::getOptionCityList($fillial ? $fillial->city : $user->city, $country_id, true),
        'fillial' => $fillial
      ]);
    } else return 'no access';
  }

  public function actionChangelocation()
  {
    return $this->renderPartial('//layouts/parts/location_form', [
      'options_country' => Lists::getOptionCountryList(Yii::$app->request->get('country')),
      'options_city' => Lists::getOptionCityList(Yii::$app->request->get('city')),
    ]);
  }

  public function actionPhone($url, $id)
  {
    if ($this->checkAccess($url, false) && $id) {
      $user_id = Yii::$app->user->identity->id;

      $check = Yii::$app->db
        ->createCommand('SELECT ura.id AS await_id, 
          (SELECT ur.id FROM user_rates ur WHERE ur.sender_id=' . $user_id . '
				  AND item_id=' . $url . ' AND item_type="' . $id . '") AS rate_id 
				  FROM user_rate_await ura WHERE ura.user_id=' . $user_id . ' 
				  AND ura.obj_id=' . $url . ' AND ura.obj_type="' . $id . '"')
        ->queryOne();

      $seller_id = Yii::$app->db
        ->createCommand('SELECT user_id FROM products WHERE id=' . $url)
        ->queryOne();

      if ($seller_id) $seller_id = $seller_id['user_id'];

      if ($seller_id && $seller_id != $user_id) {
        Yii::$app->db
          ->createCommand('DELETE FROM user_rate_await WHERE user_id=' . $user_id . ' AND obj_id=' . $seller_id . ' AND obj_type="user"')
          ->execute();

        $await = new RateAwait();
        $await->user_id = $user_id;
        $await->obj_id = $seller_id;
        $await->obj_type = 'user';
        $await->save();
      }

      if (!$check || (!$check['await_id'] && !$check['rate_id'])) {
        $this->changeClicks($url, $id);

        $await = new RateAwait();
        $await->user_id = $user_id;
        $await->obj_id = $url;
        $await->obj_type = $id;
        $await->save();
        return 'saved';
      } else return 'await exists';
    } else return 'no access';
  }

  public function actionProfilephone($url)
  {
    $user_id = $url;

    if (Yii::$app->user->isGuest || $user_id != Yii::$app->user->identity->id) {
      $date = date('Y-m-d');

      $statistics = ProfileStatistics::find()
        ->where('user_id=' . $user_id . ' AND date="' . $date . '"')
        ->one();

      if ($statistics) {
        $statistics->clicks = $statistics->clicks + 1;
      } else {
        $statistics = new ProfileStatistics();
        $statistics->user_id = $user_id;
        $statistics->date = $date;
        $statistics->clicks = 1;
      }

      $statistics->save();
    }
  }

  public function actionSaveseller($url)
  {
    if (Yii::$app->user->isGuest) {
      return json_encode(['error' => 'Вы не авторизованы']);
    }

    $user = Yii::$app->user->identity;

    $f = Favourites::find()
      ->where(['user_id' => $user->id, 'target_user_id' => $url])
      ->one();

    if (!$f) {
      $f = new Favourites();
      $f->user_id = $user->id;
      $f->target_user_id = $url;
      $f->save();
      return json_encode(['success' => 'Продавец успешно добавлен в избранные']);
    }

    return json_encode(['success' => 'Вы уже добавили данного продавца в избранные']);
  }

  public function actionDeleteseller()
  {
    if (Yii::$app->user->isGuest) {
      return json_encode(['error' => 'Вы не авторизованы']);
    }

    $ids = Yii::$app->request->post('id');

    if (!$ids) {
      return json_encode(['error' => 'Ничего не выбрано']);
    }

    $where = '';

    foreach ($ids as $id) {
      if (!$id) continue;
      $where .= ',' . $id;
    }

    if (!empty($where)) {
      $where = substr($where, 1);

      Yii::$app->db
        ->createCommand('DELETE FROM user_favourites WHERE user_id=' . Yii::$app->user->identity->id . ' AND target_user_id IN (' . $where . ')')
        ->execute();

      return json_encode(['success' => true]);
    } else {
      return json_encode(['error' => 'Ничего не выбрано']);
    }
  }

  public function actionSendrate()
  {
    if (!Yii::$app->request->isPost) {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }

    $this->checkAccess('1');

    $model = new \app\models\forms\RatingForm();
    $model->load(Yii::$app->request->post(), '');
    $result = $model->saveData();

    if (!isset($result['success']) || $result['success']) {
      $result['error'] = isset($result['error']) ? $result['error'] : true;
      $result['errors'] = isset($result['errors']) ? $result['errors'] : $model->errors;
    }

    return json_encode($result);
  }

  public function actionSorting()
  {
    $json = ['status' => false];
    $type = Yii::$app->request->post('type');
    $val = Yii::$app->request->post('value');

    if ($type && $val) {
      Yii::$app->session->set('sorting_' . $type, $val);
      $json = ['status' => true];
    } else if ($type) {
      Yii::$app->session->remove('sorting_' . $type);
      $json = ['status' => true];
    }

    return json_encode($json);
  }

  public function actionSendmsg()
  {
    if (!Yii::$app->request->isPost) {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }

    $this->checkAccess('1');

    $model = new \app\models\forms\MessageForm();
    $model->load(Yii::$app->request->post(), '');
    $result = $model->saveData();

    if (!isset($result['success']) || $result['success']) {
      $result['error'] = isset($result['error']) ? $result['error'] : true;
      $result['errors'] = isset($result['errors']) ? $result['errors'] : $model->errors;
    }

    return json_encode($result);
  }

  public function actionSendntf()
  {
    if (Yii::$app->user->isGuest) {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }

    $model = new \app\models\forms\NotificationForm();
    $model->load(Yii::$app->request->post(), '');
    $result = $model->saveData();

    if (!isset($result['success']) || !$result['success']) {
      $result['error'] = isset($result['error']) ? $result['error'] : true;
      $result['errors'] = isset($result['errors']) ? $result['errors'] : $model->errors;
    }

    return json_encode($result);
  }

  public function actionSendcomment()
  {
    if (Yii::$app->user->isGuest) {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }

    $model = new \app\models\forms\CommentForm();
    $model->load(Yii::$app->request->post(), '');
    $result = $model->saveData();

    if (!isset($result['success']) || !$result['success']) {
      $result['error'] = isset($result['error']) ? $result['error'] : true;
      $result['errors'] = isset($result['errors']) ? $result['errors'] : $model->errors;
    }

    return json_encode($result);
  }

  /*
  public function actionDeletedialog()
  {
    if (Yii::$app->user->isGuest) {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }

    $user = Yii::$app->user->identity;
    $ids = Yii::$app->request->post('id');
    $this->deleteDialogs($ids, Dialogs::STATE_DELETED);

    return 'ok';
  }

  public function actionPdeletedialog()
  {
    if (Yii::$app->user->isGuest) {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }

    $ids = Yii::$app->request->post('id');
    $this->deleteDialogs($ids, Dialogs::STATE_DELETED);
    return 'ok';
  }

  public function actionPdeletedialog2()
  {
    if (Yii::$app->user->isGuest) {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }

    $ids = Yii::$app->request->post('id');
    $this->deleteDialogs($ids, Dialogs::STATE_DELETED_AND_LOCKED);

    return 'ok';
  }
  */

  /*
  public function actionResumedialog()
  {
    if (Yii::$app->user->isGuest) {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }

    $user = Yii::$app->user->identity;
    $ids = Yii::$app->request->post('id');

    $sql = 'UPDATE dialogs SET sender_state=' . Dialogs::STATE_ACTIVE . ', date_updated="' . date('Y-m-d H:i:s') .
      '" WHERE sender_id=' . $user->id . ' AND id in (';

    $sql1 = 'UPDATE dialogs SET receiver_state=' . Dialogs::STATE_ACTIVE . ', date_updated="' . date('Y-m-d H:i:s') .
      '" WHERE receiver_id=' . $user->id . ' AND id in (';

    $where = '';

    foreach ($ids as $id) {
      if (!empty($where)) $where .= ',';
      $where .= $id;
    }

    if (!empty($where)) {
      Yii::$app->db->createCommand($sql . $where . ')')->execute();
      Yii::$app->db->createCommand($sql1 . $where . ')')->execute();
    }

    return 'ok';
  }
  */

  /*
  public function actionLockdialog()
  {
    if (Yii::$app->user->isGuest) {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }

    $user = Yii::$app->user->identity;
    $ids = Yii::$app->request->post('id');

    $sql = 'UPDATE dialogs SET sender_state=' . Dialogs::STATE_LOCKED . ', date_updated="' . date('Y-m-d H:i:s') .
      '" WHERE sender_id=' . $user->id . ' AND id in (';

    $sql1 = 'UPDATE dialogs SET receiver_state=' . Dialogs::STATE_LOCKED . ', date_updated="' . date('Y-m-d H:i:s') .
      '" WHERE receiver_id=' . $user->id . ' AND id in (';

    $where = '';

    foreach ($ids as $id) {
      if (!empty($where)) $where .= ',';
      $where .= $id;
    }

    if (!empty($where)) {
      Yii::$app->db->createCommand($sql . $where . ')')->execute();
      Yii::$app->db->createCommand($sql1 . $where . ')')->execute();
    }

    return 'ok';
  }
  */

  public function actionSpamdialog()
  {
    if (Yii::$app->user->isGuest) {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }

    $user = Yii::$app->user->identity;
    $id = Yii::$app->request->post('id');

    $sql = 'UPDATE dialogs SET sender_state=' . Dialogs::STATE_LOCKED . ', date_updated="' . date('Y-m-d H:i:s') .
      '" WHERE sender_id=' . $user->id . ' AND id=' . $id;

    $sql1 = 'UPDATE dialogs SET receiver_state=' . Dialogs::STATE_LOCKED . ', date_updated="' . date('Y-m-d H:i:s') .
      '" WHERE receiver_id=' . $user->id . ' AND id=' . $id;

    if (!empty($where)) {
      Yii::$app->db->createCommand($sql)->execute();
      Yii::$app->db->createCommand($sql1)->execute();
    }

    $target = Yii::$app->request->post('target_id');
    $text = Yii::$app->request->post('text');

    if ($text) {
      $where = '';
      $to_insert = array();
      $to_insert[] = [$user->id, $target, $text];

      if (!empty($where)) {
        Yii::$app->db
          ->createCommand('DELETE FROM complaints WHERE user_id=' . $user->id .
          ' AND target_id=' . $target . ' AND text = "' . $text . '"')
          ->execute();

        Yii::$app->db->createCommand()
          ->batchInsert('complaints', ['user_id', 'target_id', 'text'], $to_insert)
          ->execute();
      }
    }

    return json_encode(['success' => true]);
  }

  public function actionDeletefillial()
  {
    return $this->deleteItem(\app\models\user\Fillial::class, Yii::$app->request->post('id'), false);
  }

  public function actionSpamproduct()
  {
    $model = new \app\models\forms\SpamForm();
    $model->load(Yii::$app->request->post(), '');
    $result = $model->saveData();

    if (!isset($result['success']) || !$result['success']) {
      $result['error'] = isset($result['error']) ? $result['error'] : true;
      $result['errors'] = isset($result['errors']) ? $result['errors'] : $model->errors;
    }

    return json_encode($result);
  }

  public function actionGetmsg($url)
  {
    if (Yii::$app->user->isGuest) {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }

    $min = Yii::$app->request->post('current');
    $max = Yii::$app->request->post('max');

    if ($min > 0 && $max > 0) {
      $user = Yii::$app->user->identity;

      $messages = DialogMessages::find()
        ->with('sender', 'receiver')
        ->select([
          'dialog_messages.*',
          'sender_name' => '(SELECT display_name FROM users WHERE id=sender_id)'
        ])
        ->where(['dialog_id' => $url])
        ->limit($max)
        ->offset($min)
        ->orderBy('date_updated DESC')
        ->all();

      $str = 'UPDATE dialog_messages SET state=1 WHERE id in (';
      $where = '';

      foreach ($messages as $message) {
        if (!$user || $message->sender_id == $user->id) continue;

        if ($message->state != 1) {
          if (!empty($where)) $where .= ',';
          $where .= $message->id;
        }
      }

      if (!empty($where)) {
        Yii::$app->db
          ->createCommand($str . $where . ') AND sender_id!=' . $user->id)
          ->execute();
      }

      $arr = array();

      foreach ($messages as $message) {
        $el = false;

        foreach ($arr as $a) {
          if ($a['id'] == $message['id']) {
            $el = $a;
            break;
          }
        }

        if (!$el) {
          $el = $message->attributes;
          $el['sender_name'] = $message->sender_name;

          if ($el['images']) {
            $el['orig_imgs'] = $el['images'];
            $el['images'] = array();

            foreach ($el['orig_imgs'] as $src) {
              $el['images'][] = Helpers::getImageByURL($src, 200, 200);
            }
          }

          $el['images'] = array();

          $diff = Helpers::getDateOffset($message['date_updated']);
          $dt = new \DateTime($message['date_updated']);
          $cur = -1;

          if ($diff->y > 0) {
            if ($diff->y * 100000 != $cur) $el['date_text'] = '<div class="date-block" data-cur="' . $diff->y * 100000 . '"><span>' . $dt->format('o') . '</span></div>';
            $cur = $diff->y * 100000;
          } else if ($diff->m > 0) {
            if ($diff->m * 10000 != $cur) $el['date_text'] = '<div class="date-block" data-cur="' . $diff->m * 10000 . '"><span>' . $dt->format('j') . ' ' . Helpers::getDayMonthTranslated($dt->format('F')) . '</span></div>';
            $cur = $diff->m * 10000;
          } else if ($diff->d > 1) {
            if ($diff->d * 1000 != $cur) $el['date_text'] = '<div class="date-block" data-cur="' . $diff->d * 1000 . '"><span>' . $dt->format('j') . ' ' . Helpers::getDayMonthTranslated($dt->format('F')) . '</span></div>';
            $cur = $diff->d * 1000;
          } else if ($diff->d == 1) {
            if ($cur != 1) $el['date_text'] = '<div class="date-block" data-cur="1"><span>Вчера</span></div>';
            $cur = 1;
          } else if ($diff->d < 1) {
            if ($cur != 0) $el['date_text'] = '<div class="date-block" data-cur="0"><span>Сегодня</span></div>';
            $cur = 0;
          }

          $el['cur'] = $cur;
          $el['date'] = $dt->format('H') . ':' . $dt->format('i');
          $arr[] = $el;
        } else {
          $el['images'][] = $message['img_id'];
        }
      }

      return json_encode($arr);
    } else {
      return json_encode([]);
    }
  }

  public function actionSavesearch()
  {
    if (Yii::$app->user->isGuest) {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }

    $result = array();
    $search = new \app\models\user\Searches();

    if ($search->load(Yii::$app->request->post(), '')) {
      $search->user_id = Yii::$app->user->identity->id;

      if ($search->save()) {
        Yii::$app->db->createCommand('DELETE FROM user_search WHERE user_id=' . $search->user_id .
          ' AND url="' . $search->url . '" AND id!=' . $search->id)->execute();

        $result['success'] = true;
      }
    }

    return json_encode($result);
  }

  public function actionDownloadproducts($url)
  {
    if (!Yii::$app->user->isGuest) {
      XLSExporter::exportProducts($url);
    }
  }

  public function actionDelreport()
  {
    if (!Yii::$app->user->isGuest) {
      Yii::$app->db
        ->createCommand('DELETE FROM product_uploads WHERE user_id=' . Yii::$app->user->identity->id . ' AND upload_id="' . Yii::$app->request->post('upload_id') . '"')
        ->execute();

      return json_encode(['success' => true]);
    }
  }

  public function actionDelrulesbyids()
  {
    if (!Yii::$app->user->isGuest) {
      $ids = array_map(
        function ($i) {
          return (int)$i;
        },
        explode(',', Yii::$app->request->post('ids', ''))
      );

      if ($ids) {
        $upload_rules = ProductUploadRules::find()
          ->select('maybe_hash')
          ->where([
            'user_id' => Yii::$app->user->identity->id,
            'id' => $ids
          ])
          ->all();

        $hash_codes = [];

        foreach ($upload_rules as $up) {
          $hash_codes[] = $up->maybe_hash;
        }

        ProductUploadRules::deleteAll([
          'user_id' => Yii::$app->user->identity->id,
          'id' => $ids
        ]);

        if ($hash_codes) {
          ProductUploadRuleValues::deleteAll([
            'user_id' => Yii::$app->user->identity->id,
            'maybe_hash' => $hash_codes
          ]);
        }
      }

      return json_encode(['success' => true, 'reload' => true]);
    }
  }

  public function actionDelrules()
  {
    if (!Yii::$app->user->isGuest) {
      Yii::$app->db
        ->createCommand('DELETE FROM upload_rules WHERE user_id=' . Yii::$app->user->identity->id)
        ->execute();

      return json_encode(['success' => true, 'reload' => true]);
    }
  }

  public function actionDownloadreport($url)
  {
    if (!Yii::$app->user->isGuest) {
      XLSExporter::downloadReport($url);
    } else {
      $this->goBack();
    }
  }

  public function actionSaveuploadrule($url)
  {
    if (!Yii::$app->user->isGuest && Yii::$app->request->isPost) {
      $rule = ProductUploadRules::find()
        ->where(['id' => $url, 'user_id' => Yii::$app->user->identity->id])
        ->one();

      if (!$rule) {
        return json_encode(['error' => 'Ошибка сохранения: правило не найдено']);
      }

      $data = Yii::$app->request->post();

      if (Yii::$app->request->post('del_item')) {
        $rule->delete();
        return json_encode(['success' => true]);
      }

      $rule->active = $data['active'];

      if (isset($data['val']) && $data['val']) {
        $rule->param_val = $data['val'];
      } else if (isset($data['param_key'])) {
        $rule->param_key = $data['param_key'];

        if (!$rule->param_key) {
          $rule->active = false;
        }
      } else {
        return json_encode(['error' => 'Ошибка сохранения: Отсутствует значение параметра']);
      }

      if ($rule->save()) {
        return json_encode(['success' => true, 'reload' => true]);
      } else {
        return json_encode(['error' => 'Что-то пошло не так...']);
      }
    }
  }

  public function actionAdduploaderror()
  {
    try {
      if (!Yii::$app->user->isGuest && Yii::$app->request->isPost) {
        if (
          !Yii::$app->request->post('errors') ||
          !Yii::$app->request->post('group_id') ||
          !Yii::$app->request->post('upload_key')) {
          throw new \yii\web\HttpException(400, 'Неверные данные');
        }

        $rules = [];
        $rule_values = [];
        $group_id = Yii::$app->request->post('group_id');
        $upload_id = Yii::$app->request->post('upload_id');
        $upload_key = Yii::$app->request->post('upload_key');
        $error_arr = json_decode(Yii::$app->request->post('errors'), true);
        $user = Yii::$app->user->identity;
        $product_uploads = [];

        $new_rules_check = [];
        $new_rule_values_check = [];

        $result = [
          'success' => true,
          'errors' => [],
          'data' => []
        ];

        foreach ($error_arr as $data) {
          $item = false;

          if (isset($data['columns']) && isset($data['errors'])) {
            $item = new ProductUploads();
            $item->columns = json_encode($data['columns']);
            $item->error_vals = json_encode($data['errors']);
            $item->user_id = $user->id;
            $item->date = date('Y-m-d H:i:s');
            $item->upload_id = $upload_id;
            $item->group_id = $group_id;
            $product_uploads[] = $item;
          }

          $object = false;

          if (isset($data['object'])) {
            $object = $data['object'];
          }

          if ($object) {
            $errors = $data['errors'];

            foreach ($errors as $key => $val) {
              // skip generation errors
              if ($key == 'generation') {
                continue;
              }

              if (
                !isset($object['maybe_values_' . $key . '_class']) ||
                !$object['maybe_values_' . $key . '_class'] ||
                !isset($object['maybe_values_' . $key . '_where'])
              ) {
                continue;
              }

              if (!isset($object['orig_val_' . $key])) {
                $object['orig_val_' . $key] = '';
              }

              $orig_val = '';

              if ($key == 'generation') {
                /*
                if (!$object['orig_val_' . $key]) {
                  continue;
                }

                $orig_val = '';

                if (isset($object['make_val']) && !empty($object['make_val'])) {
                  $orig_val .= $object['make_val'] . ' ';
                }

                if (isset($object['model_val']) && !empty($object['model_val'])) {
                  $orig_val .= $object['model_val'] . ' ';
                }

                if (isset($object['year_val']) && !empty($object['year_val'])) {
                  $orig_val .= $object['year_val'] . ' ';
                }

                $orig_val .= $object['orig_val_' . $key];
                */
              } else if ($key == 'model') {
                if (!$object['orig_val_' . $key]) {
                  continue;
                }

                $orig_val = '';

                if (isset($object['make_val']) && !empty($object['make_val'])) {
                  $orig_val .= $object['make_val'] . ' ';
                }

                $orig_val .= $object['orig_val_' . $key];
              } else {
                $orig_val = $object['orig_val_' . $key];
              }

              if (!$orig_val) {
                continue;
              }

              $hash = md5($object['maybe_values_' . $key . '_class'] . $object['maybe_values_' . $key . '_where'] . $object['orig_val_' . $key]);

              $check = ProductUploadRules::find()
                ->where([
                  'user_id' => $user->id,
                  'group_id' => $group_id,
                  'param_key' => $key,
                  'maybe_hash' => $hash,
                  'orig_val' => $object['orig_val_' . $key]
                ])
                ->one();

              if ($check) {
                if (strpos($check->upload_key, $upload_key) === false) {
                  $check->upload_key .= (!empty($upload_key) ? ';' . $upload_key : $upload_key);
                  $check->save();
                }

                continue;
              }

              if (!isset($new_rules_check[$hash])) {
                $new_rules_check[$hash] = $hash;
                $rule = new ProductUploadRules();
                $rule->user_id = $user->id;
                $rule->group_id = $group_id;
                $rule->upload_key = $upload_key;
                $rule->param_key = $key;
                $rule->comment = $orig_val;
                $rule->orig_val = $object['orig_val_' . $key];
                $rule->active = 0;
                $rule->maybe_hash = $hash;
                $rules[] = $rule;
              }

              $vals_exist = ProductUploadRuleValues::find()
                ->where([
                  'user_id' => $user->id,
                  'group_id' => $group_id,
                  'maybe_hash' => $hash
                ])
                ->count();

              if (!$vals_exist && !isset($new_rules_values_check[$hash])) {
                $new_rules_values_check[$hash] = $hash;
                $rval = new ProductUploadRuleValues();
                $rval->user_id = $user->id;
                $rval->group_id = $group_id;
                $rval->maybe_hash = $hash;
                $rval->class_name = $object['maybe_values_' . $key . '_class'];
                $rval->where_str = $object['maybe_values_' . $key . '_where'];
                $rule_values[] = $rval;
              }
            }
          } else if (!isset($new_rules_check[$data['orig_val']])) {
            $check = ProductUploadRules::find()
              ->where([
                'group_id' => $group_id,
                'orig_val' => $data['orig_val']
              ])
              ->count();

            $new_rules_check[$data['orig_val']] = $data['orig_val'];

            if (!$check) {
              $rule = new ProductUploadRules();
              $rule->user_id = $user->id;
              $rule->group_id = $group_id;
              $rule->upload_key = $upload_key;
              $rule->orig_val = $data['orig_val'];
              $rule->active = 0;
              $rules[] = $rule;
            }
          }
        }

        if ($rules) {
          $attr_model = new ProductUploadRules();

          Yii::$app->db->createCommand()
            ->batchInsert(
              ProductUploadRules::tableName(),
              $attr_model->attributes(),
              $rules
            )
            ->execute();
        }

        if ($rule_values) {
          $attr_model = new ProductUploadRuleValues();

          Yii::$app->db->createCommand()
            ->batchInsert(
              ProductUploadRuleValues::tableName(),
              $attr_model->attributes(),
              $rule_values
            )
            ->execute();
        }

        if ($product_uploads) {
          $attr_model = new ProductUploads();

          Yii::$app->db->createCommand()
            ->batchInsert(
              ProductUploads::tableName(),
              $attr_model->attributes(),
              $product_uploads
            )
            ->execute();
        }

        return json_encode($result);
      }
    } catch (\Exception $e) {
      return $e;
    }
  }

  protected function checkAccess($url, $silent = false)
  {
    if (Yii::$app->user->isGuest) {
      if ($silent) return false;
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }

    if (Yii::$app->user->identity->state == User::STATE_LOCKED) {
      if ($silent) return false;
      throw new \yii\web\HttpException(403, 'Операция недоступна');
    }

    if (empty($url)) {
      if ($silent) return false;
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }

    return true;
  }

  protected function getItemsForAction($class, $url) {
    $user = Yii::$app->user->identity;
    $this->checkAccess($url);
    $arr = is_array($url) ? $url : [$url];
    $where = '';

    foreach ($arr as $id) $where .= ',' . $id;
    $all = Yii::$app->request->post('all');

    $result = [
      'products' => [],
      'where' => $where
    ];

    if (!$all) {
      $arr = is_array($url) ? $url : [$url];
      foreach ($arr as $id) $where .= ',' . $id;

      if ($where) {
        $result['products'] = $class::find()
          ->where('id IN (' . substr($where, 1) . ') AND user_id=' . $user->id)
          ->all();
      }
    } else {
      $query = parse_url($all);
      if (!$query) return $result;

      $get_arr = [];
      parse_str(isset($query['query']) ? $query['query'] : '', $get_arr);

      $model = new Products();
      $where = [];
      $attr_where = [];

      $link_array = explode('/' , $query['path']);
      $group_url = isset($link_array[3]) ? $link_array[3] : '';
      $group_url = explode('?', $group_url);
      $group_url = $group_url[0];

      if (isset($get_arr['url'])) {
        $group_url = $get_arr['url'];
      }

      $product_group_id = $group_url ? Yii::$app->db
        ->createCommand('SELECT product_group_id FROM product_groups WHERE url="'.$group_url.'"')
        ->queryOne() : false;

      foreach ($get_arr as $key => $get) {
        if (!$get) continue;
        if ($key == 'city' || $key == 'country' && $get == 'all') continue;
        if ($model->hasAttribute($key)) $where[$key] = $get;

        if (strpos($key, 'attribute_') !== false) {
          $attr_where[] = ['like', 'attributes_list', '%' . $get . '%', false];
        }
      }

      $result['products'] = $class::find()
        ->where(array_merge(['and', array_merge(
          ($product_group_id ? ['group_id' => $product_group_id['product_group_id']] : []),
          [
            'user_id' => Yii::$app->user->identity->id,
          ], $where)], $attr_where))
        ->all();
    }

    return $result;
  }

  protected function activateItem($class, $url)
  {
    $result = $this->getItemsForAction($class, $url);
    $products = $result['products'];

    if ($products) {
      foreach ($products as $product) {
        if ($product->status == Products::STATE_INACTIVE) {
          $product->status = Products::STATE_ACTIVE;
          $product->save();
        }
      }

      return json_encode(['success' => true]);
    } else {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }
  }

  protected function deactivateItem($class, $url)
  {

    $result = $this->getItemsForAction($class, $url);
    $products = $result['products'];

    if ($products) {
      foreach ($products as $product) {
        if ($product->status == Products::STATE_ACTIVE) {
          $product->status = Products::STATE_INACTIVE;
          $product->save();
        }
      }

      return json_encode(['success' => true]);
    } else {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }
  }

  protected function deleteItem($class, $url, $redirectTo = true, $no_delete_status = false)
  {
    $result = $this->getItemsForAction($class, $url);
    $products = $result['products'];
    $where = $result['where'];

    if ($products) {
      $user = Yii::$app->user->identity;
      $str = '';
      $ids = '';

      foreach ($products as $product) {
        $ids .= $product->id . ',';

        if ($product->hasProperty('images') && $product->images) {
          foreach ($product->images as $td) {
            $str .= ',"' . $td . '"';

            if (file_exists(Yii::$app->basePath . $td)) {
              unlink(Yii::$app->basePath . $td);
            }

            $dirname = Yii::$app->basePath . '/web/gallery/tmp/products_' . $user->id;

            if (file_exists($dirname)) {
              $image_name = explode('.', basename($td));
              $image_name = $image_name[0];
              array_map('unlink', glob($dirname . '/' . $image_name . '*'));
            }
          }
        }
      }

      $where = substr($ids, 0, -1);
      $class::deleteAll('id IN (' . $where . ') AND user_id=' . $user->id);

      if ($str) {
        Yii::$app->db
          ->createCommand('DELETE FROM images_hash WHERE url IN (' . substr($str, 1) . ') AND user_id=' . $user->id)
          ->execute();
      }

      if ($redirectTo) {
        $rdr = Yii::$app->request->referrer;
        return $this->redirect($rdr);
      } else {
        return json_encode(['success' => true]);
      }
    } else {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }
  }

  protected function deleteDialogs($ids = array(), $state = Dialogs::STATE_DELETED)
  {
    $user = Yii::$app->user->identity;

    $sql = 'UPDATE dialogs SET sender_state=' . $state . ' WHERE (sender_id=' . $user->id . ') AND id in (';
    $sql1 = 'UPDATE dialogs SET receiver_state=' . $state . ' WHERE (receiver_id=' . $user->id . ') AND id in (';

    $sql2 = 'DELETE FROM dialogs WHERE (receiver_id=' . $user->id .
      ' OR sender_id=' . $user->id . ') 
			AND (sender_state=' . Dialogs::STATE_DELETED . ' OR sender_state=' . Dialogs::STATE_DELETED_AND_LOCKED . ') 
			AND (receiver_state=' . Dialogs::STATE_DELETED . ' OR receiver_state=' . Dialogs::STATE_DELETED_AND_LOCKED .
      ') AND id in (';

    $where = '';

    foreach ($ids as $id) {
      if (!empty($where)) $where .= ',';
      $where .= $id;
    }

    if (!empty($where)) {
      Yii::$app->db->createCommand($sql . $where . ')')->execute();
      Yii::$app->db->createCommand($sql1 . $where . ')')->execute();

      $deleted = Yii::$app->db
        ->createCommand('SELECT images FROM dialog_messages 
          LEFT JOIN dialogs ON dialogs.id = dialog_messages.dialog_id
          WHERE dialog_id in (' . $where . ') 
				  AND (dialogs.sender_state=' . Dialogs::STATE_DELETED . ' OR dialogs.sender_state=' . Dialogs::STATE_DELETED_AND_LOCKED . ') 
				  AND (dialogs.receiver_state=' . Dialogs::STATE_DELETED . ' OR dialogs.receiver_state=' . Dialogs::STATE_DELETED_AND_LOCKED . ')
				  AND (dialogs.receiver_id=' . $user->id . ' OR dialogs.sender_id=' . $user->id . ')
				  AND images IS NOT NULL')
        ->queryAll();

      Yii::$app->db->createCommand($sql2 . $where . ')')->execute();

      if ($deleted) {
        foreach ($deleted as $del) {
          $images = json_decode($del['images']);

          if (is_array($images)) {
            foreach ($images as $image) {
              if (file_exists(Yii::$app->basePath . $image)) {
                unlink(Yii::$app->basePath . $image);
              }

              $dirname = Yii::$app->basePath . '/web/gallery/tmp/messages_' . $user->id;

              if (file_exists($dirname)) {
                $image_name = explode('.', basename($image));
                $image_name = $image_name[0];
                array_map('unlink', glob($dirname . '/' . $image_name . '*'));
              }
            }
          }
        }
      }
    }
  }

  protected function changeClicks($url, $type)
  {
    $date = date('Y-m-d');

    $user_id = Yii::$app->db
      ->createCommand('SELECT user_id FROM products WHERE id=' . $url)
      ->queryOne();

    $statistics = Statistics::find()
      ->where('user_id=' . $user_id['user_id'] . ' AND date="' . $date . '"')
      ->one();

    if ($statistics) {
      $statistics->clicks = $statistics->clicks + 1;
      $statistics->addClick($url);
    } else {
      $statistics = new Statistics();
      $statistics->user_id = $user_id['user_id'];
      $statistics->group_id = $type;
      $statistics->date = $date;
      $statistics->clicks = 1;
      $statistics->products = array($url => 1);
    }

    $statistics->save();
  }
}
