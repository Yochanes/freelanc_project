<?php

namespace app\models\helpers;

use Yii;

class Geo
{
    const API_TOKEN = '2c8636827b4b594e1be44a4ddee135c6c989849a';

	public static function getLocationByIp($ip)
	{
		$host = 'http://suggestions.dadata.ru/suggestions/api/4_1/rs/iplocate/address?ip=' . $ip;
		$authorization = 'Authorization: Token ' . self::API_TOKEN;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $host);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', $authorization));
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$resp = curl_exec($ch);
		$result = json_decode($resp);
		curl_close($ch);
		return $result;
	}

	public static function getLocationByAddress($addr)
	{
		$params = json_encode(array(
			'query' => $addr,
			'count' => 1
		));

		$host = $host = 'http://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/address';
		$authorization = 'Authorization: Token ' . self::API_TOKEN;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $host);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Accept: application/json', $authorization));
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$resp = curl_exec($ch);
		$result = json_decode($resp);
		curl_close($ch);
		return $result;
	}
}
