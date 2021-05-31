<?php

namespace app\modules\controlpanel\models\forms\makes;

use app\models\makes\Generations;
use Yii;
use yii\base\Model;

use app\models\makes\Makes;
use app\models\makes\Models;

use app\models\helpers\Helpers;

class MakesUploadForm extends Model
{
  public $data;
  public $group_id;
  public $group_ids;
  public $current;
  public $clear_makes;

  public function rules()
  {
    return [
      ['data', 'string'],
      [['group_id', 'current'], 'integer', 'skipOnEmpty' => true],
      [['group_ids'], 'each', 'rule' => ['integer']],
      ['clear_makes', 'boolean']
    ];
  }

  public function saveData()
  {
    $result = array();
    $user = Yii::$app->user->identity;

    if ($this->validate()) {
      $data = json_decode($this->data);

      if ($data && is_array($data)) {
        $result['validated'] = true;

        if ($this->current == 0) {
          Yii::$app->db->createCommand('DELETE FROM urls WHERE parameters like "make%"')->execute();

          if ($this->clear_makes) {
            if (!$this->group_id) {
              Yii::$app->db->createCommand('DELETE FROM make_to_group')->execute();
              Yii::$app->db->createCommand('DELETE FROM makes')->execute();
              Yii::$app->db->createCommand('DELETE FROM make_models')->execute();
              Yii::$app->db->createCommand('DELETE FROM make_generations')->execute();
            } else {
              $ids = Yii::$app->db
                ->createCommand('SELECT make_id FROM make_to_group WHERE make_group_id=' . $this->group_id)
                ->queryAll();

              $idstr = [];

              $m_to_delete = '';

              if ($ids) {
                Yii::$app->db
                  ->createCommand('DELETE FROM make_to_group WHERE make_group_id=' . $this->group_id)
                  ->execute();

                foreach ($ids as $mid) {
                  $count = Yii::$app->db
                    ->createCommand('SELECT COUNT(*) FROM make_to_group WHERE make_id="' . $mid['make_id'] . '"')
                    ->queryScalar();

                  if (!$count) {
                    $m_to_delete .= $mid['make_id'] . ',';
                  }
                }

                $m_to_delete = substr($m_to_delete, 0, -1);

                if ($m_to_delete) {
                  Yii::$app->db
                    ->createCommand('DELETE FROM makes WHERE id IN (' . $m_to_delete . ')')
                    ->execute();

                  Yii::$app->db
                    ->createCommand('DELETE FROM make_generations WHERE make_id IN (' . $m_to_delete . ')')
                    ->execute();
                }
              }
            }
          }
        }

        $to_insert_arr = array();
        $urls_to_insert = [];
        $make_to_group_insert = [];
        $params_to_insert = [];
        $make_to_group_insert_check = [];

        $to_insert = 0;
        $to_update = 0;
        $inserted = 0;
        $updated = 0;

        foreach ($data as $row) {
          if (!isset($row->make_name)) continue;
          $make_name = trim($row->make_name);
          $make_id = false;
          $make = false;
          $url = 'm-' . Helpers::translaterUrl($make_name);
          $make_name = preg_replace('/\\\\/u', '/', $make_name);

          $check = Makes::find()
            ->where('LOWER(name)="' . mb_strtolower($make_name) . '"')
            ->one();

          if ($check) {
            Yii::$app->db
              ->createCommand('DELETE FROM url_params WHERE url="' . $check->url . '" AND name="make"')
              ->execute();

            $make = $check;
            $to_update++;
            $make_id = $make->id;
            $make->name = $make_name;
            $make->url = $url;
            if (isset($row->is_popular)) $make->is_popular = $row->is_popular;
            if (!$make->save()) continue;

            $updated++;
          } else {
            $to_insert++;
            $make = new Makes();
            $make->name = $make_name;
            $make->image = '/web/makeicons/' . Helpers::translater(mb_strtolower(trim($make_name)), 'ru', null, true) . '.png';
            $make->url = $url;
            if (isset($row->is_popular)) $make->is_popular = $row->is_popular;
            if (!$make->save()) continue;
            $inserted++;

            $makes_arr[mb_strtolower($make_name)] = $make;
            $make_id = $make->id;
          }

          $params_to_insert['make_' . $make->id] = [
            'name' => 'make',
            'title' => $make->name,
            'url' => $make->url,
            'connected_id' => $make->id
          ];

          $urls_to_insert[$make->url] = [
            'url' => $make->url,
            'action' => 'makes/models',
            'parameters' => 'make'
          ];

          if ($this->group_id && $check) {
            if (!isset($make_to_group_insert_check[$make_id . '_' . $this->group_id])) {
              Yii::$app->db
                ->createCommand('DELETE FROM make_to_group WHERE make_group_id=' . $this->group_id . ' AND make_id=' . $make_id)
                ->execute();

              $make_to_group_insert[] = [$this->group_id, $make_id];
              $make_to_group_insert_check[$make_id . '_' . $this->group_id] = $make_id;
            }
          } else if (isset($row->make_group_id)) {
            if (!isset($make_to_group_insert_check[$make_id . '_' . $row->make_group_id])) {
              Yii::$app->db
                ->createCommand('DELETE FROM make_to_group WHERE make_group_id=' . $row->make_group_id . ' AND make_id=' . $make_id)
                ->execute();

              $make_to_group_insert[] = [$row->make_group_id, $make_id];
              $make_to_group_insert_check[$make_id . '_' . $row->make_group_id] = $make_id;
            }
          }

          if (!isset($row->model_name)) continue;
          $model_name = trim($row->model_name);

          if (mb_strtolower($make_name) == 'saab') {
            if (mb_strtolower($model_name) == '90') {
              $model_name = '9-0';
            } else if (mb_strtolower($model_name) == '93') {
              $model_name = '9-3';
            } else if (mb_strtolower($model_name) == '95') {
              $model_name = '9-5';
            } else if (mb_strtolower($model_name) == '96') {
              $model_name = '9-6';
            } else if (mb_strtolower($model_name) == '99') {
              $model_name = '9-9';
            }
          }

          if (empty($model_name)) continue;
          $model_id = false;
          $make_url = $url;
          $url = 'mod-' . Helpers::translaterUrl($model_name);
          $model_name = preg_replace('/\\\\/u', '/', $model_name);

          $check = Models::find()
            ->where('LOWER(name)="' . mb_strtolower($model_name) . '" AND make_id=' . $make_id)
            ->one();

          if ($check) {
            Yii::$app->db
              ->createCommand('DELETE FROM url_params WHERE url="' . $check->url . '" AND name="model"')
              ->execute();

            $model = $check;
            $model_id = $check->id;
            $model->name = $model_name;
            $model->make_url = $make_url;
            $model->url = $url;
            if (isset($row->is_popular)) $model->is_popular = $row->is_popular;
            if (!$model->save()) continue;
          } else {
            $model = new Models();
            $model->name = $model_name;
            $model->make_id = $make_id;
            $model->make_url = $make_url;
            $model->url = $url;
            if (isset($row->is_popular)) $model->is_popular = $row->is_popular;
            if (!$model->save()) continue;
            $model_id = $model->id;
          }

          $params_to_insert['model_' . $model->id] = [
            'name' => 'model',
            'title' => $model->name,
            'url' => $model->url,
            'connected_id' => $model->id
          ];

          $models_arr[$make_id . '_' . mb_strtolower(trim($model_name))] = $model;

          if (!isset($row->generation_name) && !isset($row->generation_alt_name)) continue;

          $generation = isset($row->generation_name) ? trim($row->generation_name) : trim($row->generation_alt_name);
          $generation_alt = isset($row->generation_alt_name) ? trim($row->generation_alt_name) : trim($row->generation_name);
          $years = isset($row->generation_years) ? trim($row->generation_years) : '';

          $gen_name = $generation;
          if (empty($gen_name)) $gen_name = $generation_alt;
          if (empty($gen_name)) continue;
          $gen_name = preg_replace('/\\\\/u', '/', $gen_name);

          if (isset($generations_arr[$model_id . '_' . mb_strtolower($gen_name)])) continue;

          $check = Generations::find()
            ->where('LOWER(name)="' . mb_strtolower($gen_name) . '" AND model_id=' . $model_id)
            ->one();

          $model_url = $url;
          $url = 'gen-' . Helpers::translaterUrl($gen_name);

          if ($check) {
            Yii::$app->db
              ->createCommand('DELETE FROM url_params WHERE url="' . $check->url . '" AND name="generation"')
              ->execute();

            $check->name = $gen_name;
            $check->url = $url;
            $check->make_id = $make_id;
            $check->model_url = $model_url;
            $check->alt_name = $generation_alt;
            $check->years = $years;
            $check->save();
          } else {
            $to_insert_arr[] = [$make_id, $model_id, $model_url, $gen_name, $generation_alt, $years, $url];
          }

          $generations_arr[$model_id . '_' . mb_strtolower($gen_name)] = array(
            'name' => $gen_name,
            'url' => $url,
          );

          $params_to_insert['generation_' . $make_id . '_' . $model_id . '_' . $url] = [
            'name' => 'generation',
            'title' => $gen_name,
            'url' => $url,
            'connected_id' => $check ? $check->id : ''
          ];
        }

        if (!empty($make_to_group_insert)) {
          Yii::$app->db->createCommand()
            ->batchInsert('make_to_group', ['make_group_id', 'make_id'], $make_to_group_insert)
            ->execute();
        }

        if (!empty($to_insert_arr)) {
          Yii::$app->db->createCommand()
            ->batchInsert('make_generations', ['make_id', 'model_id', 'model_url', 'name', 'alt_name', 'years', 'url'], $to_insert_arr)
            ->execute();
        }

        if (!empty($urls_to_insert)) {
          Yii::$app->db->createCommand()
            ->batchInsert('urls', ['url', 'action', 'parameters'], $urls_to_insert)
            ->execute();
        }

        if (!empty($params_to_insert)) {
          $find = '';

          foreach ($params_to_insert as $key => $pti) {
            if ($pti['name'] == 'generation' && !$pti['connected_id']) {
              $exp = explode('_', $key);
              $model_id = $exp[2];
              $find .= ' OR (model_id="' . $model_id . '" AND url="' . $pti['url'] . '")';
            }
          }

          if ($find) {
            $find = substr($find, 4);

            $gen_arr = Yii::$app->db
              ->createCommand('SELECT make_id, model_id, id, url FROM ' . Generations::tableName() . ' WHERE ' . $find)
              ->queryAll();

            foreach ($gen_arr as $gen) {
              if (isset($params_to_insert['generation_' . $gen['make_id'] . '_' . $gen['model_id'] . '_' . $gen['url']])) {
                $params_to_insert['generation_' . $gen['make_id'] . '_' . $gen['model_id'] . '_' . $gen['url']]['connected_id'] = $gen['id'];
              }
            }
          }

          Yii::$app->db->createCommand()
            ->batchInsert('url_params', ['name', 'title', 'url', 'connected_id'], $params_to_insert)
            ->execute();
        }

        $result['to_insert'] = $to_insert;
        $result['to_update'] = $to_update;
        $result['inserted'] = $inserted;
        $result['updated'] = $updated;
        $result['success'] = true;
        return $result;
      } else {
        $this->addError('upload_file', 'failed to save file');
      }
    }

    $result['validated'] = false;
    return $result;
  }
}
