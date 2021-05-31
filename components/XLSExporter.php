<?php
/**
 * Created by PhpStorm.
 * User: Igor
 * Date: 22.05.2021
 * Time: 14:28
 */

namespace app\components;


use app\models\Products;
use app\models\products\ProductGroups;
use app\models\products\ProductUploads;
use moonland\phpexcel\Excel;
use Yii;

class XLSExporter
{
  public static function exportProducts($url) {
    $product_group = ProductGroups::find()
      ->where(['product_group_id' => $url])
      ->one();

    if (!$product_group) {
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }

    $products = Products::find()
      ->where([
        'group_id' => $url,
        'user_id' => Yii::$app->user->identity->id,
        'status' => Products::STATE_ACTIVE
      ])
      ->with('attributesArray')
      ->all();

    $cols = [
      'id' => 'ID',
      'sku' => 'Артикул',
      'partnum' => 'Номер детали',
      'make_val' => 'Марка',
      'model_val' => 'Модель',
      'generation_val' => 'Поколение',
      'category_val' => 'Название',
      'year_val' => 'Год',
      'text' => 'Описание',
      'preorder' => 'Предзаказ',
      'available' => 'Наличие',
      'price' => 'Цена',
      'sale' => 'Скидка',
      'currency' => 'Валюта',
      'images' => 'Фото',
      'views' => 'Просмотры',
      'phone_views' => 'Просмотры телефона',
      // 'country_id' => 'Страна',
      // 'city' => 'Город',
      // 'address' => 'Адрес',
      // 'seller_phone' => 'Телефон',
      // 'seller_email' => 'Email',
      'seller_name' => 'Имя',
      'url' => 'Объявление'
    ];

    if ($products) {
      try {
        $headers = [];
        $columns = [];
        $values = [];

        $styleArray = [
          'borders' => [
            'style' => Excel::BORDER_MEDIUM,
            'color' => Excel::COLOR_BLACK
          ],
          'font' => [
            'bold' => false,
          ],
          'alignment' => [
            'wrapText' => true,
            'vertical' => Excel::VERTICAL_CENTER
          ]
        ];

        foreach($products as $i => $item) {
          $obj = (object) [];

          foreach ($cols as $key => $c) {
            if ($key == 'preorder' || $key == 'available') {
              $key = 'stock';
            }

            if ($key != 'stock') {
              if ($key == 'images') {
                $str = '';

                foreach ($item->images as $img) {
                  $str .= $item->url . rtrim(basename($img), '/') . ',';
                }

                $str = substr($str, 0, -1);
                $obj->{$key} = $str;
              } else if ($key == 'id') {
                $obj->{$key} = 'AR_' . $item[$key];
              } else {
                $obj->{$key} = $item[$key];
              }
            } else {
              if ($item->preorder) {
                $obj->{$key} = 'под заказ';
              } else if ($item->available) {
                $obj->{$key} = 'да';
              }
            }

            if ($key != 'stock' && !$item->$key) $obj->{$key} = '';

            if (!isset($headers[$key])) {
              $headers[$key] = $c;

              $columns[] = [
                'attribute' => $key,
                'header' => $c,
                'format' => 'text',
                'cellFormat' => $styleArray,
                'width' => mb_strlen($c) > mb_strlen($key) ? mb_strlen($c) * 2 : mb_strlen($key) * 2
              ];
            }
          }

          foreach ($item->attributesArray as $pattr) {
            $key = $pattr->name;
            $obj->{$key} = $pattr->value;

            if (!isset($headers[$key])) {
              $headers[$key] = $key;

              $columns[] = [
                'attribute' => $key,
                'header' => $key,
                'format' => 'text',
                'cellFormat' => $styleArray,
                'width' => mb_strlen($c) > mb_strlen($key) ? mb_strlen($c) * 1.2 : mb_strlen($key) * 1.2
              ];
            }
          }

          $values[] = $obj;
        }

        foreach ($values as $obj) {
          foreach ($headers as $key) {
            if (!isset($obj->{$key})) {
              $obj->{$key} = '';
            }
          }
        }

        \PhpOffice\PhpSpreadsheet\Shared\File::setUseUploadTempDirectory(true);

        Excel::export([
          'asAttachment' => true,
          'savePath' => '',
          'models' => $values,
          'columns' => $columns,
          'headers' => $headers,
          'fileName' => 'Выгрузка ' . $product_group->name . ' ' . date('m.d.y')
        ]);
      } catch (\Exception $ex) {
        header_remove('Content-Disposition');
        header('Content-type: text/html');
        throw new \yii\web\HttpException(400, $ex->getTraceAsString());
      }
    } else {
      header_remove('Content-Disposition');
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }
  }

  public static function downloadReport($url)
  {
    $count = ProductUploads::find()
      ->where(['user_id' => Yii::$app->user->identity->id, 'upload_id' => $url])
      ->count();

    if ($count) {
      $headers = [];
      $columns = [];
      $values = [];

      $styleArray = [
        'borders' => [
          'style' => Excel::BORDER_MEDIUM,
          'color' => Excel::COLOR_BLACK
        ],
        'font' => [
          'bold' => false,
        ],
        'alignment' => [
          'wrapText' => true,
          'vertical' => Excel::VERTICAL_CENTER
        ]
      ];

      if ($count > 500) {
        echo "Ожидание создания отчета...";
      }

      $offset = 0;
      $current = 0;
      $items = [];

      while ($current < $count) {
        $items = array_merge($items, ProductUploads::find()
          ->where(['user_id' => Yii::$app->user->identity, 'upload_id' => $url])
          ->limit(500)
          ->offset($offset)
          ->all());

        $offset += 500;
        $current += 500;
      }

      if ($items) {
        try {
          foreach ($items as $i => $item) {
            if (is_array($item->columns)) {
              foreach ($item->columns as $key => $c) {
                if (!isset($headers[$key])) {

                  $headers[$key] = $key;
                  $columns[] = [
                    'attribute' => $key,
                    'header' => $key,
                    'format' => 'text',
                    'cellFormat' => $styleArray,
                    'width' => mb_strlen($c) > mb_strlen($key) ? mb_strlen($c) * 1.2 : mb_strlen($key) * 1.2
                  ];
                }
              }
            }
          }

          $columns[] = [
            'attribute' => 'Ошибки',
            'header' => 'Ошибки',
            'format' => 'text',
            'cellFormat' => $styleArray,
            'width' => 70
          ];

          $headers['Ошибки'] = 'Ошибки';

          foreach ($items as $i => $item) {
            if (is_array($item->columns)) {
              $n = 0;
              $obj = (object)[];
              $vals = [];
              $errors = '';

              foreach ($item->columns as $key => $c) {
                $n++;

                $obj->{$key} = $c;
                $vals[$key] = $c;
              }

              foreach ($item->error_vals as $err) {
                $errors .= $err . PHP_EOL;
              }

              foreach ($headers as $h) {
                if (!isset($vals[$h])) {
                  $vals[$h] = '';
                  $obj->{$h} = '';
                }
              }

              $obj->{'Ошибки'} = $errors;
              $vals['Ошибки'] = $errors;
              $obj->attributes = $vals;
              $values[] = $obj;
            }
          }

          \PhpOffice\PhpSpreadsheet\Shared\File::setUseUploadTempDirectory(true);

          Excel::export([
            'asAttachment' => true,
            'fileName' => "errors_export_" . date('d_m_Y'),
            'savePath' => '',
            'models' => $values,
            'columns' => $columns,
            'headers' => $headers
          ]);
        } catch (\Exception $ex) {
          header_remove('Content-Disposition');
          header('Content-type: text/html');
          throw new \yii\web\HttpException(400, $ex->getTraceAsString());
        }
      } else {
        header_remove('Content-Disposition');
        throw new \yii\web\HttpException(404, 'Страница не найдена');
      }
    } else {
      header_remove('Content-Disposition');
      throw new \yii\web\HttpException(404, 'Страница не найдена');
    }
  }
}