<?
global $MESS;
$MESS["VBCH_CLPAY_SPCP_DTITLE"] = "CloudPayments";
$MESS["VBCH_CLPAY_SPCP_DDESCR"] = "<a href=\"http://www.http://cloudpayments.ru/\">CloudPayments</a>.<br>Приём платежей онлайн с помощью банковской карты через систему CloudPayments <Br/>
Для функционирования платежной системы в личном кабинете CloudPayments в настройках Вашего сайта пропишите данные поля<br/>
<b>Запрос на проверку платежа</b> http://#SITEURL#/bitrix/tools/cloudpayments.cloudpayment/check.php<br/>
<b>Уведомление о принятом платеже</b> http://#SITEURL#/bitrix/tools/cloudpayments.cloudpayment/pay.php<br/>
<b>Уведомление об отклоненном платежеа</b> http://#SITEURL#/bitrix/tools/cloudpayments.cloudpayment/fail.php<br/>
<b>Во всех значениях метод передачи данных POST, кодировка должна соответствовать кодировке Вашего сайта #CODING#";

$MESS['VBCH_CLPAY_APIKEY']='Public ID';
$MESS['VBCH_CLPAY_APIKEY_DESCR']='Ключ доступа (из личного кабинета CloudPayments)';
$MESS['VBCH_CLPAY_APIPASS']='Пароль для API';
$MESS['VBCH_CLPAY_APIPASS_DESCR']='Пароль доступа (из личного кабинета CloudPayments)';
$MESS['VBCH_CLPAY_PAYTYPE']='Способы оплаты';
$MESS['VBCH_CLPAY_PAYTYPE_DESCR']='Один из нескольких способов оплаты';
$MESS['VBCH_CLPAY_PAYTOSTUD']='Схемы проведения платежа';
$MESS['VBCH_CLPAY_PAYTOSTUD_DESCR']='Одностадийная или Двухстадийная оплата';
$MESS['VBCH_CLPAY_PAYTOSTUD1']="Одностадийная";
$MESS['VBCH_CLPAY_PAYTOSTUD2']="Двухстадийная";
$MESS['VBCH_CLPAY_PAYER_EMAIL']='Email покупателя';
$MESS['VBCH_CLPAY_PAYER_EMAIL_DESCR']='';
$MESS['VBCH_CLPAY_SHOULD_PAY']='Сумма заказа';
$MESS['VBCH_CLPAY_SHOULD_PAY_DESCR']='Сумма к оплате';
$MESS['VBCH_CLPAY_CURRENCY']='Валюта';
$MESS['VBCH_CLPAY_CURRENCY_DESCR']='Валюта в которой производится оплата';
$MESS['VBCH_CLPAY_DATE_INSERT']='Дата создания заказа';
$MESS['VBCH_CLPAY_DATE_INSERT_DESCR']='';
$MESS['VBCH_CLPAY_ORDER_PAY']='Номер заказа';
$MESS['VBCH_CLPAY_ORDER_PAY_DESCR']='';
$MESS['VBCH_CLPAY_WIDGET']='Виджет';
$MESS['VBCH_CLPAY_DS']='3D Secure';
$MESS['VBCH_CLPAY_SM']='Выставление счета на почту';
$MESS['VBCH_CLPAY_SB']='Оплата по подписке';
$MESS["VBCH_CLPAY_MM_DESC"]='заказ № #ORDER_ID# на "#SITE_NAME#" от #DATE#';
$MESS['VBCH_CLOUDPAY_TITLE']="Вы хотите оплатить через систему <b>CloudPayments</b>.";
$MESS['VBCH_CLOUDPAY_DESC']=" Cчет №  #ORDER_ID#  от #DATE#<br>Сумма к оплате по счету: <b>#SUMMA#</b>";
$MESS['VBCH_CLOUDPAY_BUTTON']="Оплатить";
$MESS['VBCH_CLOUDPAY_SUCCESS']='Оплата проведена успешно!';

$MESS['VBCH_CLOUDPAYMENT_5001']="Свяжитесь с вашим банком или воспользуйтесь другой картой";
$MESS['VBCH_CLOUDPAYMENT_5005']="Свяжитесь с вашим банком или воспользуйтесь другой картой";
$MESS['VBCH_CLOUDPAYMENT_5006']="Отказ сети проводить операцию или неправильный CVV код";
$MESS['VBCH_CLOUDPAYMENT_5012']="Карта не предназначена для онлайн платежей";
$MESS['VBCH_CLOUDPAYMENT_5013']="Проверьте сумму";
$MESS['VBCH_CLOUDPAYMENT_5030']="Повторите попытку позже";
$MESS['VBCH_CLOUDPAYMENT_5031']="Воспользуйтесь другой картой";
$MESS['VBCH_CLOUDPAYMENT_5034']="Свяжитесь с вашим банком или воспользуйтесь другой картой";
$MESS['VBCH_CLOUDPAYMENT_5041']="Свяжитесь с вашим банком";
$MESS['VBCH_CLOUDPAYMENT_5043']="Свяжитесь с вашим банком";
$MESS['VBCH_CLOUDPAYMENT_5051']="Недостаточно средств на карте";
$MESS['VBCH_CLOUDPAYMENT_5054']="Проверьте реквизиты или воспользуйтесь другой картой";
$MESS['VBCH_CLOUDPAYMENT_5057']="Свяжитесь с вашим банком или воспользуйтесь другой картой";
$MESS['VBCH_CLOUDPAYMENT_5065']="Свяжитесь с вашим банком или воспользуйтесь другой картой";
$MESS['VBCH_CLOUDPAYMENT_5082']="Проверьте реквизиты или воспользуйтесь другой картой";
$MESS['VBCH_CLOUDPAYMENT_5091']="Повторите попытку позже или воспользуйтесь другой картой";
$MESS['VBCH_CLOUDPAYMENT_5092']="Повторите попытку позже или воспользуйтесь другой картой";
$MESS['VBCH_CLOUDPAYMENT_5096']="Повторите попытку позже";
$MESS['VBCH_CLOUDPAYMENT_5204']="Повторите попытку позже или воспользуйтесь другой картой";
$MESS['VBCH_CLOUDPAYMENT_5206']="Свяжитесь с вашим банком или воспользуйтесь другой картой";
$MESS['VBCH_CLOUDPAYMENT_5207']="Свяжитесь с вашим банком или воспользуйтесь другой картой";
$MESS['VBCH_CLOUDPAYMENT_5300']="Воспользуйтесь другой картой";

$MESS["VBCH_CLOUDPAY_ORDERID"]="номер заказа - ";
$MESS["VBCH_CLOUDPAY_ORDERNUM"]="номер платежа - ";
$MESS["VBCH_CLOUDPAY_MESSAGE"]="сообщение - ";
$MESS["VBCH_CLOUDPAY_DATEPAY"]="дата платежа - ";
$MESS["VBCH_CLOUDPAY_ERRORCODE"]="код ошибки - ";
$MESS["VBCH_CLOUDPAY_BUYEREMAIL"]="Email покупателя - ";
$MESS["VBCH_CLOUDPAY_SUCCESCODE"]="Код подтверждения платежа - ";
?>