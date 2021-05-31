<?php

namespace app\models\forms;

use Yii;
use yii\base\Model;
use app\models\Requests;
use yii\web\UploadedFile;

use app\models\products\RequestAttributes;

use yii\imagine\Image;

class RequestForm extends Model
{
  public $make;
  public $model;
  public $year;
  public $country;
  public $city;
  public $category;
  public $generation;
  public $text;
  public $preorder;
  public $inform;
  public $imgs;
  public $imgs_to_delete;
  public $contact_type;
  public $product_id;
  public $seller_name;
  public $seller_phone;
  public $seller_email;

  public function rules()
  {
    return [
      [['city', 'country'], 'required', 'message' => Yii::t('app', 'required_field')],
      ['category', 'required', 'when' => function ($model, $attribute) { return empty($model->text); }, 'message' => Yii::t('app', 'required_field')],
      ['text', 'required', 'when' => function ($model, $attribute) { return empty($model->category); }, 'message' => Yii::t('app', 'required_field')],
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
      [['make', 'model', 'city', 'generation', 'country'], 'string', 'max' => 100, 'tooLong' => 'Длина этого поля не может превышать 100 символов'],
      ['contact_type', 'in', 'range' => ['Телефон', 'Viber', 'Telegram', 'Почта']],
      [['seller_phone'], 'string', 'max' => 20, 'tooLong' => 'Длина поля должна быть не более 20 символов'],
      [['text'], 'string', 'max' => 1000, 'tooLong' => 'Длина этого поля не может превышать 1000 символов'],
      ['seller_email', 'email', 'message' => 'Адрес почты указан некорректно. Пожалуйста, проверьте написание адреса'],
      [['category', 'seller_name'], 'string', 'max' => 255, 'tooLong' => 'Длина этого поля не может превышать 255 символов'],
      [['preorder', 'inform'], 'boolean'],
      [['year'], 'string', 'max' => 30, 'tooLong' => 'Длина поля должна быть не более 30 символов'],
      ['imgs', 'each', 'rule' => ['file', 'extensions' => 'png, jpg, jpeg, gif'], 'skipOnEmpty' => true],
      ['imgs_to_delete', 'each', 'rule' => ['string'], 'skipOnEmpty' => true],
      [['product_id'], 'integer']
    ];
  }

  public function saveData($product_group)
  {
    $result = array(
      'errors' => [],
      'error' => true
    );

    $this->imgs = UploadedFile::getInstancesByName('imgs');
    $result['messages'] = array();
    $errors = [];

    if (!$product_group) {
      $result['validated'] = false;
      $result['error'] = 'Ошибка: группа товаров не существует';
      return $result;
    }

    $gr_validate = $product_group->validateData(Yii::$app->request->post(), false, false);

    if (!$gr_validate['validated']) {
      $result['validated'] = false;
      $errors = $gr_validate['errors'];
      $result['error'] = $gr_validate['error'];
    }

    $user = Yii::$app->user->identity;

    if (isset($errors['category']) && $errors['category'] && $this->text) {
      unset($errors['category']);

      if (empty($errors)) {
        $result['validated'] = true;
        $result['error'] = false;
      }
    }

    if ($this->validate() && !$errors) {
      $result = ['error' => false];
      $product = false;

      if (!empty($this->product_id) && $user) {
        $product = Requests::findOne(['id' => $this->product_id, 'user_id' => $user->id]);
        $product->date_updated = date("Y-m-d H:i:s");
      } else {
        $product = new Requests();
        $product->group_id = $product_group->product_group_id;
      }

      if ($product) {
        if ($product->load(Yii::$app->request->post(), '')) {
          if ($product->category) {
            $category = \app\models\products\Categories::find()
              ->where('category_id="' . $product->category . '" OR url="' . $product->category . '"')
              ->one();

            if ($category) {
              $product->category = $category['url'];
              $product->category_val = $category['name'];

              $valid = $category->validateData(Yii::$app->request->post());

              if (!$valid['validated']) {
                $result['error'] = 'Ошибка: не все поля заполнены верно';
                $result['errors'] = $valid['errors'];
                return $result;
              }
            }
          }

          if (Yii::$app->user->isGuest) {
            $mod = new \app\models\forms\RegisterForm();
            $res = $mod->create();

            if ($res['error']) {
              return $res;
            } else {
              return ['success' => true, 'errors' => []];
            }
          } else {
            $user = Yii::$app->user->identity;
          }

          $product->user_id = $user->id;
          $product->country_id = $this->country;

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
              ->createCommand('SELECT name FROM make_models 
                WHERE url="' . $product->model . '" AND make_url="' . $product->make . '"')
              ->queryOne();

            if ($model) $product->model_val = $model['name'];
          }

          if ($product->generation && $model) {
            $generation = Yii::$app->db
              ->createCommand('SELECT name, years FROM make_generations 
                WHERE url="' . $product->generation . '" AND model_url="' . $product->model . '"')
              ->queryOne();

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
            $city = Yii::$app->db
              ->createCommand('SELECT name, domain FROM cities 
                WHERE name="' . $product->city . '"')
              ->queryOne();

            if ($city) {
              $product->city = $city['name'];
              $product->city_domain = $city['domain'];
            }
          }

          if ($product->category) {
            $category = Yii::$app->db
              ->createCommand('SELECT url, name FROM product_categories 
                WHERE category_id="' . $product->category . '" OR url="' . $product->category . '"')
              ->queryOne();

            if ($category) {
              $product->category = $category['url'];
              $product->category_val = $category['name'];
            }
          }

          Image::$driver = [Image::DRIVER_GD2];
          $imagine = Image::getImagine();
          $new_images = [];
          $to_delete = [];

          foreach ($this->imgs as $key => $image) {
            $name = $user->id . Yii::$app->security->generateRandomString(12) . '.' . $image->extension;

            if ($image->saveAs('gallery/tmpupload/' . $name, true)) {
              $tmppath = Yii::$app->basePath . '/web/gallery/tmpupload/' . $name;
              $regpath = Yii::$app->basePath . '/web/gallery/requests/' . $name;

              $dimensions = getimagesize($tmppath);

              if ($dimensions[0] <= 640 || $dimensions[1] <= 480) {
                $imagine
                  ->open($tmppath)
                  ->save($regpath, ['quality' => 40]);
              } else {
                Image::resize($tmppath, 640, 480)
                  ->save($regpath, ['quality' => 40]);
              }

              $new_images[] = '/web/gallery/requests/' . $name;
              unlink($tmppath);
            }
          }

          $current_images = $product->images;

          if (!is_null($current_images)) {
            foreach ($current_images as $ci) {
              if ($this->imgs_to_delete && !is_null(!$this->imgs_to_delete) && !in_array($ci, $this->imgs_to_delete)) {
                array_unshift($new_images, $ci);
              } else {
                $end = explode('/', $ci);
                $end = end($end);

                $used = Yii::$app->db
                  ->createCommand('SELECT COUNT(*) FROM products WHERE images LIKE "%' . $end . '%" AND user_id=' . $user->id . ' AND id!=' . $product->id)
                  ->queryScalar();

                if (!$used) {
                  $used = Yii::$app->db
                    ->createCommand('SELECT COUNT(*) FROM requests WHERE images LIKE "%' . $end . '%" AND user_id=' . $user->id)
                    ->queryScalar();
                }

                if (!$used) $to_delete[] = $ci;
              }
            }
          }

          $attributes_arr = [];
          $target_attributes = [];
          $attribute_ids = '';

          foreach ($product_group->attribute_groups as $key => $val) {
            if (strpos($key, 'attribute_') !== false) {
              $exp = explode('_', $key);
              $attribute_ids .= ',' . $exp[1];
            }
          }

          if ($attribute_ids) {
            $attribute_ids = substr($attribute_ids, 1);

            $product_attributes = Yii::$app->db
              ->createCommand('SELECT product_category_attributes.*, 
                  product_category_attribute_groups.name, product_category_attribute_groups.required
                  FROM product_category_attributes LEFT JOIN product_category_attribute_groups 
                  ON product_category_attribute_groups.attribute_group_id = product_category_attributes.attribute_group_id
                  WHERE product_category_attribute_groups.attribute_group_id IN (' . $attribute_ids . ')')
              ->queryAll();

            foreach ($product_attributes as $attr) {
              if (!isset($attributes_arr[$attr['attribute_group_id']])) {
                $attributes_arr[$attr['attribute_group_id']] = [];
              }

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

                $pa = new RequestAttributes();
                $product->attributes_list .= ',' . $attr_val['url'];

                if ($pa->load(array(
                  'request_id' => $product->id,
                  'name' => $attr_val['name'],
                  'value' => $attr_val['value'],
                  'url' => $attr_val['url']
                ), '')) {
                  $target_attributes[] = $pa;
                }
              }
            }
          }

          $product->images = $new_images;

          if (!empty($product->attributes_list)) {
            $product->attributes_list = substr($product->attributes_list, 1);
          }

          if ($product->save()) {
            Yii::$app->db
              ->createCommand('DELETE FROM request_attributes WHERE request_id=' . $product->id)
              ->execute();

            foreach ($target_attributes as $pa) {
              $pa->request_id = $product->id;
              $pa->save();
            }

            $str = '';

            foreach ($to_delete as $td) {
              $str .= ',"' . $td . '"';

              if (file_exists(Yii::$app->basePath . $td)) {
                unlink(Yii::$app->basePath . $td);
              }

              $dirname = Yii::$app->basePath . '/web/gallery/tmp/requests_' . $user->id;

              if (file_exists($dirname)) {
                $image_name = explode('.', basename($td));
                $image_name = $image_name[0];
                array_map('unlink', glob($dirname . '/' . $image_name . '*'));
              }
            }

            $result['error'] = false;
            $result['validated'] = true;
            $result['success'] = true;
            Yii::$app->session->setFlash('requestMsg', 'Ваша заявка успешно добавлена');
          } else {
            foreach ($new_images as $img) {
              unlink(Yii::$app->basePath . $img);
            }

            $result['errors']['general'] = 'Ошибка сохранения заявки';
          }
        } else {
          $result['errors']['general'] = 'Ошибка сохранения заявки';
        }
      } else {
        $result['errors']['general'] = 'Ошибка сохранения: заявка не найдена';
      }
    } else {
      foreach($errors as $key => $val) $this->addError($key, $val);
      $result['errors']['general'] = 'Ошибка сохранения: не все поля заполнены верно';
    }

    return $result;
  }
}
