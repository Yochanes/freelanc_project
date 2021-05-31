<?php

namespace app\models\helpers;

use Yii;
use app\models\data\Data;

class JSONData
{
  public static function createCategoriesJSON($version, $categories)
  {
    $categories_json = [];
    $synonyms_json = [];

    foreach ($categories as $item) {
      $url = isset($item['parent_url']) && trim($item['parent_url']) ? $item['parent_url'] : $item['url'];
      $categories_json[mb_strtolower($item['name'])] = [
        'id' => $item['id'],
        'url' => $url,
        'name' => $item['name'],
        'connected_attrs' => ($item['connected_attributes'] ? json_encode($item['connected_attributes']) : null),
      ];

      if ($item['synonym']) {
        $synonyms_json[mb_strtolower($item['synonym'])] = [
          'url' => $url,
          'name' => $item['name'],
          'orig_synonym' => $item['synonym'],
          'connected_attrs' => ($item['connected_attributes'] ? json_encode($item['connected_attributes']) : null)
        ];
      }
    }

    self::makeSureJsonDirExists();

    file_put_contents(
      self::getFileName(Data::CATEGORIES_FILENAME, $version),
      'var __categories__=' . json_encode($categories_json) . ';' .
      'var __synonyms__=' . json_encode($synonyms_json) . ';'
    );
  }

  public static function createMakesJSON($version, $makes, $models, $generations)
  {
    $make_names = [];
    $model_names = [];
    $makes_json = [];

    foreach ($makes as $item) {
      $makes_json[mb_strtolower($item['name'])] = [
        'id' => $item['id'],
        'url' => $item['url'],
        'name' => $item['name'],
        'models' => []
      ];

      $make_names[$item['id']] = mb_strtolower($item['name']);
    }

    foreach ($models as $item) {
      if (!isset($make_names[$item['make_id']])) {
        continue;
      }

      $make_name = $make_names[$item['make_id']];

      if (!isset($makes_json[$make_name])) {
        continue;
      }

      $makes_json[$make_name]['models'][mb_strtolower($item['name'])] = [
        'id' => $item['id'],
        'make_id' => $item['make_id'],
        'url' => $item['url'],
        'name' => $item['name'],
        'generations' => []
      ];

      $model_names[$item['id']] = mb_strtolower($item['name']);
    }

    foreach ($generations as $item) {
      if (!isset($make_names[$item['make_id']])) {
        continue;
      }

      $make_name = $make_names[$item['make_id']];

      if (!isset($makes_json[$make_name])) {
        continue;
      }

      if (!isset($model_names[$item['model_id']])) {
        continue;
      }

      $model_name = $model_names[$item['model_id']];

      $years = explode('–', $item['years']);
      $years = (isset($years[0]) ? trim($years[0]) : 'x') . '_' . (isset($years[1]) ? trim($years[1]) : 'x');

      $makes_json[$make_name]['models'][$model_name]['generations'][mb_strtolower($item['name'])] = [
        'id' => $item['id'],
        'model_id' => $item['model_id'],
        'url' => $item['url'],
        'name' => $item['name'],
        'years' => $years
      ];
    }

    self::makeSureJsonDirExists();

    file_put_contents(
      self::getFileName(Data::MAKES_FILENAME, $version),
      'var __makes__=' . json_encode($makes_json) . ';'
    );
  }

  private static function makeSureJsonDirExists()
  {
    if (!file_exists(Yii::$app->basePath . '/web/js/json/')) {
      mkdir(Yii::$app->basePath . '/web/js/json/', 0755, true);
    }
  }

  public static function updateMakesVersion($update_file_version = false)
  {
    $data = self::getFileData(Data::MAKES_FILENAME, true);
    $version = is_numeric($data->version) ? intval($data->version) : 0;
    $data->version = $version + 1;

    if ($update_file_version) {
      $data->file_version = $data->version;
    }

    $data->save();
  }

  public static function updateCategoriesVersion($update_file_version = false)
  {
    $data = self::getFileData(Data::CATEGORIES_FILENAME, true);
    $version = is_numeric($data->version) ? intval($data->version) : 0;
    $data->version = $version + 1;

    if ($update_file_version) {
      $data->file_version = $data->version;
    }

    $data->save();
  }

  public static function updateCategoriesFileVersion()
  {
    $data = self::getFileData(Data::CATEGORIES_FILENAME, true);
    $version = is_numeric($data->version) ? intval($data->version) : 0;
    $data->version = $version;
    $update_needed = false;

    if ($version != $data->file_version) {
      $update_needed = true;
    } else if (!file_exists(self::getFileName(Data::CATEGORIES_FILENAME, $version))) {
      $update_needed = true;
    }

    $data->file_version = $version;
    $data->save();

    return [ $update_needed, $data->file_version ];
  }

  public static function updateMakesFileVersion()
  {
    $data = self::getFileData(Data::MAKES_FILENAME, true);
    $version = is_numeric($data->version) ? intval($data->version) : 0;
    $data->version = $version;
    $update_needed = false;

    if ($version != $data->file_version) {
      $update_needed = true;
    } else if (!file_exists(self::getFileName(Data::MAKES_FILENAME, $version))) {
      $update_needed = true;
    }

    $data->file_version = $version;
    $data->save();

    return [ $update_needed, $data->file_version ];
  }

  private static function getFileData($filename, $remove_if_exists = false)
  {
    $data = Data::find()
      ->where([
        'filename' => $filename
      ])
      ->one();

    if (!$data) {
      $data = new Data();
    } else if ($remove_if_exists) {
      $data->delete();
    }

    $data->filename = $filename;
    $data->timestamp = date('Y.m.d H:i:s');
    return $data;
  }

  public static function getFileURL($filename, $version)
  {
    return '/web/js/json/' . $filename . '_' . $version . '.js';
  }

  public static function getFileName($filename, $version)
  {
    return Yii::$app->basePath . self::getFileURL($filename, $version);
  }
}
