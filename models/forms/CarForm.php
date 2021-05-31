<?php

namespace app\models\forms;

use Yii;
use yii\base\Model;
use app\models\user\Cars;
use app\models\user\CarAttributes;
use app\models\products\ProductGroups;

use yii\imagine\Image;

class CarForm extends Model
{
  public $car_id;
  public $make;
  public $model;
  public $generation;
  public $year;
  public $vin;
  public $razborka;
  public $imgs;
  public $imgs_to_delete;

  public function rules()
  {
    return [
      [['make', 'model', 'year'], 'required', 'message' => 'Это поле должно быть заполнено обязательно'],
      [['make', 'model', 'generation', 'year'], 'string', 'max' => 100, 'tooLong' => 'Длина этого поля не может превышать 100 символов'],
      [['razborka', 'vin'], 'string', 'max' => 255, 'tooLong' => 'Длина этого поля не может превышать 255 символов'],
      ['imgs', 'file', 'extensions' => 'png, jpg, jpeg, gif', 'skipOnEmpty' => true],
      ['imgs_to_delete', 'each', 'rule' => ['string'], 'skipOnEmpty' => true],
      [['car_id'], 'integer']
    ];
  }

  public function saveData()
  {
    $result = array();
    $car = false;
    $user = Yii::$app->user->identity;

    if (!empty($this->car_id)) {
      $car = Cars::findOne(['id' => $this->car_id, 'user_id' => $user->id]);
    } else {

      $where = 'user_id=' . $user->id;
      $and_where = '';
      $mod = new Cars();

      foreach ($this->attributes as $akey => $aval) {
        if ($akey !== 'imgs' && isset($mod->{$akey}) && $aval) {
          $and_where .= ' AND ' . $akey . '="' . $aval . '"';
        }
      }

      if ($and_where) {
        $check = Cars::find()->where($where . $and_where)->one();

        if ($check) {
          $car = $check;
        }
      }

      if (!$car) {
        $car = new Cars();
        $car->user_id = $user->id;
      }
    }

    if (!$car) {
      $result['validated'] = false;
      $result['error'] = 'Ошибка сохранения: авто не найдено';
      return $result;
    }

    $group = ProductGroups::find()
      ->where(['use_in_car_form' => 1])
      ->one();

    if (!$group) {
      $result['validated'] = false;
      $result['error'] = 'Ошибка: группа товаров не существует';
      return $result;
    }

    $gr_validate = $group->validateData(Yii::$app->request->post(), 'use_in_car_form');

    if (!$gr_validate['validated']) {
      $result['validated'] = false;
      $errors = $gr_validate['errors'];
      $result['error'] = $gr_validate['error'];
    }

    if ($car->load(Yii::$app->request->post(), '')) {
      $make = false;
      $model = false;
      $generation = false;
      $year = false;

      if ($this->validate()) {
        if ($car->make) {
          $make = Yii::$app->db
            ->createCommand('SELECT name FROM makes WHERE url="' . $car->make . '"')
            ->queryOne();

          if ($make) $car->make_val = $make['name'];
        }

        if ($car->model && $make) {
          $model = Yii::$app->db
            ->createCommand('SELECT name FROM make_models WHERE url="' . $car->model . '" AND make_url="' . $car->make . '"')
            ->queryOne();

          if ($model) $car->model_val = $model['name'];
        }

        if ($car->generation && $model) {
          $generation = Yii::$app->db
            ->createCommand('SELECT name FROM make_generations WHERE url="' . $car->generation . '" AND model_url="' . $car->model . '"')
            ->queryOne();

          if ($generation) $car->generation_val = $generation['name'];
        }

        if ($car->year) {
          $year = explode('_', $car->year);
          $year = end($year);
          if ($year) $car->year_val = $year;
        }

        $attribute_ids = '';

        foreach ($group->attribute_groups as $key => $val) {
          if (strpos($key, 'attribute_') !== false) {
            $exp = explode('_', $key);
            $attribute_ids .= ',' . $exp[1];
          }
        }

        $to_delete = [];
        Image::$driver = [Image::DRIVER_GD2];
        $imagine = Image::getImagine();
        $img = '';

        $image = $this->imgs;

        if ($image) {
          $name = 'cars_' . $user->id . '_' . Yii::$app->security->generateRandomString(20) . '.' . $image->extension;

          if ($image->saveAs('gallery/tmpupload/' . $name, true)) {
            $tmppath = Yii::$app->basePath . '/web/gallery/tmpupload/' . $name;
            $regpath = Yii::$app->basePath . '/web/gallery/cars/' . $name;

            $dimensions = getimagesize($tmppath);

            if ($dimensions[0] <= 640 || $dimensions[1] <= 480) {
              $imagine
                ->open($tmppath)
                ->save($regpath, ['quality' => 30]);
            } else {
              Image::resize($tmppath, 640, 480)
                ->save($regpath, ['quality' => 30]);
            }

            $img = '/web/gallery/cars/' . $name;
            unlink($tmppath);
            $car->image = $img;
          }
        }

        if (!empty($this->imgs_to_delete)) {
          $current_images = $car->image;
          $to_delete[] = $current_images;
        }

        if ($car->save()) {
          if ($attribute_ids) {
            $target_attributes = [];
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

                $pa = new CarAttributes();

                if ($pa->load(array(
                  'car_id' => $car->id,
                  'attribute_group_id' => $attr_val['attribute_group_id'],
                  'name' => $attr_val['name'],
                  'value' => $attr_val['value'],
                  'url' => $attr_val['url']
                ), '')) {
                  $target_attributes[] = $pa;
                }
              }
            }
          }

          Yii::$app->db
            ->createCommand('DELETE FROM user_cars_attributes WHERE car_id=' . $car->id)
            ->execute();

          foreach ($target_attributes as $pa) $pa->save();

          foreach ($to_delete as $td) {
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

          $result['validated'] = true;
          $result['success'] = true;
          return $result;
        } else {
          $result['error'] = 'Ошибка сохранения: товар не сохранен';
          $result['errors'] = $car->errors;
        }
      }

      $result['error'] = 'Ошибка сохранения: не все поля заполнены корректно';;
    } else {
      $result['error'] = 'Ошибка сохранения: неверные данные авто';
    }

    return $result;
  }
}
