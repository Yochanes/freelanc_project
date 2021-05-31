<?php

namespace app\models\forms;

use Yii;
use yii\base\Model;

use app\models\Products;
use app\models\products\ProductAttributes;
use app\models\products\ProductGroups;
use app\models\helpers\Helpers;
use yii\imagine\Image;

class ProductSellForm extends Model
{
  public $product_group_id;
  public $product_id;
  public $category;
  public $category_name;
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
  public $draft;
  public $seller_name;
  public $seller_phone;
  public $seller_email;
  public $contact_type;
  public $address;
  public $image_config;
  public $image_order;
  public $no_save;

  public function rules()
  {
    return [
      [['city', 'quantity', 'product_group_id', 'price', 'currency'], 'required', 'message' => Yii::t('app', 'required_field')],
      ['category', 'required', 'when' => function ($model, $attribute) { return empty($model->text); }, 'message' => Yii::t('app', 'required_field')],
      [
        ['seller_phone'],
        'required',
        'when' => function ($model, $attribute) {
          if (!Yii::$app->user->isGuest) {
            if (Yii::$app->request->post('username') && Yii::$app->request->post('email')) {
              return false;
            }
          }

          return !Yii::$app->user->isGuest;
        },
        'message' => Yii::t('app', 'required_field')],
      [['sku', 'partnum', 'make', 'model', 'city', 'generation'], 'string', 'max' => 100, 'tooLong' => 'Длина поля должна быть не более 100 символов'],
      [['short_description', 'seller_name', 'address'], 'string', 'max' => 255, 'tooLong' => 'Длина поля должна быть не более 255 символов'],
      [['currency', 'year'], 'string', 'max' => 30, 'tooLong' => 'Длина поля должна быть не более 30 символов'],
      [['seller_phone'], 'string', 'max' => 20, 'tooLong' => 'Длина поля должна быть не более 20 символов'],
      ['contact_type', 'in', 'range' => ['Телефон', 'Viber', 'Telegram', 'Почта']],
      [['text'], 'string', 'max' => 1000, 'tooLong' => 'Длина поля должна быть не более 1000 символов'],
      [['available'], 'boolean'],
      [['image_config', 'image_order'], 'string', 'skipOnEmpty' => true],
      ['category_name', 'string', 'max' => 255, 'skipOnEmpty' => true],
      ['seller_email', 'email', 'message' => 'Адрес почты указан некорректно. Пожалуйста, проверьте написание адреса'],
      [['price'], 'number', 'min' => '1', 'max' => 9999999999, 'tooSmall' => 'Цена не может быть ниже 1', 'tooBig' => 'Цена не может превышать 9999999999'],
      [['sale'], 'integer', 'min' => '0', 'max' => 100, 'tooSmall' => 'Скидка не может быть ниже 0', 'tooBig' => 'Скидка не может превышать 100'],
      ['status', 'integer', 'min' => 0, 'max' => 4],
      ['quantity', 'integer', 'min' => 0, 'max' => 999999, 'tooSmall' => 'Скидка не может быть ниже 0', 'tooBig' => 'Скидка не может превышать 999999'],
      ['imgs', 'each', 'rule' => ['file', 'extensions' => 'png, jpg, jpeg, gif']],
      ['imgs_to_delete', 'each', 'rule' => ['string'], 'skipOnEmpty' => true],
      [['width', 'height', 'length', 'weight'], 'double', 'min' => 0, 'max' => 999999],
      [['draft', 'no_save'], 'boolean'],
      ['product_id', 'integer']
    ];
  }

  public function saveData()
  {

    $this->draft = $this->draft == '1';
    $result = array();
    $product = false;
    $user = Yii::$app->user->identity;
    $errors = [];

    $group = ProductGroups::find()
      ->where(['product_group_id' => $this->product_group_id])
      ->one();

    if (!$group) {
      $result['validated'] = false;
      $result['error'] = 'Ошибка: группа товаров не существует';
      return $result;
    }

    $gr_validate = $group->validateData(Yii::$app->request->post());

    if (!$gr_validate['validated']) {
      $result['validated'] = false;
      $errors = $gr_validate['errors'];
      $result['error'] = $gr_validate['error'];
    }

    if (!$this->imgs && !$this->product_id) {
      $errors['imgs'] = 'Фото товара обязательно';
      $result['error'] = true;
    }

    $validated = $this->validate() && !$errors;
    $category = false;

    if ($this->category) {
      $category = \app\models\products\Categories::find()
        ->where('category_id="' . $this->category . '" OR url="' . $this->category . '"')
        ->one();

      if ($category) {
        $valid = $category->validateData(Yii::$app->request->post());

        if (!$valid['validated'] && !$this->draft) {
          $result['validated'] = false;
          $result['error'] = 'Ошибка: не все поля заполнены верно';
          foreach ($valid['errors'] as $key => $val) $errors[$key] = $val;
        }
      }
    }

    if ($validated || $this->draft) {
      if (!empty($this->product_id)) {
        Yii::$app->session->remove('draft');
        $product = Products::findOne(['id' => $this->product_id, 'user_id' => $user->id]);

        if ($product) {
          $product->date_updated = date("Y-m-d H:i:s");
          if (!$this->draft && ($product->status == Products::STATE_DRAFT || $product->status == Products::STATE_INACTIVE)) {
            $product->status = Products::STATE_ACTIVE;
          }

          if (!$this->imgs && !$product->images || (!$this->imgs && $this->imgs_to_delete && count($this->imgs_to_delete) == count($product->images))) {
            $this->addError('imgs', 'Фото товара обязательно');
            $result['error'] = "Не все поля заполнены верно";
            $result['validated'] = false;
            $validated = false;
            if (!$this->draft) return $result;
          }
        }
      } else {
        $product = new Products();
        $product->status = !$this->draft ? Products::STATE_ACTIVE : Products::STATE_DRAFT;
        $product->user_id = $user->id;
        $product->group_id = $this->product_group_id;
      }

      if ($product) {
        if ($product->load(Yii::$app->request->post(), '')) {
          if ($this->draft) {
            if ($validated) {
              $product->status = Products::STATE_INACTIVE;
            } else {
              $product->status = Products::STATE_DRAFT;
            }
          }

          if (is_null($product->price) || !$product->price) $product->price = 0;

          $product->name_template = $group->product_name_template;
          $product->url_template = $group->product_url_template;

          if ($category) {
            $product->category = $category->parent_url ? $category->parent_url : $category->url;
            $product->category_val = $this->category_name ? $this->category_name : $category->name;
          }

          if (!$this->address && !$product->address && $product->city == $user->city) {
            $product->address = $user->details->address;
          }

          if (!$this->seller_name) {
            $product->seller_name = $user->name;
          }

          if ($this->seller_phone) {
            if (!in_array($this->seller_phone, $user->contacts->phones)) {
              $product->seller_phone = $user->username;
            }
          } else {
            $product->seller_phone = $user->username;
          }

          $product->seller_email = $user->contacts->email;

          $attributes_arr = [];
          $target_attributes = [];
          $make = false;
          $model = false;
          $generation = false;
          $category = false;
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

          if (is_numeric($product->category)) {
            $category = Yii::$app->db
              ->createCommand('SELECT `url`, `name`, `parent_url` FROM product_categories 
                WHERE category_id="' . $product->category . '"')
              ->queryOne();

            if ($category) {
              $product->category = $category['parent_url'] ? $category['parent_url'] : $category['url'];
              $product->category_val = $this->category_name ? $this->category_name : $category['name'];
            }
          }

          $attribute_ids = '';

          foreach ($group->attribute_groups as $key => $val) {
            if (strpos($key, 'attribute_') !== false) {
              $exp = explode('_', $key);
              $attribute_ids .= ',' . $exp[1];
            }
          }

          if ($attribute_ids) {
            $attribute_ids = substr($attribute_ids, 1);

            $product_attributes = Yii::$app->db
              ->createCommand(
                'SELECT product_category_attributes.*, 
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

          if ($this->errors && !$this->draft) {
            $result['validated'] = false;
            $result['error'] = 'Ошибка сохранения: не все поля заполнены';
            foreach($errors as $key => $val) $this->addError($key, $val);
            return $result;
          }

          if (!empty($product->attributes_list)) {
            $product->attributes_list = substr($product->attributes_list, 1);
          }

          $new_remote_images = array(
            'remote' => [],
            'local' => []
          );

          $new_images = [];
          $error_images = [];
          $insert = 'INSERT INTO images_hash (hash, url, user_id) VALUES ';
          $values = '';
          $to_delete = [];
          Image::$driver = [Image::DRIVER_GD2];
          $imagine = Image::getImagine();
          $new_images_arr = [];
          $k = 0;

          foreach ($this->imgs as $key => $image) {
            $imgr = Helpers::uploadImage($imagine, $image, 'products', [], [], '', true, $this->image_config ? json_decode($this->image_config) : false);
            $new_images = array_merge($imgr['new'], $new_images);
            $error_images = array_merge($imgr['errors'], $error_images);
            $values .= $imgr['values'];

            if (!$imgr['errors']) {
              $new_images_arr[$image->name] = $imgr['new'];
            }
          }

          $current_images = $product->images;
          $remote_images = $product->remote_images;

          if (!is_null($current_images)) {
            foreach ($current_images as $ci) {
              if (is_null($this->imgs_to_delete) || !in_array($ci, $this->imgs_to_delete)) {
                array_unshift($new_images, $ci);
                $key = array_search($ci, $remote_images->local);

                if ($key) {
                  $new_remote_images['local'][] = $ci;
                  $new_remote_images['remote'][] = $remote_images->remote[$key];
                }
              } else {
                $end = explode('/', $ci);
                $end = end($end);
                $to_delete[] = $ci;
              }
            }
          }

          $product->images = $new_images;

          if ($this->image_config) {
            $config = json_decode($this->image_config);

            if ($config) {
              if ($config->uploaded) {
                foreach ($config->uploaded as $key => $rotated) {
                  if (in_array($key, $product->images)) {
                    $imagine->open(Yii::$app->basePath . $key)
                      ->rotate($rotated->rotate)
                      ->save(Yii::$app->basePath . $key);

                    $dirname = Yii::$app->basePath . '/web/gallery/tmp/products';

                    if (file_exists($dirname)) {
                      $image_name = explode('.', basename($key));
                      $image_name = $image_name[0];
                      array_map('unlink', glob($dirname . '/' . $image_name . '*'));
                    }

                    $dirname = Yii::$app->basePath . '/web/gallery/tmp/images';

                    if (file_exists($dirname)) {
                      $image_name = explode('.', basename($key));
                      $image_name = $image_name[0];
                      array_map('unlink', glob($dirname . '/' . $image_name . '*'));
                    }
                  }
                }
              }
            }
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

          $product->remote_images = $new_remote_images;
          $product->error_images = $error_images;
          $curs_obj = $user->curs;
          $curs = false;
          $scale = false;

          if (!$curs_obj) {
            $curs_obj = Yii::$app->session->get('curs_values');
            $curs = $curs_obj['vals'];
            $scale = $curs_obj['scales'];
          } else {
            $curs = $curs_obj->curs_values;
            $scale = $curs_obj->curs_scales;
          }

          if ($curs && $scale) {
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

          if ($error_images) {
            $result['error'] = 'Некоторые изображения уже используются в других ваших объявлениях';
            $result['error_images'] = $error_images;
            foreach($errors as $key => $val) $this->addError($key, $val);
            return $result;
          }

          $product->partnum_orig = $product->partnum;
          $product->partnum = preg_replace('/[^A-Za-z0-9\-]/', '', $product->partnum);

          if ($this->no_save) {
            $result['validated'] = true;
            $result['success'] = true;
            foreach($errors as $key => $val) $this->addError($key, $val);
            return $result;
          }

          if ($product->save(!$this->draft)) {
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
            $result['product_id'] = $product->id;
            Yii::$app->session->set('last_product_id', $product->id);

            foreach($errors as $key => $val) {
              $this->addError($key, $val);
            }

            return $result;
          } else {
            foreach ($new_images as $img) {
              unlink(Yii::$app->basePath . $img);
            }

            $result['errors'] = $product->errors;

            foreach($errors as $key => $val) {
              $this->addError($key, $val);
            }

            $result['error'] = 'Ошибка сохранения: товар не сохранен';
          }
        } else {
          foreach($errors as $key => $val) {
            $this->addError($key, $val);
          }

          $result['error'] = 'Ошибка сохранения: неверные данные товара';
        }
      } else {
        foreach($errors as $key => $val) {
          $this->addError($key, $val);
        }

        $result['error'] = 'Ошибка сохранения: товар не найден';
      }
    } else {
      foreach($errors as $key => $val) {
        $this->addError($key, $val);
      }
    }

    return $result;
  }
}
