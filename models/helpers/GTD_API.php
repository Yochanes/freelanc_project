<?php

namespace app\models\helpers;

class GTD_API
{
  private static $API_KEY = 'RsjaRNCH0723nFfx4w7MtojIQYTZdDkx';
  private static $API_URL = 'https://capi.gtdel.com';

  public static function calculateDelivery(array $data)
  {
    $url = self::$API_URL . '/1.0/order/calculate';
    return self::sendPost($url, $data);
  }

  public static function getCities($country_code = '')
  {
    $url = self::$API_URL . '/1.0/tdd/city/get-list';
    return self::sendPost($url, $country_code ? ['country_code' => $country_code] : []);
  }

  private static function sendPost($url, $post_data = array())
  {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

    if ($post_data) {
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Authorization: Bearer ' . self::$API_KEY,
      'Content-Type: application/json'));

    $output = curl_exec($ch);
    $error = curl_error($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (empty($error) && $httpcode === 200) {
      return json_decode($output, true);
    } else if (empty($error)) {
      return ['error' => $output, 'description' => 'Request was not successful', 'http_code' => $httpcode];
    } else {
      return ['error' => $error, 'description' => 'request was not successful'];
    }
  }
}
