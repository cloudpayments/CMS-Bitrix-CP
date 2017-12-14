<?php
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
?>
<style>
td span:nth-child(2)
{
     margin-right:70px;
}
</style>
<?
$description = array(
	'RETURN' => Loc::getMessage('SALE_HPS_CLOUDPAYMENT_RETURN'),
	'RESTRICTION' => Loc::getMessage('SALE_HPS_CLOUDPAYMENT_RESTRICTION'),
	'COMMISSION' => Loc::getMessage('SALE_HPS_CLOUDPAYMENT_COMMISSION'),
	'MAIN' => Loc::getMessage('VBCH_CLPAY_SPCP_DDESCR')
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
        "TYPE_NALOG" => array(
            "NAME" => Loc::getMessage("SALE_HPS_CLOUDPAYMENT_TYPE_NALOG"),
            "DESCRIPTION" => Loc::getMessage("SALE_HPS_CLOUDPAYMENT_TYPE_NALOG_DESC"),
            'SORT' => 770,
           'GROUP' => 'PAYMENT',
            "TYPE" => "SELECT",
            "INPUT"=>array(
                'TYPE'=>'ENUM',
                'OPTIONS'=>array(
                    '0'=>Loc::getMessage('SALE_HPS_NALOG_TYPE_0'),
                    '1'=>Loc::getMessage('SALE_HPS_NALOG_TYPE_1'),
                    "2"=>Loc::getMessage('SALE_HPS_NALOG_TYPE_2'),
                    "3"=>Loc::getMessage('SALE_HPS_NALOG_TYPE_3'),
                    "4"=>Loc::getMessage('SALE_HPS_NALOG_TYPE_4'),
                    "5"=>Loc::getMessage('SALE_HPS_NALOG_TYPE_5')
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
	)
);