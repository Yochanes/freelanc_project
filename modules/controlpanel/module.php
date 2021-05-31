<?php
namespace app\modules\controlpanel;

class Module extends \yii\base\Module
{
	
	public $controllerNamespace = 'app\modules\controlpanel\controllers';
	
    public function init()
    {
      parent::init();

      $this->setAliases([
        '@dashboard-assets' => __DIR__ . '/assets'
      ]);

		  \Yii::configure($this, require __DIR__ . '/config/config.php');
    }
}
?>