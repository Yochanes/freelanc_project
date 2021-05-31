<?php

namespace app\models\products;

use Yii;
use yii\base\Model;
use yii\imagine\Image;

use app\models\Cities;
use app\models\Products;
use app\models\helpers\Helpers;

class UploadForm extends Model
{
  public $data;
  private $is_debug = true;

  public function rules()
  {
    return [
      [['data'], 'string']
    ];
  }

  public function saveData($group)
  {
    try {
      $result = array(
        'products' => array()
      );

      $user = Yii::$app->user->identity;

      if (!$user->city) {
        return ['error' => 'Город не указан в профиле'];
      }

      $city = Cities::find()->where(['name' => $user->city])->one();

      if (!$city) {
        return ['error' => 'Город отсутствует в базе данных'];
      }

      $images_uploaded = [];
      $config = $user->config;
      $this->debug(PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL);
      $skip = $config->skip_upload_errors;
      $product_ids = '';
      $last_product_id = 0;

      $model = new Products();

      if ($this->validate()) {
        $result['validated'] = true;
        $n = 0;
        $img_arr = [];
        $to_insert = array();
        $found_products = 0;
        $saved_products = 0;
        $duplicates = 0;
        $is_error = 0;
        $data = json_decode($this->data);
        $attr_rows = [];
        $empty_attrs = [];
        $urls = array();

        if (!is_array($data)) {
          $result['error'] = 'Ошибка: неверные данные';
        }

        $this->debug('Обработка данных массива [' . count($data) . ']');

        foreach ($data as $rowIndex => $row) {
          $product_error = false;
          // if (!isset($row->category) || !$row->category) continue;
          //if ($group->make_required && (!isset($row->make) || !$row->make)) continue;
          $found_products++;
          $this->debug('------------------Обработка данных товара ' . $found_products . '---------------------');
          $this->debug(print_r($row, TRUE));
          $attr_list = '';

          if (isset($row->attrs) && is_array($row->attrs)) {
            foreach ($row->attrs as $attr) $attr_list .= ',' . $attr->url;
            if (!empty($attr_list)) $attr_list = substr($attr_list, 1);
          }

          $product = false;

          if ((isset($row->id) && $row->id) || (isset($row->sku) && $row->sku)) {
            $product = Products::find()
              ->where('user_id="' . $user->id . '" AND ' . (isset($row->id) ? 'id="' . $row->id . '"' : 'sku="' . $row->sku . '"'))
              ->one();
          }

          /*
          $product = Products::find()
            ->where(
              (isset($row->make) ? 'make="' . $row->make . '"' : 'make IS NULL') .
              (isset($row->model) ? ' AND model="' . $row->model . '"' : ' AND model IS NULL') .
              (isset($row->generation) ? ' AND generation="' . $row->generation . '"' : ' AND generation IS NULL') .
              (isset($row->category) ? ' AND category="' . $row->category . '"' : ' AND category IS NULL') .
              (isset($row->year) ? ' AND year="' . $row->year . '"' : ' AND year IS NULL') .
              (isset($row->sku) ? ' AND sku="' . $row->sku . '"' : ' AND (sku IS NULL OR sku="")') .
              (isset($row->partnum) ? ' AND partnum="' . $row->partnum . '"' : ' AND (partnum IS NULL OR partnum="")') .
              ' AND group_id=' . $group->product_group_id .
              ($attr_list ? ' AND attributes_list="' . $attr_list . '"' : '') .
              ' AND user_id=' . $user->id .
              ' AND status!=' . Products::STATE_DELETED)
            ->one();


          $enc_data = json_decode(json_encode($row), true);

          if (!$product) {
            $product = new Products();
            $product->user_id = $user->id;
            $product->group_id = $group->product_group_id;
            $product_found = false;
            $this->debug('Товар не существует, создание нового');
          } else if ($config->skip_upload_duplicates) {
            $duplicates++;
            $this->debug('Обнаружен дубликат товара, пропускаем запись');
            continue;
          } else {
            $duplicates++;
            $this->debug('Товар уже существует');
          }

          */

          $enc_data = json_decode(json_encode($row), true);

          if (!$product) {
            $product = new Products();
            $product->user_id = $user->id;
            $product->group_id = $group->product_group_id;
            $product_found = false;
          } else {
            $duplicates++;
            $product_found = true;
          }

          $data_to_validate = $enc_data;

          if (isset($enc_data['attrs'])) {
            foreach ($enc_data['attrs'] as $enc_attr) {
              $data_to_validate['attribute_' . $enc_attr['attribute_group_id']] = $enc_attr;
            }
          }

          $gr_validate = $group->validateData($data_to_validate, 'generation', true);

          if (!$gr_validate['validated']) {
            if (!$skip) {
              if (!$product_error) {
                $is_error++;
                $product_error = true;
                $product->status = Products::STATE_DRAFT;
              }

              $this->debug('Валидация группы не успешна');
              /*
              $this->debug('---------GROUP NOT VALIDATED---------');
              $this->debug(print_r($data_to_validate, true));
              $this->debug(print_r($gr_validate, true));
              $this->debug('-------------------------------------');
              */
            } else {
              $this->debug('Валидация группы не успешна, пропускаем товар');
              /*
              $this->debug('---------GROUP NOT VALIDATED---------');
              $this->debug(print_r($data_to_validate, true));
              $this->debug(print_r($gr_validate, true));
              $this->debug('-------------------------------------');
              */
              continue;
            }

            $product->status = Products::STATE_DRAFT;
          }

          $product->seller_name = $user->display_name;
          $product->seller_phone = $user->username;
          $product->seller_email = $user->contacts->email;
          $product->name_template = $group->product_name_template;
          $product->url_template = $group->product_url_template;
          $product->city = $user->city;
          $product->city_domain = $city->domain;
          $product->address = $user->details->address;
          $product->country_id = $city->country_id;
          $product->date_updated = date('Y-m-d H:i:s');

          if ($product->load($enc_data, '')) {
            if (isset($row->city)) {
              $up_city = Cities::find()->where(['name' => $row->city])->one();

              if ($up_city) {
                $product->city = $up_city->name;
                $product->city_domain = $city->domain;
              }
            }

            $product->partnum_orig = $product->partnum;
            $product->partnum = preg_replace('/[^A-Za-z0-9\-]/', '', $product->partnum);

            $img_arr = [];
            $new_remote_images = [];
            $error_images = [];

            if ($product->years) {
              $split = explode('-', $product->years);

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

            $image_error = false;

            if (isset($row->image) && is_array($row->image) && $row->image) {
              $existing_images = [];

              if ($product_found) {
                foreach ($product->images as $image) {
                  $existing_images[] = $product->url . rtrim(basename($image), '/');
                }
              }

              foreach ($row->image as $url) {
                $base = basename($url);
                $ext = explode('.', $base);
                $ext = end($ext);
                $ext = strtolower($ext);

                if (!in_array($ext, ['gif', 'jpeg', 'jpg', 'png', 'wbmp', 'webp', 'xbm'])) {
                  $this->debug('Изображение [' . $url . '] имеет некорректное расширение, пропускаю...');
                  continue;
                }

                if (in_array($url, $existing_images)) {
                  $this->debug('Изображение [' . $url . '] уже привязано к товару, пропускаю...');
                  continue;
                }

                $sql_url = str_replace('\\', '\\\\\\\\', substr(json_encode($url), 1, -1));

                $remote_exists = Products::find()
                  ->where('remote_images like "%' . $sql_url . '%"' . ($product_found ? ' AND id!="' . $product->id . '"' : '') . 'user_id="' . $user->id . '"')
                  ->limit(1)
                  ->count();

                if ($remote_exists || in_array($url, $images_uploaded)) {
                  if (!$product_found) {
                    $product->status = Products::STATE_DRAFT;
                  }

                  if (!$image_error) {
                    $image_error = true;
                  }

                  if (!$product_error) {
                    $is_error++;
                    $product_error = true;
                  }

                  if (!isset($result['upload_errors'])) {
                    $result['upload_errors'] = [];
                  }

                  if (!isset($result['upload_errors'][$rowIndex])) {
                    $result['upload_errors'][$rowIndex] = [];
                  }

                  if (!isset($result['upload_errors'][$rowIndex]['error_images'])) {
                    $result['upload_errors'][$rowIndex]['error_images'] = [];
                  }

                  $result['upload_errors'][$rowIndex]['error_images'][] = $url;

                  $this->debug('Изображение [' . $url . '] уже существует, пропускаю...');
                  $error_images[] = $url;
                  continue;
                }

                $images_uploaded[] = $url;

                if ($product_found) {
                  $img_found = false;

                  foreach ($product->remote_images->remote as $key => $val) {
                    if ($val === $url) {
                      $new_remote_images[] = $url;
                      $img_arr[] = $product->remote_images->local[$key];
                      $img_found = true;
                      break;
                    }
                  }

                  if ($img_found) {
                    continue;
                  }

                  foreach ($product->images as $val) {
                    if (strpos($url, $val) !== false) {
                      $img_found = true;
                      break;
                    }
                  }

                  if ($img_found) {
                    continue;
                  }
                }

                if (!$this->url_exists($url)) {
                  if (!isset($result['upload_errors'])) {
                    $result['upload_errors'] = [];
                  }

                  if (!isset($result['upload_errors'][$rowIndex])) {
                    $result['upload_errors'][$rowIndex] = [];
                  }

                  if (!isset($result['upload_errors'][$rowIndex]['error_images'])) {
                    $result['upload_errors'][$rowIndex]['error_images'] = [];
                  }

                  $result['upload_errors'][$rowIndex]['error_images'][] = $url;

                  $this->debug('Изображение [' . $url . '] не найдено, пропускаю...');
                  continue;
                }

                $new_remote_images[] = $url;
                $img_name = explode('.', basename(strtolower($url)));
                $img_token = $user->id . Yii::$app->security->generateRandomString(12);
                $img_name = $img_token . '.' . end($img_name);
                $image_path = '/web/gallery/products/' . $img_name;

                $urls[] = array(
                  'dest' => Yii::$app->basePath . $image_path,
                  'tmpdest' => Yii::$app->basePath . '/web/gallery/tmpupload/' . $img_name,
                  'url' => $url,
                  'path' => $image_path
                );

                $img_arr[] = $image_path;
              }
            } else if (!$product_found || !$product->images) {
              if (!$product_error) {
                $is_error++;
                $product_error = true;
                $product->status = Products::STATE_DRAFT;
              }

              $this->debug('Изображения отсутствуют, добавляю ошибку');
            }

            if ($product_found) {
              foreach ($product->remote_images->remote as $key => $val) {
                if (!in_array($val, $new_remote_images)) {
                  if (file_exists(Yii::$app->basePath . $product->remote_images->local[$key])) {
                    unlink(Yii::$app->basePath . $product->remote_images->local[$key]);
                  }

                  $dirname = Yii::$app->basePath . '/web/gallery/tmp/products';

                  if (file_exists($dirname)) {
                    $image_name = explode('.', basename($product->remote_images->local[$key]));
                    $image_name = $image_name[0];
                    array_map('unlink', glob($dirname . '/' . $image_name . '*'));
                  }

                  /*
                  Yii::$app->db
                    ->createCommand('DELETE FROM images_hash 
                    WHERE url="' . $product->remote_images->local[$key] . '" 
									  AND user_id=' . Yii::$app->user->identity->id)
                    ->execute();
                  */
                }
              }
            }

            if ($error_images) {
              $product->error_images = $error_images;
            }

            $product->remote_images = array(
              'remote' => $new_remote_images,
              'local' => $img_arr
            );

            if ($product_found) {
              $product->images = array_merge($product->images, $img_arr);
            } else {
              $product->images = $img_arr;
            }

            if (!$product->images) {
              $product->status = Products::STATE_DRAFT;

              if (!$product_error) {
                $is_error++;
                $product_error = true;
              }

              $this->debug('Изображения в итоговом товаре отсутствуют, добавляю ошибку');
            }

            if ($attr_list) {
              $product->attributes_list = $attr_list;
            }

            if ((!$product->price || !is_numeric($product->price))) {
              $product->price = null;

              if (!$skip) {
                $product->status = Products::STATE_DRAFT;
                $this->debug('Цена отсутствует, добавляю ошибку');

                if (!$product_error) {
                  $product_error = true;
                  $is_error++;
                }
              } else {
                $this->debug('Цена отсутствует, пропускаю...');
                continue;
              }
            }

            if (isset($row->currency) && in_array(strtoupper($row->currency), ['USD', 'BYN', 'RUB', 'EUR'])) {
              $product->currency = $row->currency;
              $this->debug('Устанавливаю валюту товара ' . $row->currency);
            }

            if (!$product->currency) {
              if (!$skip) {
                $product->status = Products::STATE_DRAFT;
                $this->debug('Валюта отсутствует [валюта="' . $product->currency . '"], добавляю ошибку');

                if (!$product_error) {
                  $product_error = true;
                  $is_error++;
                }
              } else {
                $this->debug('Валюта отсутствует [валюта="' . $product->currency . '"], пропускаем товар');
                continue;
              }
            }

            if ($product->price && $product->currency) {
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
                if (isset($curs[$product->currency]) && isset($scale[$product->currency])) {
                  $product->byn_price = $product->price * ($curs[$product->currency] / $scale[$product->currency]);
                } else {
                  $product->byn_price = $product->price;
                }

                if ($product->currency != 'RUB') {
                  if (isset($curs['RUB']) && isset($scale['RUB'])) {
                    $product->rub_price = ($product->byn_price / $curs['RUB']) * $scale['RUB'];
                  }
                } else {
                  $product->rub_price = $product->price;
                }
              }
            }

            $cat_valid = ['validated' => true];

            if ($product->category) {
              $category = \app\models\products\Categories::find()
                ->where('category_id="' . $product->category . '" OR url="' . $product->category . '"')
                ->cache(3600)
                ->one();

              if ($category) {
                $attr_arr = [];

                if (isset($row->attrs)) {
                  foreach ($row->attrs as $attr) {
                    $attr_arr['attribute_' . $attr->attribute_group_id] = $attr->attribute_group_id;
                  }
                }

                $cat_valid = $category->validateData($attr_arr, 'attribute_', true);

                if (!$cat_valid['validated']) {
                  if (!$skip) {
                    $this->debug('Валидация категории не успешна');

                    if (!$product_error) {
                      $product->status = Products::STATE_DRAFT;
                      $product_error = true;
                      $is_error++;
                    }

                    $this->debug('Валидация категории не успешна');

                    //$this->debug('--------CATEGORY NOT VALIDATED---------');
                    //$this->debug(print_r($attr_arr, true));
                    //$this->debug(print_r($cat_valid, true));
                    //$this->debug('---------------------------------------');

                  } else {
                    $this->debug('Валидация категории не успешна, пропускаем товар');
                    /*
                    $this->debug('--------CATEGORY NOT VALIDATED---------');
                    $this->debug(print_r($attr_arr, true));
                    $this->debug(print_r($cat_valid, true));
                    $this->debug('---------------------------------------');
                    */
                    continue;
                  }
                }
              }
            }

            if (isset($row->id) && $row->id && $product_found) {
              $product->id = $row->id;
            }

            if ($product->save()) {
              $saved_products++;

              if ($last_product_id && $product->id - $last_product_id > 1) {
                $this->debug('Внимание: ID записи нового товара отличается от предыдущего более чем на 1 [' . $last_product_id . ' - ' . $product->id . ']');
              }

              $last_product_id = $product->id;
              $product_ids .= $product->id . ',';
              $this->debug('Товар ' . $found_products . ' - ' . $saved_products . ' успешно сохранен. ID ' . $product->id);

              if ($saved_products != $found_products) {
                $this->debug('Внимание: порядновый номер сохраненного товара не соответствует порядковому номеру товара');
              }

              Yii::$app->db
                ->createCommand('DELETE FROM product_attributes WHERE product_id=' . $product->id)
                ->execute();

              if (isset($row->attrs) && is_array($row->attrs)) {
                foreach ($row->attrs as $attr) {
                  $attribute = new ProductAttributes();
                  $attribute->product_id = $product->id;

                  if ($attribute->load(json_decode(json_encode($attr), true), '')) {
                    $attr_rows[$attribute->product_id . '_' . $attribute->url] = $attribute->attributes;
                  }
                }
              }

              if (!$cat_valid['validated']) {
                foreach ($category->attributes_required as $attr_id) {
                  if (!isset($attr_arr["attribute_$attr_id"]) || empty($attr_arr["attribute_$attr_id"])) {
                    $a = Yii::$app->db
                      ->createCommand('SELECT name FROM product_category_attribute_groups WHERE attribute_group_id=' . $attr_id)
                      ->queryOne();

                    if ($a) {
                      $attribute = new ProductAttributes();
                      $attribute->product_id = $product->id;
                      $attribute->value = 'не указано';
                      $attribute->url = strtolower(Helpers::translater($a['name'], 'ru')) . '_no';
                      $attribute->name = $a['name'];
                      $empty_attrs[$product->id . '_' . $attribute->url] = $attribute;
                    }
                  }
                }
              }
            } else {
              $this->debug('Ошибка сохранения товара');
              $this->debug(print_r($product->errors, true));

              $result['products'][] = array(
                'errors' => $product->errors
              );

              foreach ($img_arr as $img) {
                if (file_exists(Yii::$app->basePath . $img)) {
                  unlink(Yii::$app->basePath . $img);
                }
              }
            }
          } else {
            $result['products'][] = array(
              'errors' => $product->errors
            );

            $this->debug('Ошибка заполнения модели товара данными');
            $this->debug(print_r($enc_data, TRUE));
          }

          $n++;
        }

        if ($attr_rows || $empty_attrs) {
          $attr_model = new ProductAttributes();

          Yii::$app->db->createCommand()
            ->batchInsert(
              ProductAttributes::tableName(),
              $attr_model->attributes(),
              array_merge($attr_rows, $empty_attrs))
            ->execute();
        }

        if ($product_ids) {
          $this->debug('Обработка товаров окончена. ID записей: ' . substr($product_ids, 0, -1));
          $total_ids = explode(',', $product_ids);
          $this->debug('Записей: ' . (count($total_ids) - 1));
        }

        if ($found_products < count($data)) {
          $this->debug('Внимание: количество найденных товаров не совпадает с количеством записей');
        }

        if (!empty($urls)) {
          $this->debug('Найдено ' . count($urls) . ' изображений для загрузки');
          $this->j_file_download($urls);
        }

        $result['success'] = true;
        $result['found'] = $found_products;
        $result['saved'] = $saved_products;
        $result['duplicates'] = $duplicates;
        $result['is_error'] = $is_error;
        return $result;
      } else {
        $this->debug('Данные не прошли валидацию:');
        $this->debug(print_r($this->errors, TRUE));
        $result['validated'] = false;
        return $result;
      }
    } catch (\Exception $ex1) {
      $this->debug('При обработке возникла ошибка: ' . $ex1->getMessage());
      $this->debug($ex1->getTraceAsString());
      return false;
    }
  }

  private function debug($str, $clear = false) {
    if (!$this->is_debug) return;

    if ($clear) {
      file_put_contents(__DIR__ . '/upload_debug.log', '' . PHP_EOL);
    }

    file_put_contents(__DIR__ . '/upload_debug.log', $str . PHP_EOL, FILE_APPEND);
  }

  private static function writeLog($str) {
    file_put_contents(__DIR__ . '/upload_image_debug.log', $str . PHP_EOL, FILE_APPEND);
  }

  private function url_exists($url) {
    $headers = @get_headers($url);

    if($headers && strpos( $headers[0], '200')) {
      return true;
    }

    return false;
  }

  private function j_file_download(array $urls) {
    $ch = curl_init('http://localhost:8080/upload');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
      'urls' => json_encode($urls)
    ]));
    curl_exec($ch);
    curl_close($ch);
  }

  private function single_download(array $urls) {
    Image::$driver = [Image::DRIVER_GD2];
    $imagine = Image::getImagine();
    $insert = 'INSERT INTO images_hash (hash, url, user_id) VALUES ';
    $values = '';
    $user_id = Yii::$app->user->identity->id;

    foreach ($urls as $key => $url) {
      $addr = $url['url'];

      $file = $url['tmpdest'];
      //$fp = fopen($file, "w");

      try {
        file_put_contents($file, file_get_contents($addr));

        if ($url['tmpdest'] && $url['dest'] && file_exists($url['tmpdest'])) {
          try {
            $dimensions = getimagesize($url['tmpdest']);

            if ($dimensions[0] <= 640 || $dimensions[1] <= 480) {
              $imagine
                ->open($url['tmpdest'])
                ->save($url['dest'], array('quality' => 25));
            } else {
              Image::resize($url['tmpdest'], 640, 480)
                ->save($url['dest'], ['quality' => 25]);
            }
          } catch (\Exception $ex) {
            self::writeLog("Ошибка масштабирования изображения [" . $url['url'] . "]: " . $ex->getMessage());
            self::writeLog($ex->getTraceAsString());
          }

          $values .= ',("' . md5_file($url['tmpdest']) . '","' . $url['path'] . '",' . $user_id . ')';
        }

        unlink($url['tmpdest']);
      } catch (\Exception $ex) {
        self::writeLog('При загрузке изображения ' . $addr . ' возникла ошибка');
        self::writeLog($ex->getMessage());
        self::writeLog($ex->getTraceAsString());
      }
    }

    if ($values) {
      Yii::$app->db
        ->createCommand($insert . substr($values, 1) . ';')
        ->execute();
    }
  }

  private function multiple_download(array $urls)
  {
    try {
      Image::$driver = [Image::DRIVER_GD2];
      $imagine = Image::getImagine();
      $multi_handle = curl_multi_init();
      $curl_handles = [];
      $this->debug('Загружаю ' . count($urls) . ' изображений...');

      foreach ($urls as $key => $url) {
        $file = $url['tmpdest'];

        if (!is_file($file)) {
          $ch = curl_init(str_replace('https:', 'http:', $url['url']));
          $fp = fopen($file, "w");

          $curl_handles[$key] = array(
            'ch' => $ch,
            'file' => $fp
          );

          curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
          curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
          curl_setopt($ch, CURLOPT_FILE, $fp);
          curl_setopt($ch, CURLOPT_HEADER, 0);
          curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
          curl_multi_add_handle($multi_handle, $ch);
        }
      }

      $running = null;

      $this->full_curl_multi_exec($multi_handle, $running);

      do {
        curl_multi_select($multi_handle);
        $this->full_curl_multi_exec($multi_handle, $running);

        while ($info = curl_multi_info_read($multi_handle)) {
          // dump info
        }
      } while ($running);

      $insert = 'INSERT INTO images_hash (hash, url, user_id) VALUES ';
      $values = '';
      $user_id = Yii::$app->user->identity->id;

      foreach ($urls as $key => $url) {
        $handle = $curl_handles[$key];
        $ch = $handle['ch'];
        $error = curl_error($ch);
        $closed = fclose($handle['file']);
        curl_multi_remove_handle($multi_handle, $ch);
        curl_close($ch);
        if (!$closed) continue;
        time_nanosleep(0, 100000);

        if (!empty($error)) {
          self::writeLog("Ошибка загрузки изображения [" . $url['url'] . "]: " . $error);
          unlink($url['tmpdest']);
        } else {
          if ($url['tmpdest'] && $url['dest'] && file_exists($url['tmpdest'])) {
            try {
              $dimensions = getimagesize($url['tmpdest']);

              if ($dimensions[0] <= 640 || $dimensions[1] <= 480) {
                $imagine
                  ->open($url['tmpdest'])
                  ->save($url['dest'], array('quality' => 25));
              } else {
                Image::resize($url['tmpdest'], 640, 480)
                  ->save($url['dest'], ['quality' => 25]);
              }
            } catch (\Exception $ex) {
              self::writeLog("Ошибка масштабирования изображения [" . $url['url'] . "]: " . $ex->getMessage());
              self::writeLog($ex->getTraceAsString());
            }

            $values .= ',("' . md5_file($url['tmpdest']) . '","' . $url['path'] . '",' . $user_id . ')';
          }

          unlink($url['tmpdest']);
        }
      }

      curl_multi_close($multi_handle);

      if ($values) {
        Yii::$app->db
          ->createCommand($insert . substr($values, 1) . ';')
          ->execute();
      }
    } catch (Exception $cme) {
      self::writeLog($cme->getTraceAsString());
    }
  }

  function full_curl_multi_exec($mh, &$still_running)
  {
    do {
      $rv = curl_multi_exec($mh, $still_running);
    } while ($rv == CURLM_CALL_MULTI_PERFORM);

    return $rv;
  }
}
