<?php

namespace app\models\makes;

use Yii;

class MakeGroups extends \yii\db\ActiveRecord
{

  private $_makesCount = -1;

  public function rules()
  {
    return [
      [['name', 'filter_name', 'use_in_car_form', 'url'], 'required'],
      [['name', 'filter_name', 'import_filter', 'url'], 'string', 'max' => 255],
      ['make_group_id', 'integer'],
      [['use_in_car_form'], 'boolean']
    ];
  }

  public static function tableName()
  {
    return 'make_groups';
  }

  public function getId()
  {
    return $this->make_group_id;
  }

  public function getMakes()
  {
    return $this->hasMany(Makes::class, ['id' => 'make_id'])
      ->viaTable('make_to_group', ['make_group_id' => 'make_group_id'])
      ->innerJoin('make_to_group', 'make_to_group.make_id=' . Makes::tableName() . '.id')
      ->orderBy([Makes::tableName() . '.name' => SORT_ASC])
      ->indexBy('id');
  }

  public function getMakesCount()
  {

    if ($this->_makesCount < 0) {
      $this->_makesCount = Yii::$app->db
        ->createCommand('SELECT COUNT(*) FROM make_to_group WHERE make_group_id=' . $this->make_group_id)
        ->queryScalar();
    }

    return $this->_makesCount;
  }
}
