<?php

namespace app\modules\controlpanel\models\forms\makes;

use Yii;
use yii\base\Model;
use yii\web\UploadedFile;

use app\models\makes\MakeGroups;
use app\models\makes\Makes;

use app\models\site\Pages;
use app\models\helpers\Helpers;

class MakesWithGroupsUploadForm extends Model
{
  public $data;

  public function rules()
  {
    return [
      [['data'], 'string']
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

        $groups_arr = array();
        $groups_tarr = MakeGroups::find()->all();

        foreach ($groups_tarr as $row) {
          if (!$row->import_filter) continue;
          $groups_arr[mb_strtolower(trim($row->import_filter))] = $row->make_group_id;
        }

        $makes_arr = array();
        $makes_tarr = Makes::find()->all();
        foreach ($makes_tarr as $row) $makes_arr[mb_strtolower(trim($row['name']))] = $row;

        $to_insert = 0;
        $to_update = 0;
        $inserted = 0;
        $updated = 0;

        Yii::$app->db
          ->createCommand('DELETE FROM make_to_group')
          ->execute();

        $make_to_groups = [];

        foreach ($data as $row) {
          if (!isset($row->make_name)) continue;
          $make_name = trim($row->make_name);
          $make_id = false;
          $make_group = false;

          if (isset($makes_arr[mb_strtolower($make_name)])) {
            $make = $makes_arr[mb_strtolower($make_name)];
            $to_update++;
            $make_id = $make->id;
            $updated++;
          }

          if ($make_id) {
            if (isset($row->import_filter) && is_array($row->import_filter)) {
              foreach ($row->import_filter as $if) {
                if (isset($groups_arr[mb_strtolower(trim($if))])) {
                  $make_group = $groups_arr[mb_strtolower(trim($if))];
                  $make_to_groups[] = array($make_group, $make_id);
                }
              }
            }
          }
        }

        if ($make_to_groups) {
          Yii::$app->db
            ->createCommand()->batchInsert('make_to_group', ['make_group_id', 'make_id'], $make_to_groups)
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
