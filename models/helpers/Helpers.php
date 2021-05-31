<?php

namespace app\models\helpers;

use Yii;
use yii\imagine\Image;

class Helpers
{
  public static function getDateDiff($dt_from, $dt_to = '')
  {
    if (empty($dt_to)) $dt_to = date("Y-m-d H:i:s");
    if (is_string($dt_from)) $dt_from = new \DateTime($dt_from);
    if (is_string($dt_to)) $dt_to = new \DateTime($dt_to);

    $diff = $dt_from->diff($dt_to);
    $keyarr = ['y', 'm', 'd', 'h', 'i', 's'];

    foreach ($diff as $k => $v) {
      if (!in_array($k, $keyarr)) continue;
      $arr = str_split($v);
      $diff->{'str_' . $k} = (int)end($arr);
    }

    $str = '';

    if ($diff->y > 0) {
      $str .= $diff->y . (($diff->str_y == 1 && $diff->y != 11) ? ' год' : (($diff->str_y > 0 && $diff->str_y < 5 && ($diff->y < 10 || $diff->y > 20)) ? ' года' : ' лет'));
      if ($diff->y > 1) return $str;
    }

    if ($diff->m > 0) {
      if (!empty($str)) $str .= ', ';
      $str .= $diff->m . (($diff->str_m == 1 && $diff->m != 11) ? ' месяц' : (($diff->str_m > 0 && $diff->str_m < 5 && ($diff->m < 10 || $diff->m > 20)) ? ' месяца' : ' месцев'));
      if ($diff->m > 1) return $str;
    }

    if ($diff->d > 0) {
      if (!empty($str)) $str .= ', ';
      $str .= $diff->d . (($diff->str_d == 1 && $diff->d != 11) ? ' день' : (($diff->str_d > 0 && $diff->str_d < 5 && ($diff->d < 10 || $diff->d > 20)) ? ' дня' : ' дней'));
      if ($diff->d > 1) return $str;
    }

    if ($diff->h > 0) {
      if (!empty($str)) $str .= ', ';
      $str .= $diff->h . (($diff->str_h == 1 && $diff->h != 11) ? ' час' : (($diff->str_h > 0 && $diff->str_h < 5 && ($diff->h < 10 || $diff->h > 20)) ? ' часа' : ' часов'));
      if ($diff->h > 6) return $str;
    }

    if ($diff->y == 0 && $diff->m == 0 && $diff->d == 0 && $diff->h == 0 && $diff->i > 0) {
      if (!empty($str)) $str .= ', ';
      $str .= $diff->i . (($diff->str_i == 1 && $diff->i != 11) ? ' минута' : (($diff->str_i > 0 && $diff->str_i < 5 && ($diff->i < 10 || $diff->i > 20)) ? ' минуты' : ' минут'));
    }

    if ($diff->y == 0 && $diff->m == 0 && $diff->d == 0 && $diff->h == 0 && $diff->i == 0 && $diff->s > 0) {
      if (!empty($str)) $str .= ', ';
      $str .= $diff->s . (($diff->str_s == 1 && $diff->s != 11) ? ' секунда' : (($diff->str_s > 0 && $diff->str_s < 5 && ($diff->s < 10 || $diff->s > 20)) ? ' секунды' : ' секунд'));
    }

    return $str;
  }

  public static function getDateOffset($dt_from, $dt_to = '')
  {
    if (empty($dt_to)) $dt_to = date("Y-m-d H:i:s");
    if (is_string($dt_from)) $dt_from = new \DateTime($dt_from);
    if (is_string($dt_to)) $dt_to = new \DateTime($dt_to);
    return $dt_from->diff($dt_to);
  }

  public static function getDayMonthTranslated($d = '', $ending = false)
  {
    $arr = [
      'January' => 'Январ' . (!$ending ? 'я' : 'ь'),
      'February' => 'Феврал' . (!$ending ? 'я' : 'ь'),
      'March' => 'Март' . (!$ending ? 'а' : ''),
      'April' => 'Апрел' . (!$ending ? 'я' : 'ь'),
      'May' => 'Ма' . (!$ending ? 'я' : 'й'),
      'June' => 'Июн' . (!$ending ? 'я' : 'ь'),
      'July' => 'Июл' . (!$ending ? 'я' : 'ь'),
      'August' => 'Август' . (!$ending ? 'а' : ''),
      'September' => 'Сентябр' . (!$ending ? 'я' : 'ь'),
      'October' => 'Октябр' . (!$ending ? 'я' : 'ь'),
      'November' => 'Ноябр' . (!$ending ? 'я' : 'ь'),
      'December' => 'Декабр' . (!$ending ? 'я' : 'ь'),
      'Sunday' => 'Воскресенье',
      'Monday' => 'Понедельник',
      'Tuesday' => 'Вторник',
      'Wednesday' => 'Среда',
      'Thursday' => 'Четверг',
      'Friday' => 'Пятница',
      'Saturday' => 'Суббота'
    ];

    if (!isset($arr[$d])) {
      return '';
    } else {
      return $arr[$d];
    }
  }

  public static function uploadImage($imagine, $image, $folder, $new = [], $errors = [], $values = '', $hash_check = true, $config) {
    $user = Yii::$app->user->identity;
    $name = $user->id . Yii::$app->security->generateRandomString(12) . '.' . $image->extension;

    if ($image->saveAs('gallery/tmpupload/' . $name, true)) {
      $tmppath = Yii::$app->basePath . '/web/gallery/tmpupload/' . $name;
      $regpath = Yii::$app->basePath . '/web/gallery/' . $folder . '/' . $name;

      $hash_exists = $hash_check ? Yii::$app->db
        ->createCommand('SELECT url FROM images_hash WHERE hash="' . md5_file($tmppath) . '" AND user_id=' . $user->id . ' LIMIT 1')
        ->queryOne() : faslse;

      $to_upload_conf = $config && $config->to_upload ? $config->to_upload : (object)[];

      if (!$hash_exists) {
        $dimensions = getimagesize($tmppath);

        if ($dimensions[0] <= 640 || $dimensions[1] <= 480) {
          if (isset($config->to_upload->{$image->name})) {
            $imagine
              ->open($tmppath)
              ->rotate($config->to_upload->{$image->name}->rotate)
              ->save($regpath, ['quality' => 40]);
          } else {
            $imagine
              ->open($tmppath)
              ->save($regpath, ['quality' => 40]);
          }
        } else {
          if (isset($config->to_upload->{$image->name})) {
            Image::resize($tmppath, 640, 480)
              ->rotate($config->to_upload->{$image->name}->rotate)
              ->save($regpath, ['quality' => 40]);
          } else {
            Image::resize($tmppath, 640, 480)
              ->save($regpath, ['quality' => 40]);
          }
        }

        $new[] = '/web/gallery/' . $folder . '/' . $name;
        $values .= ',("' . md5_file($tmppath) . '","' . '/web/gallery/' . $folder . '/' . $name . '",' . $user->id . ')';
        unlink($tmppath);
      } else {
        unlink($tmppath);
        $errors[] = $image->name;
      }
    }

    return [
      'new' => $new,
      'errors' => $errors,
      'values' => $values
    ];
  }

  public static function getImage($image, $width = '', $height = '')
  {
    return '/image/get/' .
      (is_object($image) ? '/' . $image->id : $image) .
      (!empty($width) && !empty($height) ? '?width=' . $width . '&height=' . $height : '');
  }

  public static function getImageByURL($image, $width = '', $height = '')
  {
    $tmppath = '';
    $exp = explode('_', basename($image));

    if (sizeof($exp) > 1) {
      $tmppath = $exp[0] . '_' . $exp[1];
    }

    return '/image/url?source=' .
      $image . '&tmppath=' . $tmppath . (!empty($width) && !empty($height) ? '&width=' . $width . '&height=' . $height : '');
  }

  public static function sendSMSCode($phone, $text = 'Код авторизации')
  {
    $code = 123456;//rand(100000, 999999);
    Yii::$app->session->set('tmp_code', $code);

    if (strpos('%s', $text) !== false) {
      $text = sprintf($text, $code);
    }

    /*
    $username = 'Autrazborka_drLT';
    $password = '32ST3c';

    if (strpos($phone, '+') !== false) {
      $phone = substr($phone, 1);
    }

    $ch = curl_init('https://api.br.mts.by/41/json2/simple');

    $headers = array(
      'Content-Type: application/json',
      'Accept: application/json',
      'Authorization: Basic '. base64_encode("$username:$password")
    );

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
      "phone_number" => $phone,
      "start_time" => date('Y-m-d H:i:s'),
      "tag" => "Код авторизации",
      "channels" => ["sms"],
      "channel_options" => [
        "sms" => [
          "text" => "$text: $code",
          "ttl" => 60
        ]
      ]
    ]));

    $content = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($content, true);
    $data = ['success' => true];

    if (isset($result['message_id']) && $result['message_id']) {
      $data['code'] = $code;
    } else {
      $data['success'] = false;
      $data['error'] = isset($result['error_text']) ? $data['error_text'] : $data['error_code'];
    }
    */
    $data = ['success' => true];
    $data['code'] = $code;
    return $data;
  }

  public static function recalcProductPrices($curs_values, $curs_scales, $user_id = '')
  {
    Yii::$app->db
      ->createCommand('UPDATE products p LEFT JOIN user_curs uc ON p.user_id = uc.user_id SET p.byn_price=p.price * ' . ($curs_values['USD'] / $curs_scales['USD']) . ' WHERE p.currency="USD" AND uc.use_default=1' . ($user_id ? ' AND p.user_id="' . $user_id . '"' : ''))
      ->execute();

    Yii::$app->db
      ->createCommand('UPDATE products p LEFT JOIN user_curs uc ON p.user_id = uc.user_id SET p.rub_price=p.byn_price / ' . ($curs_values['RUB'] / $curs_scales['RUB']) . ' WHERE p.currency="USD" AND uc.use_default=1' . ($user_id ? ' AND p.user_id="' . $user_id . '"' : ''))
      ->execute();

    Yii::$app->db
      ->createCommand('UPDATE products p LEFT JOIN user_curs uc ON p.user_id = uc.user_id SET p.byn_price=p.price * ' . ($curs_values['EUR'] / $curs_scales['EUR']) . ' WHERE p.currency="EUR" AND uc.use_default=1' . ($user_id ? ' AND p.user_id="' . $user_id . '"' : ''))
      ->execute();

    Yii::$app->db
      ->createCommand('UPDATE products p LEFT JOIN user_curs uc ON p.user_id = uc.user_id SET p.rub_price=p.byn_price / ' . ($curs_values['RUB'] / $curs_scales['RUB']) . ' WHERE p.currency="EUR" AND uc.use_default=1' . ($user_id ? ' AND p.user_id="' . $user_id . '"' : ''))
      ->execute();

    Yii::$app->db
      ->createCommand('UPDATE products p LEFT JOIN user_curs uc ON p.user_id = uc.user_id SET p.byn_price=p.price * ' . ($curs_values['RUB'] / $curs_scales['RUB']) . ' WHERE p.currency="RUB" AND uc.use_default=1' . ($user_id ? ' AND p.user_id="' . $user_id . '"' : ''))
      ->execute();

    Yii::$app->db
      ->createCommand('UPDATE products p LEFT JOIN user_curs uc ON p.user_id = uc.user_id SET p.rub_price=p.price WHERE p.currency="RUB" AND uc.use_default=1' . ($user_id ? ' AND p.user_id="' . $user_id . '"' : ''))
      ->execute();

    Yii::$app->db
      ->createCommand('UPDATE products p LEFT JOIN user_curs uc ON p.user_id = uc.user_id SET p.byn_price=p.price, p.rub_price=p.byn_price / ' . ($curs_values['RUB'] / $curs_scales['RUB']) . ' WHERE p.currency="BYN" AND uc.use_default=1' . ($user_id ? ' AND p.user_id="' . $user_id . '"' : ''))
      ->execute();
  }

  public static function translaterUrl($string) {
    $string = mb_strtolower(trim($string));

    if (preg_match('/[А-Яа-яЁё]/u', $string)) {
      $converter = array(
        'а' => 'a', 'б' => 'b', 'в' => 'v',
        'г' => 'g', 'д' => 'd', 'е' => 'e',
        'ё' => 'yo', 'ж' => 'zh', 'з' => 'z',
        'и' => 'i', 'й' => 'j', 'к' => 'k',
        'л' => 'l', 'м' => 'm', 'н' => 'n',
        'о' => 'o', 'п' => 'p', 'р' => 'r',
        'с' => 's', 'т' => 't', 'у' => 'u',
        'ф' => 'f', 'х' => 'kh', 'ц' => 'c',
        'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch',
        'ь' => '', 'ы' => 'y', 'ъ' => '',
        'э' => 'eh', 'ю' => 'yu', 'я' => 'ya'
      );

      $url =  strtr($string, $converter);
    } else {
      $url = $string;
    }

    $url = preg_replace('/(\-|\–)+/miu', '_', $url);
    $url = preg_replace('/(\s|\/|\\\\|,)+/miu', '-', $url);

    $xconverter = array(
      'akh' => 'ah', 'bkh' => 'bh', 'dkh' => 'dh', 'fkh' => 'fh',
      'gkh' => 'gh', 'ikh' => 'ih', 'jkh' => 'jh', 'lkh' => 'lh',
      'mkh' => 'mh', 'nkh' => 'nh', 'okh' => 'oh', 'pkh' => 'ph',
      'qkh' => 'qh', 'rkh' => 'rh', 'tkh' => 'th', 'ukh' => 'uh',
      'vkh' => 'vh', 'wkh' => 'wh', 'xkh' => 'xh', 'ykh' => 'yh'
    );

    $url =  strtr($url, $xconverter);
    return $url;
  }

  public static function isCyrillic($string)
  {
    return preg_match('/[А-Яа-яЁё]/u', $string);
  }

  public static function translater($string, $lang, $par = null, $url = false)
  {
    if ($par == 1) {
      $string = self::mb_ucfirst($string);
    }

    if (!self::isCyrillic($string)) {
      return $string;
    }

    $converter = array(
      'а' => 'a', 'б' => 'b', 'в' => 'v',
      'г' => 'g', 'д' => 'd', 'е' => 'e',
      'ё' => 'yo', 'ж' => 'zh', 'з' => 'z',
      'и' => 'i', 'й' => 'j', 'к' => 'k',
      'л' => 'l', 'м' => 'm', 'н' => 'n',
      'о' => 'o', 'п' => 'p', 'р' => 'r',
      'с' => 's', 'т' => 't', 'у' => 'u',
      'ф' => 'f', 'х' => 'h', 'ц' => 'c',
      'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch',
      'ь' => '\'', 'ы' => 'y', 'ъ' => '\'',
      'э' => 'e', 'ю' => 'yu', 'я' => 'ya',

      'А' => 'A', 'Б' => 'B', 'В' => 'V',
      'Г' => 'G', 'Д' => 'D', 'Е' => 'E',
      'Ё' => 'E', 'Ж' => 'Zh', 'З' => 'Z',
      'И' => 'I', 'Й' => 'Y', 'К' => 'K',
      'Л' => 'L', 'М' => 'M', 'Н' => 'N',
      'О' => 'O', 'П' => 'P', 'Р' => 'R',
      'С' => 'S', 'Т' => 'T', 'У' => 'U',
      'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C',
      'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sch',
      'Ь' => '\'', 'Ы' => 'Y', 'Ъ' => '\'',
      'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',
    );

    if ($url) {
      $converter = array_merge(['-' => '_', '/' => '_', ' ' => '_'], $converter);
    }

    if ($lang == 'en') {
      $converter = array_flip($converter);
      $converter['e'] = 'e';
      $converter['Е'] = 'E';
    }

    return strtr($string, $converter);
  }

  public static function getEnding($str)
  {
    $exp = explode(' ', $str);

    if (count($exp) > 1) {
      $str1 = '';

      foreach ($exp as $e) {
        $str1 .= self::changeEnd($e) . ' ';
      }

      return mb_substr($str1, 0, mb_strlen($str1) - 1);
    } else {
      return self::changeEnd($str);
    }
  }

  public static function getEndingSingle($str) {
    if (!$str) return $str;
    $exp = explode(' ', trim($str));
    $result = '';

    foreach ($exp as $part) {
      $last = mb_substr($part, -1);

      if ($last == 'а') {
        $result .= ' ' . mb_substr($part, 0, -1) . 'у';
      } else if ($last == 'я') {
        $result .= ' ' . mb_substr($part, 0, -1) . 'ю';
      } else {
        $result .= ' ' . $part;
      }
    }

    return trim($result);
  }

  public static function changeEnd($str) {
    $last = mb_substr($str, -2);
    if ($last == 'ть' || $last == 'ти') return mb_substr($str, 0, mb_strlen($str) - 1) . 'ей';
    if ($last == 'ки' || $last == 'ты') return mb_substr($str, 0, mb_strlen($str) - 1) . 'ов';
    if ($last == 'ны') return mb_substr($str, 0, mb_strlen($str) - 1);
    if ($last == 'са') return mb_substr($str, 0, mb_strlen($str) - 1);
    return $str;
  }

  public static function sanitize_namespace_slashes($str) {
    return str_replace('\\', '/', $str);
  }

  public static function unsanitize_namespace_slashes($str) {
    return str_replace('/', '\\', $str);
  }

  public static function mb_ucfirst($text)
  {
    return mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
  }
}
