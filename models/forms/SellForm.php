<?php

namespace app\models\forms;

use Yii;
use yii\base\Model;
use app\models\Products;
use yii\web\UploadedFile;

use app\models\products\ProductAttributes;
use app\models\helpers\Helpers;
use yii\imagine\Image;

class SellForm extends Model
{
  public $product_group_id;
  public $product_id;
  public $category;
  public $category_name;
  public $country;
  public $sku;
  public $partnum;
  public $make;
  public $model;
  public $generation;
  public $quantity;
  public $year;
  public $body;
  public $city;
  public $short_description;
  public $text;
  public $status;
  public $available;
  public $sale;
  public $currency;
  public $price;
  public $imgs;
  public $imgs_to_delete;
  public $width;
  public $height;
  public $length;
  public $weight;
  public $contact_type;
  public $address;
  public $image_config;
  public $image_order;

  public function rules()
  {
    return [
      [['city', 'country', 'quantity', 'product_group_id', 'price', 'currency'], 'required', 'message' => Yii::t('app', 'required_field')],
      ['category', 'required', 'when' => function ($model, $attribute) { return empty($model->text); }, 'message' => Yii::t('app', 'required_field')],
      ['text', 'required', 'when' => function ($model, $attribute) { return empty($model->category); }, 'message' => Yii::t('app', 'required_field')],
      [['sku', 'partnum', 'make', 'model', 'city', 'generation'], 'string', 'max' => 100, 'tooLong' => 'Длина поля должна быть не более 100 символов'],
      [['short_description', 'address'], 'string', 'max' => 255, 'tooLong' => 'Длина поля должна быть не более 255 символов'],
      ['category_name', 'string', 'max' => 255, 'skipOnEmpty' => true],
      [['currency', 'year'], 'string', 'max' => 30, 'tooLong' => 'Длина поля должна быть не более 30 символов'],
      [['text'], 'string', 'max' => 1000, 'tooLong' => 'Длина поля должна быть не более 1000 символов'],
      [['available'], 'boolean'],
      [['image_config', 'image_order'], 'string', 'skipOnEmpty' => true],
      ['contact_type', 'in', 'range' => ['Телефон', 'Viber', 'Telegram', 'Почта']],
      [['price'], 'number', 'min' => '1', 'max' => 9999999999, 'tooSmall' => 'Цена не может быть ниже 1', 'tooBig' => 'Цена не может превышать 9999999999'],
      [['sale'], 'number', 'min' => '0', 'max' => 100, 'tooSmall' => 'Скидка не может быть ниже 0', 'tooBig' => 'Скидка не может превышать 100'],
      ['status', 'integer', 'min' => 0, 'max' => 4],
      ['quantity', 'integer', 'min' => 0, 'max' => 999999, 'tooSmall' => 'Скидка не может быть ниже 0', 'tooBig' => 'Скидка не может превышать 999999'],
      ['imgs', 'each', 'rule' => ['file', 'extensions' => 'png, jpg, jpeg, gif']],
      ['imgs_to_delete', 'each', 'rule' => ['string'], 'skipOnEmpty' => true],
      [['width', 'height', 'length', 'weight'], 'double', 'min' => 0, 'max' => 999999],
    ];
  }

  public function saveData($product_group)
  {
    $result = array(
      'errors' => [],
      'error' => true
    );

    $user = false;

    $this->imgs = UploadedFile::getInstancesByName('imgs');
    $result['messages'] = array();
    $errors = [];

    if (!$product_group) {
      $result['validated'] = false;
      $result['error'] = 'Ошибка: группа товаров не существует';
      return $result;
    }

    $gr_validate = $product_group->validateData(Yii::$app->request->post());

    if (!$gr_validate['validated']) {
      $result['validated'] = false;
      $errors = $gr_validate['errors'];
      $result['error'] = $gr_validate['error'];
      $result['errors'] = $gr_validate['errors'];
    }

    if (!$this->imgs) {
      $errors['imgs'] = 'Фото товара обязательно';
      $result['validated'] = false;
      $result['error'] = 'Ошибка: не все поля заполнены верно';
    }

    $category = false;

    if ($this->category) {
      $category = \app\models\products\Categories::find()
        ->where('category_id="' . $this->category . '" OR url="' . $this->category . '"')
        ->one();

      if ($category) {
        $valid = $category->validateData(Yii::$app->request->post());

        if (!$valid['validated']) {
          $result['validated'] = false;
          $result['error'] = 'Ошибка: не все поля заполнены верно';
          foreach ($valid['errors'] as $key => $val) $errors[$key] = $val;
        }
      }
    }

    if ($this->validate() && !$errors) {
      $result = ['error' => false];
      $product = new Products();
      $product->group_id = $this->product_group_id;

      if ($product->load(Yii::$app->request->post(), '')) {
        if ($category) {
          $product->category = $category->parent_url ? $category->parent_url : $category->url;
          $product->category_val = $this->category_name ? $this->category_name : $category->name;
        }

        if (Yii::$app->user->isGuest) {
          $mod = new \app\models\forms\RegisterForm();
          $res = $mod->create();

          if ($res['error']) {
            return $res;
          } else {
            return ['success' => true, 'errors' => []];
          }
        }

        $product->user_id = $user->id;
        $product->name_template = $product_group->product_name_template;
        $product->url_template = $product_group->product_url_template;
        $product->seller_name = $user->name;
        $product->seller_phone = $user->username;
        $product->seller_email = $user->contacts->email;

        if ($product->status != Products::STATE_LOCKED) $product->status = Products::STATE_ACTIVE;
        $attributes_arr = [];
        $target_attributes = [];
        $make = false;
        $model = false;
        $generation = false;
        $year = false;
        $city = false;
        $product->attributes_list = '';

        if ($product->make) {
          $make = Yii::$app->db
            ->createCommand('SELECT name FROM makes WHERE url="' . $product->make . '"')
            ->queryOne();

          if ($make) $product->make_val = $make['name'];
        }

        if ($product->model && $make) {
          $model = Yii::$app->db
            ->createCommand('SELECT name FROM make_models WHERE url="' . $product->model .
              '" AND make_url="' . $product->make . '"')
            ->queryOne();

          if ($model) $product->model_val = $model['name'];
        }

        if ($product->generation && $model) {
          $generation = Yii::$app->db
            ->createCommand('SELECT name FROM make_generations WHERE url="' . $product->generation . '" AND model_url="' . $product->model . '"')
            ->queryOne();

          if ($generation) $product->generation_val = $generation['name'];
        }

        if ($product->year) {
          $year = explode('_', $product->year);
          $year = end($year);
          if ($year) $product->year_val = $year;

          if ($generation && isset($generation['years'])) {
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
          $city = Yii::$app->db
            ->createCommand('SELECT name, domain, country_id FROM cities 
                WHERE name="' . $product->city . '"')
            ->queryOne();

          if ($city) {
            $product->city = $city['name'];
            $product->city_domain = $city['domain'];
            $product->country_id = $city['country_id'];
          }
        }

        $attribute_ids = '';

        foreach ($product_group->attribute_groups as $key => $val) {
          if (strpos($key, 'attribute_') !== false) {
            $exp = explode('_', $key);
            $attribute_ids .= ',' . $exp[1];
          }
        }

        if ($attribute_ids) {
          $attribute_ids = substr($attribute_ids, 1);

          $product_attributes = Yii::$app->db->createCommand('SELECT product_category_attributes.*, 
							product_category_attribute_groups.name, product_category_attribute_groups.required
							FROM product_category_attributes LEFT JOIN product_category_attribute_groups 
							ON product_category_attribute_groups.attribute_group_id = product_category_attributes.attribute_group_id
							WHERE product_category_attribute_groups.attribute_group_id IN (' . $attribute_ids . ')')
            ->queryAll();

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

              if (!$attr_val) {
                if (!$attributes_arr[$attr_id][0]['required']) {
                  continue;
                } else {
                  $this->addError($k, Yii::t('app', 'required_field'));
                }
              }

              $pa = new ProductAttributes();
              $product->attributes_list .= ',' . $attr_val['url'];

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
        }

        if ($this->errors) {
          $result['validated'] = false;
          $result['error'] = 'Ошибка сохранения: не все поля заполнены';
          return $result;
        }

        if (!empty($product->attributes_list)) {
          $product->attributes_list = substr($product->attributes_list, 1);
        }

        $new_images = [];

        $new_remote_images = array(
          'remote' => [],
          'local' => []
        );

        $insert = 'INSERT INTO images_hash (hash, url, user_id) VALUES ';
        $values = '';
        $to_delete = [];
        $error_images = [];
        Image::$driver = [Image::DRIVER_GD2];
        $imagine = Image::getImagine();

        foreach ($this->imgs as $key => $image) {
          $imgr = Helpers::uploadImage($imagine, $image, 'products', [], [], '', true, $this->image_config ? json_decode($this->image_config) : false);
          $new_images = array_merge($imgr['new'], $new_images);
          $error_images = array_merge($imgr['errors'], $error_images);
          $values .= $imgr['values'];
        }

        if ($this->image_order) {
          $order = json_decode($this->image_order, true);
          $order_arr = [];
          $found_imgs = [];

          $n = 0;

          $uploaded = $order['uploaded'];

          if ($uploaded) {
            foreach ($uploaded as $up => $ind) {
              if (in_array($up, $product->images)) {
                $real_ind = array_search($up, $product->images);

                if ($real_ind !== false) {
                  $order_arr[] = $real_ind;
                  $found_imgs[] = $up;
                  $n++;
                }
              }
            }
          }

          $to_upload = $order['to_upload'];
          $to_upload_arr = [];

          if ($to_upload) {
            foreach ($to_upload as $up => $ind) {
              if (isset($new_images_arr[$up])) {
                $real_url = $new_images_arr[$up];
                $real_ind = array_search($real_url[0], $product->images);

                if ($real_ind) {
                  $found_imgs[] = $real_url;
                  $to_upload_arr[$real_ind] = $ind;
                }
              }
            }
          }

          foreach ($product->images as $up) {
            if (!in_array($up, $found_imgs)) {
              $order_arr[] = $n;
              $n++;
            }
          }

          $arr = $product->images;
          array_multisort($order_arr, SORT_ASC, $arr);
          $product->images = $arr;
        }

        $product->images = $new_images;
        $product->error_images = $error_images;
        $product->remote_images = $new_remote_images;

        $curs_arr = Yii::$app->session->get('curs_values');

        if ($curs_arr) {
          $curs = $curs_arr['vals'];
          $scale = $curs_arr['scales'];

          if (isset($curs[$product->currency]) && isset($scale[$product->currency]) && $product->currency != 'BYN') {
            $product->byn_price = $product->price * ($curs[$product->currency] / $scale[$product->currency]);
          } else {
            $product->byn_price = $product->price;
          }

          if ($product->currency != 'RUB') {
            if (isset($curs['RUB']) && isset($scale['RUB'])) {
              $product->rub_price = $product->byn_price / ($curs['RUB'] / $scale['RUB']);
            }
          } else {
            $product->rub_price = $product->price;
          }
        }

        $product->partnum_orig = $product->partnum;
        $product->partnum = preg_replace('/[^A-Za-z0-9\-]/', '', $product->partnum);

        if ($product->save()) {
          if ($values) {
            Yii::$app->db
              ->createCommand($insert . substr($values, 1) . ';')
              ->execute();
          }

          Yii::$app->db
            ->createCommand('DELETE FROM product_attributes WHERE product_id=' . $product->id)
            ->execute();

          foreach ($target_attributes as $pa) {
            $pa->product_id = $product->id;
            $pa->save();
          }

          $str = '';

          foreach ($to_delete as $td) {
            $str .= ',"' . $td . '"';

            if (file_exists(Yii::$app->basePath . $td)) {
              unlink(Yii::$app->basePath . $td);
            }

            $dirname = Yii::$app->basePath . '/web/gallery/tmp/products';

            if (file_exists($dirname)) {
              $image_name = explode('.', basename($td));
              $image_name = $image_name[0];
              array_map('unlink', glob($dirname . '/' . $image_name . '*'));
            }
          }

          if ($str) {
            Yii::$app->db
              ->createCommand('DELETE FROM images_hash 
                  WHERE url IN (' . substr($str, 1) . ') AND user_id=' . $user->id)
              ->execute();
          }

          $result['validated'] = true;
          $result['success'] = true;
          return $result;
        } else {
          foreach ($new_images as $img) {
            unlink(Yii::$app->basePath . $img);
          }

          $result['errors'] = $product->errors;
          $result['error'] = 'Ошибка сохранения: товар не сохранен';
        }
      } else {
        $result['error'] = 'Ошибка сохранения: неверные данные товара';
      }
    } else {
      $result['validated'] = false;
      foreach($errors as $key => $val) $result['errors'][$key] = $val;
      foreach($this->errors as $key => $val) $result['errors'][$key] = $val;
    }

    if (isset($result['validated']) && !$result['validated']) {
      $result['errors']['general'] = 'Ошибка сохранения: не все поля заполнены верно';
    }

    return $result;
  }
}
