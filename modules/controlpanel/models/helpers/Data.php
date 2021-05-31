<?php

namespace app\modules\controlpanel\models\helpers;

use app\models\helpers\Helpers;
use app\models\products\Categories;

class Data
{
  public static function buildCategoryTree()
  {
    $arr = Categories::find()->all();
    $n = count($arr);
    $i = 0;

    $similar_found = [];

    foreach ($arr as $cat_ind => $cat) {
      $similar = Categories::find()
        ->where('(name LIKE "' . $cat->name . ' %" OR parent_name="' . $cat->name . '") AND category_id!="' . $cat->category_id . '"')
        ->all();

      $connected = '';
      $url_connected = '';

      foreach ($similar as $ind => $s) {
        $connected .= $s->name . ';';
        $cat_url = $s->url;

        if ($s->connected_attributes) {
          $cat_url = $s->parent_url;

          foreach ($s->connected_attributes as $ca) {
            $cat_url .= '/' . $ca->url;
          }
        }

        $url_connected .= $cat_url . ';';
        $synonyms = explode(';', $s->synonym);
        $target_synonyms = '';
        $target_synonym_urls = '';

        foreach ($synonyms as $syn) {
          if (!$syn || empty($syn)) continue;

          if (mb_strpos($syn, $cat->name . ';') !== false || mb_strpos($syn, ';' . $cat->name) !== false || $syn === $cat->name) {
            $s->connected_category .= Helpers::mb_ucfirst($cat->name) . ';';
            $s->connected_category_url .= $cat_url;
          } else if (mb_strpos($cat->synonym, $syn . ';') === false && mb_strpos($cat->synonym, ';' . $syn) === false) {
            $syn = Helpers::mb_ucfirst($syn);

            if (
              (mb_strpos($target_synonyms, $syn . ';') === false) &&
              (mb_strpos($target_synonyms, ';' . $syn) === false)
            ) {
              $target_synonyms .= $syn . ';';
              $target_synonym_urls .= Categories::createUrlFromCyrillic($syn) . ';';
            }

            if (
              (mb_strpos($connected, $syn . ';') === false) &&
              (mb_strpos($connected, ';' . $syn) === false)
            ) {
              $connected .= $syn . ';';
              $url_connected .= $cat_url . ';';
            }
          }
        }

        if ($target_synonyms) {
          $s->synonym = mb_substr($target_synonyms, 0, -1);
          $s->synonym_url = substr($target_synonym_urls, 0, -1);
        } else {
          $s->synonym = '';
          $s->synonym_url = '';
        }

        if ($s->connected_category) {
          $s->connected_category = mb_substr($s->connected_category, 0, -1);
          $s->connected_category_url = substr($s->connected_category_url, 0, -1);
        } else {
          $s->connected_category = '';
          $s->connected_category_url = '';
        }

        $similar_found[$s->category_id] = $s;
        $s->save();
      }

      $side_words = [
        'левый' => 'правый',
        'правый' => 'левый',
        'левая' => 'правая',
        'правая' => 'левая',
        'левое' => 'правое',
        'правое' => 'левое'
      ];

      foreach ($side_words as $from => $to) {
        $replaced = self::replace_category_side_word($cat, $s, $from, $to);

        if ($replaced) {
          $connected .= $replaced[0];
          $url_connected .= $replaced[1];
        }
      }

      if (isset($similar_found[$cat->category_id])) {
        $synonym_urls = explode(';', $similar_found[$cat->category_id]->synonym_url);
        $syn_names = explode(';', $similar_found[$cat->category_id]->synonym);

        $cat_syns = explode(';', $cat->synonym);
        $cat_syn_urls = explode(';', $cat->synonym_url);

        foreach ($cat_syns as $key => $syn_name) {
          if (!in_array($syn_name, $syn_names)) {
            if (isset($cat_syn_urls[$key])) {
              $syn_names[] = $syn_name;
              $synonym_urls[] = $cat_syn_urls[$key];
            }
          }
        }
      } else {
        $synonym_urls = explode(';', $cat->synonym_url);
        $syn_names = explode(';', $cat->synonym);
      }

      $target_syn_names = '';
      $target_synonyms = '';
      $syns = '';

      foreach ($syn_names as $key => $syn) {
        $syns .= ' ' . $syn;

        if ($syn && !empty(trim($syn))) {
          //$syn = Categories::createUrl($syn);

          if (
            (mb_strpos($target_syn_names, $syn . ';') === false) &&
            (mb_strpos($target_syn_names, ';' . $syn) === false)
          ) {
            $target_synonyms .= $synonym_urls[$key] . ';';
            $target_syn_names .= Helpers::mb_ucfirst($syn) . ';';
          }
        }
      }

      $cat->synonym = mb_substr($target_syn_names, 0, -1);
      $cat->synonym_url = substr($target_synonyms, 0, -1);
      $cat->connected_category = mb_substr($connected, 0, -1);
      $cat->connected_category_url = substr($url_connected, 0, -1);
      $cat->save();
      $i++;
    }

    return $i == $n;
  }

  private static function replace_category_side_word($cat, $similar_cat, $from, $to)
  {
    if (mb_strpos($cat->name, $from) !== false) {
      $alt = str_replace($from, $to, $similar_cat->name);

      return [
        $alt . ';',
        Categories::createUrlFromCyrillic($alt) . ';'
      ];
    }

    return [];
  }
}