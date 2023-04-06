<?php
  $MESS['SALE_HPS_CLOUDPAYMENT'] = 'CloudPayments';
  $MESS["SALE_HPS_CLOUDPAYMENT_SHOP_ID"] = "Public ID";
  $MESS["SALE_HPS_CLOUDPAYMENT_SHOP_ID_DESC"] = "Ключ доступа (из личного кабинета CloudPayments)";
  $MESS["SALE_HPS_CLOUDPAYMENT_PAYMENT_ID"] = "Код заказа (ID)";
  $MESS["SALE_HPS_CLOUDPAYMENT_PAY_ID"] = "Код платежа (ID)";
  $MESS["CONNECT_SETTINGS_CLOUDPAYMENT"] = 'Настройки платежной системы';
  $MESS["SALE_HPS_CLOUDPAYMENT_SHOP_KEY"] = "Пароль для API";
  $MESS["SALE_HPS_CLOUDPAYMENT_SHOP_KEY_DESC"] = "Пароль доступа (из личного кабинета CloudPayments)";
  $MESS["SALE_HPS_CLOUDPAYMENT_CHECKONLINE"] = "Использовать функционал онлайн касс CloudKassir";
  $MESS["SALE_HPS_CLOUDPAYMENT_CHECKONLINE_DESC"] = "Данный функционал должен быть включен на стороне CloudPayments";
  $MESS["SALE_HPS_CLOUDPAYMENT_SHOULD_PAY"] = "Сумма к оплате";
  $MESS["SALE_HPS_CLOUDPAYMENT_PAYMENT_DATE"] = "Дата создания заказа";
  $MESS["SALE_HPS_CLOUDPAYMENT_IS_TEST"] = "Тестовый режим";
  $MESS["SALE_HPS_CLOUDPAYMENT_CHANGE_STATUS_PAY"] = "Автоматически оплачивать заказ при получении успешного статуса оплаты";
  $MESS["SALE_HPS_CLOUDPAYMENT_PAYMENT_TYPE"] = "Тип платёжной системы";
  $MESS["SALE_HPS_CLOUDPAYMENT_BUYER_ID"] = "Код покупателя";
  $MESS["SALE_HPS_CLOUDPAYMENT_BUYER_EMAIL"] = "Email покупателя";
  $MESS["SALE_HPS_CLOUDPAYMENT_BUYER_PHONE"] = "Телефон покупателя";
  $MESS["SALE_HPS_CLOUDPAYMENT_CURRENCY"] = "Валюта заказа";
  $MESS["SALE_HPS_CLOUDPAYMENT_RETURN"] = "Возвраты платежей не поддерживаются";
  $MESS["SALE_HPS_CLOUDPAYMENT_RESTRICTION"] = "Ограничение по сумме платежей зависит от способа оплаты, который выберет покупатель";
  $MESS["SALE_HPS_CLOUDPAYMENT_COMMISSION"] = "Без комисси для покупателя";
  $MESS["SALE_HPS_CLOUDPAYMENT_INN"] = "ИНН организации";
  $MESS["SALE_HPS_CLOUDPAYMENT_INN_DESC"] = "ИНН вашей организации или ИП, на который зарегистрирована касса";
  $MESS["SALE_HPS_CLOUDPAYMENT_TYPE_NALOG"] = 'Тип системы налогообложения';
  $MESS["SALE_HPS_CLOUDPAYMENT_TYPE_NALOG_DESC"] = 'Указанная система налогообложения должна совпадать с одним из вариантов, зарегистрированных в ККТ.';
  $MESS["SALE_HPS_NALOG_TYPE_0"] = "Общая система налогообложения";
  $MESS["SALE_HPS_NALOG_TYPE_1"] = "Упрощенная система налогообложения (Доход)";
  $MESS["SALE_HPS_NALOG_TYPE_2"] = "Упрощенная система налогообложения (Доход минус Расход)";
  $MESS["SALE_HPS_NALOG_TYPE_3"] = "Единый налог на вмененный доход";
  $MESS["SALE_HPS_NALOG_TYPE_4"] = "Единый сельскохозяйственный налог";
  $MESS["SALE_HPS_NALOG_TYPE_5"] = "Патентная система налогообложения";
  $MESS["VBCH_CLPAY_SPCP_DDESCR"] = "<a href=\"https://cloudpayments.ru/\">CloudPayments</a>.<br>Приём платежей онлайн с помощью банковской карты через систему CloudPayments <Br/>
Зайти в личный кабинет CloudPayments и исправить пути: <br/>
&nbsp;&nbsp;	Настройки Сheck уведомлений: https://" . $_SERVER['HTTP_HOST'] . "/bitrix/tools/sale_ps_result.php?action=check<br/>
&nbsp;&nbsp;	Настройки Pay уведомлений: https://" . $_SERVER['HTTP_HOST'] . "/bitrix/tools/sale_ps_result.php?action=pay<br/>
&nbsp;&nbsp;	Настройки Fail уведомлений: https://" . $_SERVER['HTTP_HOST'] . "/bitrix/tools/sale_ps_result.php?action=fail<br/>
&nbsp;&nbsp;	Настройки Confirm уведомлений: https://" . $_SERVER['HTTP_HOST'] . "/bitrix/tools/sale_ps_result.php?action=confirm<br/>
&nbsp;&nbsp;	Настройки Refund уведомлений: https://" . $_SERVER['HTTP_HOST'] . "/bitrix/tools/sale_ps_result.php?action=refund<br/>
&nbsp;&nbsp;	Настройки Сancel уведомлений: https://" . $_SERVER['HTTP_HOST'] . "/bitrix/tools/sale_ps_result.php?action=cancel<br/>";

  $MESS["SALE_HPS_CLOUDPAYMENT_TYPE_SYSTEM"] = "Тип схемы проведения платежей";
  $MESS["SALE_HPS_TYPE_SCHEME_0"] = "Одностадийная оплата";
  $MESS["SALE_HPS_TYPE_SCHEME_1"] = "Двухстадийная оплата";

  $MESS["SALE_HPS_CLOUDPAYMENT_SUCCESS_URL"] = "Success URL";
  $MESS["SALE_HPS_CLOUDPAYMENT_SUCCESS_URL_DESC"] = "";
  $MESS["SALE_HPS_CLOUDPAYMENT_FAIL_URL"] = "Fail URL";
  $MESS["SALE_HPS_CLOUDPAYMENT_FAIL_URL_DESC"] = "";
  $MESS["SALE_HPS_CLOUDPAYMENT_WIDGET_LANG"] = "Язык виджета";
  $MESS["SALE_HPS_CLOUDPAYMENT_WIDGET_LANG_DESC"] = "";

  $MESS["SALE_HPS_WIDGET_LANG_TYPE_0"] = "Русский MSK";
  $MESS["SALE_HPS_WIDGET_LANG_TYPE_1"] = "Английский CET";
  $MESS["SALE_HPS_WIDGET_LANG_TYPE_2"] = "Латышский CET";
  $MESS["SALE_HPS_WIDGET_LANG_TYPE_3"] = "Азербайджанский AZT";
  $MESS["SALE_HPS_WIDGET_LANG_TYPE_4"] = "Русский ALMT";
  $MESS["SALE_HPS_WIDGET_LANG_TYPE_5"] = "Казахский ALMT";
  $MESS["SALE_HPS_WIDGET_LANG_TYPE_6"] = "Украинский EET";
  $MESS["SALE_HPS_WIDGET_LANG_TYPE_7"] = "Польский CET";
  $MESS["SALE_HPS_WIDGET_LANG_TYPE_8"] = "Португальский CET";
  $MESS["SALE_HPS_WIDGET_LANG_TYPE_9"] = "Чешский CET";
  $MESS["SALE_HPS_WIDGET_LANG_TYPE_10"] = "Вьетнамский ICT";
  $MESS["SALE_HPS_WIDGET_LANG_TYPE_11"] = "Турецкий TRT";
  $MESS["SALE_HPS_WIDGET_LANG_TYPE_12"] = "Испанский CET";
  $MESS["SALE_HPS_WIDGET_LANG_TYPE_13"] = "Узбекский UZT";


  $MESS["SALE_HPS_CLOUDPAYMENT_VAT_DELIVERY"] = "Выберите НДС на доставку, если необходимо";
  $MESS["SALE_HPS_CLOUDPAYMENT_VAT_DELIVERY_DESC"] = "";

  $MESS["VAT"] = "Выберите НДС на доставку, если необходимо";
  $MESS["NOT_VAT"] = "Без НДС";

  $MESS["DELIVERY_VAT1"] = "НДС 20%";
  $MESS["DELIVERY_VAT2"] = "НДС 10%";
  $MESS["DELIVERY_VAT3"] = "НДС 0%";
  $MESS["DELIVERY_VAT4"] = "расчетный НДС 10/110";
  $MESS["DELIVERY_VAT5"] = "расчетный НДС 20/120";

  $MESS["STATUS_GROUP"] = "Статусы которые переключают заказ";

  $MESS["STATUS_TWOCHECK"] = "Статус для отправки отдельного чека";
  $MESS["STATUS_GROUP3"] = "ФЗ-54";
  $MESS["STATUS_GROUP2"] = 'Статусы в которые переключают заказ (работает если установлен параметр "Отдельные статусы"';
  $MESS["STATUS_PAY"] = "Статус оплачен";
  $MESS["STATUS_CHANCEL"] = "Статус возврата платежа";
  $MESS["STATUS_AUTHORIZE"] = "Статус подтверждения авторизации платежа (двухстадийные платежи)";
  $MESS["STATUS_AU"] = "Статус авторизованного платежа (двухстадийные платежи)";
  $MESS["STATUS_VOID"] = "Статус отмена авторизованного платежа (двухстадийные платежи)";
  $MESS["STATUS_PARTIAL_PAY"] = "Статус частичной оплаты";

  $MESS["SALE_HPS_CLOUDPAYMENT_COURSE_RATE"] = "Коэффициент курса банка-эквайра по отношению к курсу ЦБ";
  $MESS["SALE_HPS_CLOUDPAYMENT_COURSE_RATE_DESC"] = "";

  $MESS["SALE_HPS_CLOUDPAYMENT_NEW_STATUS"] = "Отдельные статусы";
  $MESS["SALE_HPS_CLOUDPAYMENT_NEW_STATUS_DESC"] = "Включается раздельный функционал переключения статусов и статусов для уведомлений";

  $MESS["SALE_HPS_CLOUDPAYMENT_WIDGET_DESIGN"] = "Дизайн виджета";
  $MESS["SALE_HPS_CLOUDPAYMENT_WIDGET_DESIGN_DESC"] = "";
  $MESS["SALE_HPS_WIDGET_DESIGN_TYPE_0"] = "classic";
  $MESS["SALE_HPS_WIDGET_DESIGN_TYPE_1"] = "modern";
  $MESS["SALE_HPS_WIDGET_DESIGN_TYPE_2"] = "mini";

  $MESS["SALE_HPS_CLOUDPAYMENT_CalculationPlace"] = "Место осуществления расчетов";
  $MESS["SALE_HPS_CLOUDPAYMENT_CalculationPlace_DESC"] = "Адрес (адреса) сайта точки продаж, для печати чека";

  $MESS["SALE_HPS_CLOUDPAYMENT_SPIC"] = "Код ИКПУ доставки";
  $MESS["SALE_HPS_CLOUDPAYMENT_PACKAGE_CODE"] = "Код упаковки доставки";

  $MESS["CREATE_ORDER1"] = "Выберите тип отправки счета";
  $MESS["CREATE_ORDER_EMAIL"] = "Тип отправки счета";

  $MESS["CREATE_ORDER_PHONE"] = "отправить по SMS";
  $MESS["CREATE_ORDER_PHONE_DESC"] = "Работает только с методом Cloudpayments";
  
  $MESS["CREATE_ORDER_EMAIL"] = "отправить по Email";

  $MESS["OLD_TYPE"] = "Отправка ссылки на оплату на почту";
  $MESS["NEW_TYPE"] = "Отправка методом cloudpayments";
  $MESS["CREATE_ORDER_TYPE"] = "Тип отправки счета";

  $MESS["SPOSOB_RASCHETA1"]="Признак способа расчета";
  $MESS["PREDMET_RASCHETA1"]="Признак предмета расчета";


  $MESS["SPOSOB_RASCHETA1_0"] = "неизвестный способ расчета";
  $MESS["SPOSOB_RASCHETA1_1"] = "предоплата 100%";
  $MESS["SPOSOB_RASCHETA1_2"] = "предоплата";
  $MESS["SPOSOB_RASCHETA1_3"] = "аванс";
  $MESS["SPOSOB_RASCHETA1_4"] = "полный расчёт";

  $MESS["PREDMET_RASCHETA1_0"] = "неизвестный предмет оплаты";
  $MESS["PREDMET_RASCHETA1_1"] = "товар";
  $MESS["PREDMET_RASCHETA1_2"] = "подакцизный товар";
  $MESS["PREDMET_RASCHETA1_3"] = "работа";
  $MESS["PREDMET_RASCHETA1_4"] = "услуга";