<?php
$MESS['SALE_HPS_CLOUDPAYMENT'] = 'CloudPayments';
$MESS["SALE_HPS_CLOUDPAYMENT_SHOP_ID"] = "Public ID";
$MESS["SALE_HPS_CLOUDPAYMENT_SHOP_ID_DESC"] = "Ключ доступа (из личного кабинета CloudPayments)";
$MESS["SALE_HPS_CLOUDPAYMENT_PAYMENT_ID"] = "Код заказа (ID)";
$MESS["SALE_HPS_CLOUDPAYMENT_PAY_ID"] = "Код платежа (ID)";
$MESS["CONNECT_SETTINGS_CLOUDPAYMENT"]='Настройки платежной системы';
$MESS["SALE_HPS_CLOUDPAYMENT_SHOP_KEY"] = "Пароль для API";
$MESS["SALE_HPS_CLOUDPAYMENT_SHOP_KEY_DESC"] = "Пароль доступа (из личного кабинета CloudPayments)";
$MESS["SALE_HPS_CLOUDPAYMENT_CHECKONLINE"]="Использовать функционал онлайн касс";
$MESS["SALE_HPS_CLOUDPAYMENT_CHECKONLINE_DESC"]="Данный функционал должен быть включен на стороне CloudPayments";
$MESS["SALE_HPS_CLOUDPAYMENT_SHOULD_PAY"] = "Сумма к оплате";
$MESS["SALE_HPS_CLOUDPAYMENT_PAYMENT_DATE"] = "Дата создания заказа";
$MESS["SALE_HPS_CLOUDPAYMENT_IS_TEST"] = "Тестовый режим";
$MESS["SALE_HPS_CLOUDPAYMENT_CHANGE_STATUS_PAY"] = "Автоматически оплачивать заказ при получении успешного статуса оплаты";
$MESS["SALE_HPS_CLOUDPAYMENT_PAYMENT_TYPE"] = "Тип платёжной системы";
$MESS["SALE_HPS_CLOUDPAYMENT_BUYER_ID"] = "Код покупателя";
$MESS["SALE_HPS_CLOUDPAYMENT_BUYER_EMAIL"] = "Email покупателя";
$MESS["SALE_HPS_CLOUDPAYMENT_BUYER_PHONE"] = "Телефон покупателя";
$MESS["SALE_HPS_CLOUDPAYMENT_CURRENCY"]="Валюта заказа";
$MESS["SALE_HPS_CLOUDPAYMENT_RETURN"] = "Возвраты платежей не поддерживаются";
$MESS["SALE_HPS_CLOUDPAYMENT_RESTRICTION"] = "Ограничение по сумме платежей зависит от способа оплаты, который выберет покупатель";
$MESS["SALE_HPS_CLOUDPAYMENT_COMMISSION"] = "Без комисси для покупателя";
$MESS["SALE_HPS_CLOUDPAYMENT_INN"]="ИНН организации";
$MESS["SALE_HPS_CLOUDPAYMENT_INN_DESC"]="ИНН вашей организации или ИП, на который зарегистрирована касса";
$MESS["SALE_HPS_CLOUDPAYMENT_TYPE_NALOG"]='Тип системы налогообложения';
$MESS["SALE_HPS_CLOUDPAYMENT_TYPE_NALOG_DESC"]='Указанная система налогообложения должна совпадать с одним из вариантов, зарегистрированных в ККТ.';
$MESS["SALE_HPS_NALOG_TYPE_0"]="Общая система налогообложения";
$MESS["SALE_HPS_NALOG_TYPE_1"]="Упрощенная система налогообложения (Доход)";
$MESS["SALE_HPS_NALOG_TYPE_2"]="Упрощенная система налогообложения (Доход минус Расход)";
$MESS["SALE_HPS_NALOG_TYPE_3"]="Единый налог на вмененный доход";
$MESS["SALE_HPS_NALOG_TYPE_4"]="Единый сельскохозяйственный налог";                
$MESS["SALE_HPS_NALOG_TYPE_5"]="Патентная система налогообложения";
$MESS["VBCH_CLPAY_SPCP_DDESCR"] = "<a href=\"http://www.http://cloudpayments.ru/\">CloudPayments</a>.<br>Приём платежей онлайн с помощью банковской карты через систему CloudPayments <Br/>
Зайти в личный кабинет CloudPayments и исправить пути: <br/>
&nbsp;&nbsp;	Настройки Сheck уведомлений: http://".$_SERVER['HTTP_HOST']."/bitrix/tools/sale_ps_result.php?action=check<br/>
&nbsp;&nbsp;	Настройки Pay уведомлений: http://".$_SERVER['HTTP_HOST']."/bitrix/tools/sale_ps_result.php?action=pay<br/>
&nbsp;&nbsp;	Настройки Fail уведомлений: http://".$_SERVER['HTTP_HOST']."/bitrix/tools/sale_ps_result.php?action=fail<br/>";
$MESS["SALE_HPS_CLOUDPAYMENT_TYPE_SYSTEM"] = "Тип схемы проведения платежей";
$MESS["SALE_HPS_TYPE_SCHEME_0"]="Одностадийная оплата";
$MESS["SALE_HPS_TYPE_SCHEME_1"]="Двухстадийная оплата";

$MESS["SALE_HPS_CLOUDPAYMENT_SUCCESS_URL"]="Success URL";
$MESS["SALE_HPS_CLOUDPAYMENT_SUCCESS_URL_DESC"]="";
$MESS["SALE_HPS_CLOUDPAYMENT_FAIL_URL"]="Fail URL";
$MESS["SALE_HPS_CLOUDPAYMENT_FAIL_URL_DESC"]="";
$MESS["SALE_HPS_CLOUDPAYMENT_WIDGET_LANG"]="Язык виджета";
$MESS["SALE_HPS_CLOUDPAYMENT_WIDGET_LANG_DESC"]="";

$MESS["SALE_HPS_WIDGET_LANG_TYPE_0"]="Русский MSK";	
$MESS["SALE_HPS_WIDGET_LANG_TYPE_1"]="Английский CET";	
$MESS["SALE_HPS_WIDGET_LANG_TYPE_2"]="Латышский CET";	
$MESS["SALE_HPS_WIDGET_LANG_TYPE_3"]="Азербайджанский AZT";	
$MESS["SALE_HPS_WIDGET_LANG_TYPE_4"]="Русский ALMT";
$MESS["SALE_HPS_WIDGET_LANG_TYPE_5"]="Казахский ALMT";	
$MESS["SALE_HPS_WIDGET_LANG_TYPE_6"]="Украинский EET";
$MESS["SALE_HPS_WIDGET_LANG_TYPE_7"]="Польский CET";	
$MESS["SALE_HPS_WIDGET_LANG_TYPE_8"]="Португальский CET";	

$MESS["SALE_HPS_CLOUDPAYMENT_VAT_DELIVERY"]="Выберите НДС на доставку, если необходимо";
$MESS["SALE_HPS_CLOUDPAYMENT_VAT_DELIVERY_DESC"]="";

$MESS["VAT"]="Выберите НДС на доставку, если необходимо";
$MESS["NOT_VAT"]="Без НДС";

$MESS["DELIVERY_VAT1"]="НДС 18%";
$MESS["DELIVERY_VAT2"]="НДС 10%";
$MESS["DELIVERY_VAT3"]="НДС 0%";
$MESS["DELIVERY_VAT4"]="расчетный НДС 10/110";
$MESS["DELIVERY_VAT5"]="расчетный НДС 18/118";


$MESS["STATUS_GROUP"]="Статусы";
$MESS["STATUS_PAY"]="Статус оплачен";
$MESS["STATUS_CHANCEL"]="Статус возврата платежа";
$MESS["STATUS_AUTHORIZE"]="Статус подтверждения авторизации платежа (двухстадийные платежи)";
$MESS["STATUS_AU"]="Статус авторизованного платежа (двухстадийные платежи)";
$MESS["STATUS_VOID"]="Статус отмена авторизованного платежа (двухстадийные платежи)";
$MESS["STATUS_PARTIAL_PAY"]="Статус частичной оплаты";
