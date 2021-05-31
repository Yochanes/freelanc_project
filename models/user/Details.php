<?php

namespace app\models\user;

use Yii;

class Details extends \yii\db\ActiveRecord
{

  private $waranty_default = '{"engine":{"checked":"0","value":"","label":"\u0414\u0432\u0438\u0433\u0430\u0442\u0435\u043b\u0438"},"mkpp":{"checked":"0","value":"","label":"\u041c\u041a\u041f\u041f"},"akpp":{"checked":"0","value":"","label":"\u0410\u041a\u041f\u041f"},"wheels":{"checked":"0","value":"","label":"\u041a\u043e\u043b\u0435\u0441\u0430 (\u0448\u0438\u043d\u044b \u0438 \u0434\u0438\u0441\u043a\u0438)"},"other":{"checked":"0","value":"","label":"\u041f\u0440\u043e\u0447\u0438\u0435 \u0434\u0435\u0442\u0430\u043b\u0438"},"missing":{"checked":"0","value":"","label":"\u0413\u0430\u0440\u0430\u043d\u0442\u0438\u044f \u043d\u0435 \u0440\u0430\u0441\u043f\u0440\u043e\u0441\u0442\u0440\u0430\u043d\u044f\u0435\u0442\u0441\u044f"},"conditions":{"checked":"0","value":"","label":"\u0414\u043e\u043f\u043e\u043b\u043d\u0438\u0442\u0435\u043b\u044c\u043d\u044b\u0435 \u0443\u0441\u043b\u043e\u0432\u0438\u044f"}}';
  private $refund_default = '{"default":{"checked":"0","value":"","label":"\u041f\u0440\u0438 \u043b\u044e\u0431\u044b\u0445 \u0432\u043e\u043f\u0440\u043e\u0441\u0430\u0445 \u043e \u0432\u043e\u0437\u0432\u0440\u0430\u0442\u0435, \u0441\u0432\u044f\u0437\u044b\u0432\u0430\u0439\u0442\u0435\u0441\u044c \u0441 \u043d\u0430\u043c\u0438 \u043f\u043e \u043d\u043e\u043c\u0435\u0440\u0443"},"allowed":{"checked":"0","value":"","label":"\u0412\u043e\u0437\u0432\u0440\u0430\u0442\u0443 \u043f\u043e\u0434\u043b\u0435\u0436\u0438\u0442 \u0442\u043e\u0432\u0430\u0440"},"missing":{"checked":"0","value":"","label":"\u0412\u043e\u0437\u0432\u0440\u0430\u0442\u0443 \u043d\u0435 \u043f\u043e\u0434\u043b\u0435\u0436\u0438\u0442"},"conditions":{"checked":"0","value":"","label":"\u0423\u0441\u043b\u043e\u0432\u0438\u044f \u0434\u043b\u044f \u0432\u043e\u0437\u0432\u0440\u0430\u0442\u0430"},"case":{"checked":"0","value":"","label":"\u0412 \u0441\u043b\u0443\u0447\u0430\u0435 \u0432\u043e\u0437\u0432\u0440\u0430\u0442\u0430"},"flat":{"checked":"0","value":"","label":"\u0412\u043e\u0437\u0432\u0440\u0430\u0442 \u0442\u043e\u0432\u0430\u0440\u0430 \u043f\u0440\u0438 \u0441\u0430\u043c\u043e\u0432\u044b\u0432\u043e\u0437\u0435:"},"delivery":{"checked":"0","value":"","label":"\u0412\u043e\u0437\u0432\u0440\u0430\u0442 \u0442\u043e\u0432\u0430\u0440\u0430 \u043f\u0440\u0438 \u0434\u043e\u0441\u0442\u0430\u0432\u043a\u0435 \u043f\u043e \u0433\u043e\u0440\u043e\u0434\u0443:"},"shipping":{"checked":"0","value":"","label":"\u0412\u043e\u0437\u0432\u0440\u0430\u0442 \u0442\u043e\u0432\u0430\u0440\u0430 \u043f\u0440\u0438 \u0434\u043e\u0441\u0442\u0430\u0432\u043a\u0435 \u0442\u0440\u0430\u043d\u0441\u043f\u043e\u0440\u0442\u043d\u043e\u0439 \u043a\u043e\u043c\u043f\u0430\u043d\u0438\u0435\u0439:"},"additional":{"checked":"0","value":"","label":"\u0414\u043e\u043f\u043e\u043b\u043d\u0438\u0442\u0435\u043b\u044c\u043d\u043e"}}';
  private $conditions_default = '{"conditions":{"checked":"0","value":"","label":"\u0414\u043e\u043f\u043e\u043b\u043d\u0438\u0442\u0435\u043b\u044c\u043d\u044b\u0435 \u0443\u0441\u043b\u043e\u0432\u0438\u044f"}}';
  private $payment_default = '{"default":{"checked":"0","value":"","label":"\u041f\u0440\u0438 \u043b\u044e\u0431\u044b\u0445 \u0432\u043e\u043f\u0440\u043e\u0441\u0430\u0445 \u043f\u043e \u043e\u043f\u043b\u0430\u0442\u0435, \u0441\u0432\u044f\u0437\u044b\u0432\u0430\u0439\u0442\u0435\u0441\u044c \u0441 \u043d\u0430\u043c\u0438 \u043f\u043e \u043d\u043e\u043c\u0435\u0440\u0443"},"nal":{"checked":"0","value":"","label":"\u041e\u043f\u043b\u0430\u0442\u0430 \u043d\u0430\u043b\u0438\u0447\u043d\u044b\u043c\u0438"},"card":{"checked":"0","value":"","label":"\u041e\u043f\u043b\u0430\u0442\u0430 \u043a\u0430\u0440\u0442\u043e\u0439"},"send":{"checked":"0","value":"","label":"\u041e\u043f\u043b\u0430\u0442\u0430 \u043f\u0435\u0440\u0435\u0432\u043e\u0434\u043e\u043c \u043d\u0430 \u043a\u0430\u0440\u0442\u0443"},"transfer":{"checked":"0","value":"","label":"\u041e\u043f\u043b\u0430\u0442\u0430 \u043f\u043e \u0431\u0435\u0437\u043d\u0430\u043b\u0443"},"conditions":{"checked":"0","value":"","label":"\u0414\u043e\u043f\u043e\u043b\u043d\u0438\u0442\u0435\u043b\u044c\u043d\u044b\u0435 \u0443\u0441\u043b\u043e\u0432\u0438\u044f"},"free":{"checked":"0","value":"","label":"\u0411\u0435\u0441\u043f\u043b\u0430\u0442\u043d\u043e\u0435 \u043f\u0435\u0440\u0435\u043c\u0435\u0449\u0435\u043d\u0438\u0435 \u0442\u043e\u0432\u0430\u0440\u0430 \u043c\u0435\u0436\u0434\u0443 \u043d\u0430\u0448\u0438\u043c\u0438 \u043f\u0440\u0435\u0434\u0441\u0442\u0430\u0432\u0438\u0442\u0435\u043b\u044c\u0441\u0442\u0432\u0430\u043c\u0438"},"additional":{"checked":"0","value":"","label":"\u0414\u043e\u043f\u043e\u043b\u043d\u0438\u0442\u0435\u043b\u044c\u043d\u043e"}}';
  private $paymcity_default = '{"nal":{"checked":"0","value":"","label":"\u041e\u043f\u043b\u0430\u0442\u0430 \u043d\u0430\u043b\u0438\u0447\u043d\u044b\u043c\u0438"},"card":{"checked":"0","value":"","label":"\u041e\u043f\u043b\u0430\u0442\u0430 \u043a\u0430\u0440\u0442\u043e\u0439"},"send":{"checked":"0","value":"","label":"\u041e\u043f\u043b\u0430\u0442\u0430 \u043f\u0435\u0440\u0435\u0432\u043e\u0434\u043e\u043c \u043d\u0430 \u043a\u0430\u0440\u0442\u0443"},"pre":{"checked":"0","value":"","label":"\u041d\u0443\u0436\u043d\u0430 \u043f\u0440\u0435\u0434\u043e\u043f\u043b\u0430\u0442\u0430"},"additional":{"checked":"0","value":"","label":"\u0414\u043e\u043f\u043e\u043b\u043d\u0438\u0442\u0435\u043b\u044c\u043d\u043e"}}';
  private $paymship_default = '{"pick":{"checked":"0","value":"","label":"\u041f\u043e\u043b\u043d\u0430\u044f \u043e\u043f\u043b\u0430\u0442\u0430 \u043f\u0440\u0438 \u043f\u043e\u043b\u0443\u0447\u0435\u043d\u0438\u0438 (\u043d\u0430\u043b\u043e\u0436\u0435\u043d\u043d\u044b\u043c \u043f\u043b\u0430\u0442\u0435\u0436\u043e\u043c)"},"send":{"checked":"0","value":"","label":"\u041f\u043e\u043b\u043d\u0430\u044f \u043e\u043f\u043b\u0430\u0442\u0430 \u043f\u043e\u0441\u043b\u0435 \u043e\u0442\u043f\u0440\u0430\u0432\u043a\u0438 \u0442\u043e\u0432\u0430\u0440\u0430"},"card":{"checked":"0","value":"","label":"\u041e\u043f\u043b\u0430\u0442\u0430 \u043f\u0435\u0440\u0435\u0432\u043e\u0434\u043e\u043c \u043d\u0430 \u043a\u0430\u0440\u0442\u0443"},"pre":{"checked":"0","value":"","label":"\u0420\u0430\u0431\u043e\u0442\u0430 \u043f\u043e \u043f\u0440\u0435\u0434\u043e\u043f\u043b\u0430\u0442\u0435"},"additional":{"checked":"0","value":"","label":"\u0414\u043e\u043f\u043e\u043b\u043d\u0438\u0442\u0435\u043b\u044c\u043d\u043e"}}';
  private $delivery_default = '{"our":{"checked":"0","value":"","label":"\u0423 \u043d\u0430\u0441 \u0441\u043e\u0431\u0441\u0442\u0432\u0435\u043d\u043d\u0430\u044f \u0441\u043b\u0443\u0436\u0431\u0430 \u0434\u043e\u0441\u0442\u0430\u0432\u043a\u0438"},"questions":{"checked":"0","value":"","label":"\u0427\u0442\u043e\u0431\u044b \u0443\u0442\u043e\u0447\u043d\u0438\u0442\u044c \u0432\u043e\u043f\u0440\u043e\u0441\u044b \u043f\u043e \u0434\u043e\u0441\u0442\u0430\u0432\u043a\u0435, \u0437\u0432\u043e\u043d\u0438\u0442\u0435 \u043d\u0430\u043c \u043f\u043e \u043d\u043e\u043c\u0435\u0440\u0443"},"pickup":{"checked":"0","value":"","label":"\u0421\u0430\u043c\u043e\u0432\u044b\u0432\u043e\u0437 \u043f\u043e \u0430\u0434\u0440\u0435\u0441\u0443"},"delivery":{"checked":"0","value":"","label":"\u0414\u043e\u0441\u0442\u0430\u0432\u043a\u0430 \u043f\u043e \u0433\u043e\u0440\u043e\u0434\u0443"},"shipping":{"checked":"0","value":"","label":"\u0414\u043e\u0441\u0442\u0430\u0432\u043a\u0430 \u0434\u043e \u0442\u0440\u0430\u043d\u0441\u043f\u043e\u0440\u0442\u043d\u043e\u0439 \u043a\u043e\u043c\u043f\u0430\u043d\u0438\u0438"},"free":{"checked":"0","value":"","label":"\u0411\u0435\u0441\u043f\u043b\u0430\u0442\u043d\u043e\u0435 \u043f\u0435\u0440\u0435\u043c\u0435\u0449\u0435\u043d\u0438\u0435 \u0442\u043e\u0432\u0430\u0440\u0430 \u043c\u0435\u0436\u0434\u0443 \u043d\u0430\u0448\u0438\u043c\u0438 \u043f\u0440\u0435\u0434\u0441\u0442\u0430\u0432\u0438\u0442\u0435\u043b\u044c\u0441\u0442\u0432\u0430\u043c\u0438"},"additional":{"checked":"0","value":"","label":"\u0414\u043e\u043f\u043e\u043b\u043d\u0438\u0442\u0435\u043b\u044c\u043d\u043e"}}';

  public static function tableName()
  {
    return 'user_details';
  }

  public function rules()
  {
    return [
      [['address'], 'string', 'max' => 255],
      [['call_time', 'sklad_time', 'office_time'], 'string', 'max' => 500],
    ];
  }

  public function afterFind()
  {
    parent::afterFind();
    $this->waranty = $this->waranty != 'null' ? json_decode($this->waranty, true) : json_decode($this->waranty_default, true);
    $this->refund = $this->refund != 'null' ? json_decode($this->refund, true) : json_decode($this->refund_default, true);
    $this->conditions = $this->conditions != 'null' ? json_decode($this->conditions, true) : json_decode($this->conditions_default, true);
    $this->payment = $this->payment != 'null' ? json_decode($this->payment, true) : json_decode($this->payment_default, true);
    $this->paymcity = $this->paymcity != 'null' ? json_decode($this->paymcity, true) : json_decode($this->paymcity_default, true);
    $this->paymship = $this->paymship != 'null' ? json_decode($this->paymship, true) : json_decode($this->paymship_default, true);
    $this->delivery = $this->delivery != 'null' ? json_decode($this->delivery, true) : json_decode($this->delivery_default, true);
  }

  public function beforeSave($insert)
  {
    if (parent::beforeSave($insert)) {
      $this->waranty = json_encode($this->waranty);
      $this->refund = json_encode($this->refund);
      $this->conditions = json_encode($this->conditions);
      $this->payment = json_encode($this->payment);
      $this->paymcity = json_encode($this->paymcity);
      $this->paymship = json_encode($this->paymship);
      $this->delivery = json_encode($this->delivery);
      $this->address = htmlspecialchars($this->address);
      return true;
    }

    return false;
  }
}
