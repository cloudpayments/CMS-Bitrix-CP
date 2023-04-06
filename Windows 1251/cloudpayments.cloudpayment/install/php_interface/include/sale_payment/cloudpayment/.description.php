<?php

  use Bitrix\Main\Localization\Loc;

  Loc::loadMessages(__FILE__);

  $description = array(
    'RETURN' => Loc::getMessage('SALE_HPS_CLOUDPAYMENT_RETURN'),
    'RESTRICTION' => Loc::getMessage('SALE_HPS_CLOUDPAYMENT_RESTRICTION'),
    'COMMISSION' => Loc::getMessage('SALE_HPS_CLOUDPAYMENT_COMMISSION'),
    'MAIN' => Loc::getMessage('VBCH_CLPAY_SPCP_DDESCR'),
  );

  $data = array(
    'NAME' => Loc::getMessage('SALE_HPS_CLOUDPAYMENT'),
    'SORT' => 500,
    'DOMAIN' => 'BOX',
    'CODES' => array(
      "APIKEY" => array(
        "NAME" => Loc::getMessage("SALE_HPS_CLOUDPAYMENT_SHOP_KEY"),
        "DESCRIPTION" => Loc::getMessage("SALE_HPS_CLOUDPAYMENT_SHOP_KEY_DESC"),
        'SORT' => 300,
        'GROUP' => 'PAYMENT',
      ),
      "APIPASS" => array(
        "NAME" => Loc::getMessage("SALE_HPS_CLOUDPAYMENT_SHOP_ID"),
        "DESCRIPTION" => Loc::getMessage("SALE_HPS_CLOUDPAYMENT_SHOP_ID_DESC"),
        'SORT' => 100,
        'GROUP' => 'PAYMENT',
      ),
      "SUCCESS_URL" => array(
        "NAME" => Loc::getMessage("SALE_HPS_CLOUDPAYMENT_SUCCESS_URL"),
        "DESCRIPTION" => Loc::getMessage("SALE_HPS_CLOUDPAYMENT_SUCCESS_URL_DESC"),
        'SORT' => 301,
        'GROUP' => 'PAYMENT',
      ),
      "FAIL_URL" => array(
        "NAME" => Loc::getMessage("SALE_HPS_CLOUDPAYMENT_FAIL_URL"),
        "DESCRIPTION" => Loc::getMessage("SALE_HPS_CLOUDPAYMENT_FAIL_URL_DESC"),
        'SORT' => 302,
        'GROUP' => 'PAYMENT',
      ),
      "WIDGET_LANG" => array(
        "NAME" => Loc::getMessage("SALE_HPS_CLOUDPAYMENT_WIDGET_LANG"),
        "DESCRIPTION" => Loc::getMessage("SALE_HPS_CLOUDPAYMENT_WIDGET_LANG_DESC"),
        'SORT' => 303,
        'GROUP' => 'PAYMENT',
        "TYPE" => "SELECT",
        "INPUT" => array(
          'TYPE' => 'ENUM',
          'OPTIONS' => array(
            'ru-RU' => Loc::getMessage('SALE_HPS_WIDGET_LANG_TYPE_0'),
            'en-US' => Loc::getMessage('SALE_HPS_WIDGET_LANG_TYPE_1'),
            "lv" => Loc::getMessage('SALE_HPS_WIDGET_LANG_TYPE_2'),
            "az" => Loc::getMessage('SALE_HPS_WIDGET_LANG_TYPE_3'),
            "kk" => Loc::getMessage('SALE_HPS_WIDGET_LANG_TYPE_4'),
            "kk-KZ" => Loc::getMessage('SALE_HPS_WIDGET_LANG_TYPE_5'),
            "uk" => Loc::getMessage('SALE_HPS_WIDGET_LANG_TYPE_6'),
            "pl" => Loc::getMessage('SALE_HPS_WIDGET_LANG_TYPE_7'),
            "pt" => Loc::getMessage('SALE_HPS_WIDGET_LANG_TYPE_8'),
            "cs-CZ" => Loc::getMessage('SALE_HPS_WIDGET_LANG_TYPE_9'),
            "vi-VN" => Loc::getMessage('SALE_HPS_WIDGET_LANG_TYPE_10'),
            "tr-TR" => Loc::getMessage('SALE_HPS_WIDGET_LANG_TYPE_11'),
            "es-ES" => Loc::getMessage('SALE_HPS_WIDGET_LANG_TYPE_12'),
            "uz" => Loc::getMessage('SALE_HPS_WIDGET_LANG_TYPE_13')
          ),
        ),
      ),
      "WIDGET_SKIN" => array(
        "NAME" => Loc::getMessage("SALE_HPS_CLOUDPAYMENT_WIDGET_DESIGN"),
        "DESCRIPTION" => Loc::getMessage("SALE_HPS_CLOUDPAYMENT_WIDGET_DESIGN_DESC"),
        'SORT' => 303,
        'GROUP' => 'PAYMENT',
        "TYPE" => "SELECT",
        "INPUT" => array(
          'TYPE' => 'ENUM',
          'OPTIONS' => array(
            'classic' => Loc::getMessage('SALE_HPS_WIDGET_DESIGN_TYPE_0'),
            'modern' => Loc::getMessage('SALE_HPS_WIDGET_DESIGN_TYPE_1'),
            "mini" => Loc::getMessage('SALE_HPS_WIDGET_DESIGN_TYPE_2'),
          ),
        ),
      ),
      "CHECKONLINE" => array(
        "NAME" => Loc::getMessage("SALE_HPS_CLOUDPAYMENT_CHECKONLINE"),
        "DESCRIPTION" => Loc::getMessage("SALE_HPS_CLOUDPAYMENT_CHECKONLINE_DESC"),
        'SORT' => 700,
        'GROUP' => 'PAYMENT',
        "INPUT" => array(
          'TYPE' => 'Y/N'
        ),
      ),
      "INN" => array(
        "NAME" => Loc::getMessage("SALE_HPS_CLOUDPAYMENT_INN"),
        "DESCRIPTION" => Loc::getMessage("SALE_HPS_CLOUDPAYMENT_INN_DESC"),
        'SORT' => 750,
        'GROUP' => 'PAYMENT',
      ),
      "calculationPlace" => array(
        "NAME" => Loc::getMessage("SALE_HPS_CLOUDPAYMENT_CalculationPlace"),
        "DESCRIPTION" => Loc::getMessage("SALE_HPS_CLOUDPAYMENT_CalculationPlace_DESC"),
        'SORT' => 750,
        'GROUP' => 'PAYMENT',
      ),
      "TYPE_NALOG" => array(
        "NAME" => Loc::getMessage("SALE_HPS_CLOUDPAYMENT_TYPE_NALOG"),
        "DESCRIPTION" => Loc::getMessage("SALE_HPS_CLOUDPAYMENT_TYPE_NALOG_DESC"),
        'SORT' => 770,
        'GROUP' => 'PAYMENT',
        "TYPE" => "SELECT",
        "INPUT" => array(
          'TYPE' => 'ENUM',
          'OPTIONS' => array(
            '0' => Loc::getMessage('SALE_HPS_NALOG_TYPE_0'),
            '1' => Loc::getMessage('SALE_HPS_NALOG_TYPE_1'),
            "2" => Loc::getMessage('SALE_HPS_NALOG_TYPE_2'),
            "3" => Loc::getMessage('SALE_HPS_NALOG_TYPE_3'),
            "4" => Loc::getMessage('SALE_HPS_NALOG_TYPE_4'),
            "5" => Loc::getMessage('SALE_HPS_NALOG_TYPE_5')
          ),
        ),
      ),

      "TYPE_SYSTEM" => array(
        "NAME" => Loc::getMessage("SALE_HPS_CLOUDPAYMENT_TYPE_SYSTEM"),
        'SORT' => 770,
        'GROUP' => 'PAYMENT',
        "TYPE" => "SELECT",
        "INPUT" => array(
          'TYPE' => 'ENUM',
          'OPTIONS' => array(
            '0' => Loc::getMessage('SALE_HPS_TYPE_SCHEME_0'),
            '1' => Loc::getMessage('SALE_HPS_TYPE_SCHEME_1'),
          ),
        ),
      ),

      "PAYMENT_ID" => array(
        "NAME" => Loc::getMessage("SALE_HPS_CLOUDPAYMENT_PAYMENT_ID"),
        'SORT' => 400,
        'GROUP' => 'PAYMENT',
        'DEFAULT' => array(
          'PROVIDER_KEY' => 'ORDER',
          'PROVIDER_VALUE' => 'ID'
        )
      ),
      "PAY_ID" => array(
        "NAME" => Loc::getMessage("SALE_HPS_CLOUDPAYMENT_PAY_ID"),
        'SORT' => 400,
        'GROUP' => 'PAYMENT',
        'DEFAULT' => array(
          'PROVIDER_KEY' => 'PAYMENT',
          'PROVIDER_VALUE' => 'ID'
        )
      ),
      "PAYMENT_DATE_INSERT" => array(
        "NAME" => Loc::getMessage("SALE_HPS_CLOUDPAYMENT_PAYMENT_DATE"),
        'SORT' => 500,
        'GROUP' => 'PAYMENT',
        'DEFAULT' => array(
          'PROVIDER_KEY' => 'ORDER',
          'PROVIDER_VALUE' => 'DATE_INSERT'
        )
      ),
      "PAYMENT_SHOULD_PAY" => array(
        "NAME" => Loc::getMessage("SALE_HPS_CLOUDPAYMENT_SHOULD_PAY"),
        'SORT' => 600,
        'GROUP' => 'PAYMENT',
        'DEFAULT' => array(
          'PROVIDER_KEY' => 'PAYMENT',
          'PROVIDER_VALUE' => 'SUM'
        )
      ),
      "PAYMENT_CURRENCY" => array(
        "NAME" => Loc::getMessage("SALE_HPS_CLOUDPAYMENT_CURRENCY"),
        'SORT' => 600,
        'GROUP' => 'PAYMENT',
        'DEFAULT' => array(
          'PROVIDER_KEY' => 'ORDER',
          'PROVIDER_VALUE' => 'CURRENCY'
        )
      ),
      /*     "COURSE_RATE" => array(
             "NAME" => Loc::getMessage("SALE_HPS_CLOUDPAYMENT_COURSE_RATE"),
             "DESCRIPTION" => Loc::getMessage("SALE_HPS_CLOUDPAYMENT_COURSE_RATE_DESC"),
             'SORT' => 750,
             'GROUP' => 'PAYMENT',
           ),*/
      "PS_CHANGE_STATUS_PAY" => array(
        "NAME" => Loc::getMessage("SALE_HPS_CLOUDPAYMENT_CHANGE_STATUS_PAY"),
        'SORT' => 700,
        'GROUP' => 'GENERAL_SETTINGS',
        "INPUT" => array(
          'TYPE' => 'Y/N'
        ),
      ),
      "PS_IS_TEST" => array(
        "NAME" => Loc::getMessage("SALE_HPS_CLOUDPAYMENT_IS_TEST"),
        'SORT' => 900,
        'GROUP' => 'GENERAL_SETTINGS',
        "INPUT" => array(
          'TYPE' => 'Y/N'
        )
      ),
      "PAYMENT_BUYER_ID" => array(
        "NAME" => Loc::getMessage("SALE_HPS_CLOUDPAYMENT_BUYER_ID"),
        'SORT' => 1000,
        'GROUP' => 'PAYMENT',
        'DEFAULT' => array(
          'PROVIDER_KEY' => 'ORDER',
          'PROVIDER_VALUE' => 'USER_ID'
        )
      ),
      "PAYMENT_BUYER_EMAIL" => array(
        "NAME" => Loc::getMessage("SALE_HPS_CLOUDPAYMENT_BUYER_EMAIL"),
        'SORT' => 1000,
        'GROUP' => 'PAYMENT',
        'DEFAULT' => array(
          'PROVIDER_KEY' => 'ORDER',
          'PROVIDER_VALUE' => 'USER_EMAIL'
        )
      ),
      "PAYMENT_BUYER_PHONE" => array(
        "NAME" => Loc::getMessage("SALE_HPS_CLOUDPAYMENT_BUYER_PHONE"),
        'SORT' => 1000,
        'GROUP' => 'PAYMENT',
        'DEFAULT' => array(
          'PROVIDER_KEY' => 'ORDER',
          'PROVIDER_VALUE' => 'USER_PHONE'
        )
      ),
      /*      "NEW_STATUS" => array(
              "NAME" => Loc::getMessage("SALE_HPS_CLOUDPAYMENT_NEW_STATUS"),
              "DESCRIPTION" => Loc::getMessage("SALE_HPS_CLOUDPAYMENT_NEW_STATUS_DESC"),
              'SORT' => 700,
              'GROUP' => 'PAYMENT',
              "INPUT" => array(
                'TYPE' => 'Y/N'
              ),
            ),*/
    )
  );

  ///DELIVERY VAT

  if(CModule::IncludeModule("sale") && CModule::IncludeModule("catalog")) {
    $VAT_LIST['20'] = Loc::getMessage("DELIVERY_VAT1");
    $VAT_LIST['10'] = Loc::getMessage("DELIVERY_VAT2");
    $VAT_LIST['0'] = Loc::getMessage("DELIVERY_VAT3");
    $VAT_LIST['110'] = Loc::getMessage("DELIVERY_VAT4");
    $VAT_LIST['120'] = Loc::getMessage("DELIVERY_VAT5");

    if($VAT_LIST) {
      $db_dtype = CSaleDelivery::GetList(array("SORT" => "ASC", "NAME" => "ASC"), array(
        "LID" => 's1',
        "ACTIVE" => "Y"
      ), false, false, array());
      while($ar_dtype = $db_dtype->Fetch()) {
        $data['CODES']["VAT_DELIVERY" . $ar_dtype['ID']] = array(
          "NAME" => $ar_dtype['NAME'],
          'SORT' => 303,
          'GROUP' => Loc::getMessage("VAT"),
          "TYPE" => "SELECT",
          "INPUT" => array(
            'TYPE' => 'ENUM',
            'OPTIONS' => $VAT_LIST
          ),
        );
      }
    }

    $db_dtype = CSaleStatus::GetList(array());
    while($ar_dtype = $db_dtype->Fetch()) {
      $STATUS_ALL[$ar_dtype['ID']] = $ar_dtype['NAME'];
    }

    $data['CODES']['SPIC'] = array(
      "NAME" => Loc::getMessage("SALE_HPS_CLOUDPAYMENT_SPIC"),
      "SORT" => 304,
      "GROUP" => Loc::getMessage("VAT")
    );

    $data['CODES']['PACKAGE_CODE'] = array(
      "NAME" => Loc::getMessage("SALE_HPS_CLOUDPAYMENT_PACKAGE_CODE"),
      "SORT" => 304,
      "GROUP" => Loc::getMessage("VAT")
    );

    $data['CODES']["STATUS_PAY"] = array(
      "NAME" => Loc::getMessage("STATUS_PAY"),
      'SORT' => 300,
      'GROUP' => Loc::getMessage("STATUS_GROUP"),
      "TYPE" => "SELECT",
      "INPUT" => array(
        'TYPE' => 'ENUM',
        'OPTIONS' => $STATUS_ALL
      ),
    );

    /*    $data['CODES']["STATUS_PAY2"] = array(
          "NAME" => Loc::getMessage("STATUS_PAY"),
          'SORT' => 300,
          'GROUP' => Loc::getMessage("STATUS_GROUP2"),
          "TYPE" => "SELECT",
          "INPUT" => array(
            'TYPE' => 'ENUM',
            'OPTIONS' => $STATUS_ALL
          ),
        );*/

    $data['CODES']["STATUS_CHANCEL"] = array(
      "NAME" => Loc::getMessage("STATUS_CHANCEL"),
      'SORT' => 301,
      'GROUP' => Loc::getMessage("STATUS_GROUP"),
      "TYPE" => "SELECT",
      "INPUT" => array(
        'TYPE' => 'ENUM',
        'OPTIONS' => $STATUS_ALL
      ),
    );

    /*    $data['CODES']["STATUS_CHANCEL2"] = array(
          "NAME" => Loc::getMessage("STATUS_CHANCEL"),
          'SORT' => 301,
          'GROUP' => Loc::getMessage("STATUS_GROUP2"),
          "TYPE" => "SELECT",
          "INPUT" => array(
            'TYPE' => 'ENUM',
            'OPTIONS' => $STATUS_ALL
          ),
        );*/

    $data['CODES']["STATUS_AU"] = array(
      "NAME" => Loc::getMessage("STATUS_AU"),
      'SORT' => 302,
      'GROUP' => Loc::getMessage("STATUS_GROUP"),
      "TYPE" => "SELECT",
      "INPUT" => array(
        'TYPE' => 'ENUM',
        'OPTIONS' => $STATUS_ALL,
        "DEFAULT" => "N"
      ),
      "DEFAULT" => "N"
    );

    /*    $data['CODES']["STATUS_AU2"] = array(
          "NAME" => Loc::getMessage("STATUS_AU"),
          'SORT' => 302,
          'GROUP' => Loc::getMessage("STATUS_GROUP2"),
          "TYPE" => "SELECT",
          "INPUT" => array(
            'TYPE' => 'ENUM',
            'OPTIONS' => $STATUS_ALL,
            "DEFAULT" => "N"
          ),
          "DEFAULT" => "N"
        );*/

    $data['CODES']["STATUS_AUTHORIZE"] = array(
      "NAME" => Loc::getMessage("STATUS_AUTHORIZE"),
      'SORT' => 303,
      'GROUP' => Loc::getMessage("STATUS_GROUP"),
      "TYPE" => "SELECT",
      "INPUT" => array(
        'TYPE' => 'ENUM',
        'OPTIONS' => $STATUS_ALL,
        "DEFAULT" => "N"
      ),
      "DEFAULT" => "N"
    );

    /*    $data['CODES']["STATUS_AUTHORIZE2"] = array(
          "NAME" => Loc::getMessage("STATUS_AUTHORIZE"),
          'SORT' => 303,
          'GROUP' => Loc::getMessage("STATUS_GROUP2"),
          "TYPE" => "SELECT",
          "INPUT" => array(
            'TYPE' => 'ENUM',
            'OPTIONS' => $STATUS_ALL,
            "DEFAULT" => "N"
          ),
          "DEFAULT" => "N"
        );*/

    $data['CODES']["STATUS_VOID"] = array(
      "NAME" => Loc::getMessage("STATUS_VOID"),
      'SORT' => 304,
      'GROUP' => Loc::getMessage("STATUS_GROUP"),
      "TYPE" => "SELECT",
      "INPUT" => array(
        'TYPE' => 'ENUM',
        'OPTIONS' => $STATUS_ALL,
        "DEFAULT" => "N"
      ),
      "DEFAULT" => "N"
    );

    /*    $data['CODES']["STATUS_VOID2"] = array(
          "NAME" => Loc::getMessage("STATUS_VOID"),
          'SORT' => 304,
          'GROUP' => Loc::getMessage("STATUS_GROUP2"),
          "TYPE" => "SELECT",
          "INPUT" => array(
            'TYPE' => 'ENUM',
            'OPTIONS' => $STATUS_ALL,
            "DEFAULT" => "N"
          ),
          "DEFAULT" => "N"
        );*/

    $data['CODES']["STATUS_PARTIAL_PAY"] = array(
      "NAME" => Loc::getMessage("STATUS_PARTIAL_PAY"),
      'SORT' => 305,
      'GROUP' => Loc::getMessage("STATUS_GROUP"),
      "TYPE" => "SELECT",
      "INPUT" => array(
        'TYPE' => 'ENUM',
        'OPTIONS' => $STATUS_ALL,
        "DEFAULT" => "N"
      ),
      "DEFAULT" => "N"
    );

    $data['CODES']["STATUS_TWOCHECK"] = array(
      "NAME" => Loc::getMessage("STATUS_TWOCHECK"),
      'SORT' => 305,
      'GROUP' => Loc::getMessage("STATUS_GROUP"),
      "TYPE" => "SELECT",
      "INPUT" => array(
        'TYPE' => 'ENUM',
        'OPTIONS' => $STATUS_ALL,
        "DEFAULT" => "N"
      ),
      "DEFAULT" => "N"
    );


  }

  $data['CODES']["CREATE_ORDER_TYPE"] = array(
    "NAME" => Loc::getMessage("CREATE_ORDER_TYPE"),
    'SORT' => 305,
    'GROUP' => Loc::getMessage("CREATE_ORDER1"),
    "TYPE" => "SELECT",
    "INPUT" => array(
      'TYPE' => 'ENUM',
      'OPTIONS' => array("1" => Loc::getMessage("OLD_TYPE"), "2" => Loc::getMessage("NEW_TYPE")),
    ),
  );

  $data['CODES']["CREATE_ORDER_EMAIL"] = array(
    "NAME" => Loc::getMessage("CREATE_ORDER_EMAIL"),
    "DESCRIPTION" => Loc::getMessage("CREATE_ORDER_EMAIL_DESC"),
    'SORT' => 700,
    'GROUP' => Loc::getMessage("CREATE_ORDER1"),
    "INPUT" => array(
      'TYPE' => 'Y/N'
    ),
  );

  $data['CODES']["CREATE_ORDER_PHONE"] = array(
    "NAME" => Loc::getMessage("CREATE_ORDER_PHONE"),
    "DESCRIPTION" => Loc::getMessage("CREATE_ORDER_PHONE_DESC"),
    'SORT' => 700,
    'GROUP' => Loc::getMessage("CREATE_ORDER1"),
    "INPUT" => array(
      'TYPE' => 'Y/N'
    ),
  );
  $PROPERTYS1 = array();
/*  global $DB;
  $results_sql = $DB->Query("SELECT * FROM `b_iblock_property` WHERE `ACTIVE`='Y' ORDER BY `NAME` ASC");
  while($row_sql = $results_sql->Fetch()):
    $PROPERTYS[$row_sql['ID']] = $row_sql['NAME'];
  endwhile;*/



$PROPERTYS1 = array(
0 =>Loc::getMessage("SPOSOB_RASCHETA1_0"),
1=>Loc::getMessage("SPOSOB_RASCHETA1_1"),
2=>Loc::getMessage("SPOSOB_RASCHETA1_2"),
3=>Loc::getMessage("SPOSOB_RASCHETA1_3"),
4=>Loc::getMessage("SPOSOB_RASCHETA1_4"));

  $PROPERTYS2 = array(
  0 =>Loc::getMessage("PREDMET_RASCHETA1_0"),
  1=>Loc::getMessage("PREDMET_RASCHETA1_1"),
  2=>Loc::getMessage("PREDMET_RASCHETA1_2"),
  3=>Loc::getMessage("PREDMET_RASCHETA1_3"),
  4=>Loc::getMessage("PREDMET_RASCHETA1_4"));


  $data['CODES']["SPOSOB_RASCHETA1"] = array(
    "NAME" => Loc::getMessage("SPOSOB_RASCHETA1"),
    'SORT' => 305,
    'GROUP' => Loc::getMessage("STATUS_GROUP3"),
    "TYPE" => "SELECT",
    "INPUT" => array(
      'TYPE' => 'ENUM',
      'OPTIONS' => $PROPERTYS1,
      "DEFAULT" => "N"
    ),
    "DEFAULT" => "N"
  );

  $PREDMET_RASCHETA1 = array(

  );

  $data['CODES']["PREDMET_RASCHETA1"] = array(
    "NAME" => Loc::getMessage("PREDMET_RASCHETA1"),
    'SORT' => 305,
    'GROUP' => Loc::getMessage("STATUS_GROUP3"),
    "TYPE" => "SELECT",
    "INPUT" => array(
      'TYPE' => 'ENUM',
      'OPTIONS' => $PROPERTYS2,
      "DEFAULT" => "N"
    ),
    "DEFAULT" => "N"
  );








