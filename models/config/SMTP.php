<?php

namespace app\models\config;

use Yii;

class SMTP extends \yii\db\ActiveRecord
{

  public static function tableName()
  {
    return 'config_smtp';
  }

  public function rules()
  {
    return [
      [['site_email', 'host', 'username', 'password', 'port', 'active'], 'required'],
      [['site_email', 'host', 'username', 'password'], 'string', 'max' => 100],
      ['active', 'boolean'],
      [['port', 'id'], 'integer']
    ];
  }

  public function sendEmail($to, $subject = '', $text = '', $html = '')
  {
    $transport = \Swift_SmtpTransport::newInstance($this->host, $this->port)
      ->setUserName($this->username)
      ->setPassword($this->password);

    Yii::$app->mailer->setTransport($transport);

    Yii::$app->mailer
      ->compose()
      ->setFrom($this->site_email)
      ->setTo($to)
      ->setSubject($subject)
      ->setTextBody($text)
      ->setHtmlBody($html ? $html : $text)
      ->send();
  }
}
