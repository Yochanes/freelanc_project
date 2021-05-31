<?php

namespace app\models\forms;

use yii\base\Model;

class UploadForm extends Model
{

  public $imageFiles;

  public function rules()
  {
    return [
      [['imageFiles'], 'file', 'skipOnEmpty' => false, 'extensions' => 'gif, png, jpg', 'maxFiles' => 1],
    ];
  }

  public function upload()
  {
    if ($this->validate()) {
      foreach ($this->imageFiles as $file) {
        $file->saveAs('uploads/' . $file->baseName . '.' . $file->extension);
      }

      return true;
    } else {
      return false;
    }
  }
}
