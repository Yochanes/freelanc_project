<?php

namespace app\commands;

use app\models\site\ConfigSchedule;
use Yii;
use yii\console\Controller;
use app\models\Products;
use app\models\helpers\Helpers;

// /opt/php/7.0/bin/php www/teavto.ru/yii test
// /opt/php/7.0/bin/php www/teavto.ru/yii command/deactivate Rh0yN@,R0v@yl@

class CommandController extends Controller
{

  private $debug = true;

  private $token = 'Rh0yN@,R0v@yl@';

  public function actionIndex()
  {
    $this->log_message('Got test request');
    echo "Command test success";
    return 0;
  }

  public function actionProducts($t = '')
  {
    if ($t != $this->token) {
      $this->log_message('Error updating product product prices: token invalid');
      echo 'invalid token';
      return;
    }

    $this->log_message('Updating product product prices');
    $host = 'https://www.nbrb.by/api/exrates/rates?periodicity=0';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $host);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $resp = curl_exec($ch);

    $result = json_decode($resp, true);

    curl_close($ch);

    if ($result) {
      $this->log_message('New currency scales fetched');
      $rates = [];

      foreach ($result as $rate) {
        if (!$rate['Cur_OfficialRate']) continue;

        if (!is_string($rate['Cur_Abbreviation'])) continue;
        $abbr = strtolower($rate['Cur_Abbreviation']);
        if ($abbr != 'usd' && $abbr != 'eur' && $abbr != 'rub') continue;
        $rates[$abbr] = $rate;
      }

      $curs_rub = number_format($rates['rub']['Cur_OfficialRate'], 2);
      $curs_eur = number_format($rates['eur']['Cur_OfficialRate'], 2);
      $curs_usd = number_format($rates['usd']['Cur_OfficialRate'], 2);
      $curs_rub_scale = $rates['rub']['Cur_Scale'];
      $curs_eur_scale = $rates['eur']['Cur_Scale'];
      $curs_usd_scale = $rates['usd']['Cur_Scale'];

      if ($curs_rub && $curs_eur && $curs_usd && $curs_rub_scale && $curs_eur_scale && $curs_usd_scale) {
        $curs_values = [];
        $curs_scales = [];

        $curs_values['RUB'] = $curs_rub;
        $curs_scales['RUB'] = $curs_rub_scale;
        $curs_values['EUR'] = $curs_eur;
        $curs_scales['EUR'] = $curs_eur_scale;
        $curs_values['USD'] = $curs_usd;
        $curs_scales['USD'] = $curs_usd_scale;

        Helpers::recalcProductPrices($curs_values, $curs_scales);

        $curs_values = json_encode($curs_values);
        $curs_scales = json_encode($curs_scales);

        Yii::$app->db
          ->createCommand("UPDATE user_curs SET curs_values='" . $curs_values . "', curs_scales='" . $curs_scales . "', date_updated='" . date('Y-m-d H:i:s') . "' WHERE use_default=1")
          ->execute();

        $this->log_message('Product prices updated successfully');
        echo 'curs updated';
      } else {
        $this->log_message('Error updating product prices: new currency scales not found');
        echo 'curs not updated';
      }
    } else {
      $this->log_message('Error updating product prices: new currency scales not fetched');
      echo 'curs not fetched';
    }
  }

  public function actionDeactivate($t = '')
  {
    if ($t != $this->token) {
      $this->log_message('Error deactivating requests: token invalid');
      echo 'invalid token';
      return;
    }

    $date_offset = date("Y-m-d H:i:s", strtotime('-30 days'));

    Yii::$app->db
      ->createCommand('UPDATE requests SET status=' . Products::STATE_INACTIVE . ' WHERE status=' . Products::STATE_ACTIVE . ' AND date_updated<="' . $date_offset . '"')
      ->execute();

    $this->log_message('Requests successfully deactivated');
    echo 'requests deactivated';
  }

  public function beforeAction($action)
  {
    if ($this->action->id != 'index') {
      $config = ConfigSchedule::findOne('id > 0');

      if ($config) {
        $this->token = $config->token;
      }
    }

    return parent::beforeAction($action);
  }

  private function log_message($str)
  {
    if (!$this->debug) {
      return;
    }

    file_put_contents(__DIR__ . '/commands_debug.log', date('Y.m.d H:i:s') . ': ' . $str . PHP_EOL, FILE_APPEND);
  }
}
