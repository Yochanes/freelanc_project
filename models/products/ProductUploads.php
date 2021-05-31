<?php

namespace app\models\products;

class ProductUploads extends \yii\db\ActiveRecord
{

  public function rules()
  {
    return [
      [['columns', 'error_vals', 'upload_id', 'group_id'], 'required'],
      [['columns', 'error_vals', 'upload_id'], 'string'],
      [['id', 'user_id', 'group_id'], 'integer'],
    ];
  }

  public static function tableName()
  {
    return 'product_uploads';
  }

  public function afterFind()
  {
    parent::afterFind();
    $this->columns = !is_null($this->columns) && !empty($this->columns) ? json_decode($this->columns, TRUE) : [];
    $this->error_vals = !is_null($this->error_vals) && !empty($this->error_vals) ? json_decode($this->error_vals, TRUE) : [];
  }
}
