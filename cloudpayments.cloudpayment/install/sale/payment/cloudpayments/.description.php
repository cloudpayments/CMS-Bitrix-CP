<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?><?
include(GetLangFileName(dirname(__FILE__)."/", "/payment.php"));

$psTitle = GetMessage("VBCH_CLPAY_SPCP_DTITLE");
$psDescription = GetMessage("VBCH_CLPAY_SPCP_DDESCR",array("#SITEURL#"=>$_SERVER['HTTP_HOST'],"#CODING#"=>SITE_CHARSET));

$arPSCorrespondence = array(
		"APIKEY" => array(
				"NAME" => GetMessage("VBCH_CLPAY_APIKEY"),
				"DESCR" => GetMessage("VBCH_CLPAY_APIKEY_DESCR"),
				"VALUE" => "",
				"TYPE" => ""
			),
		"APIPASS" => array(
				"NAME" => GetMessage("VBCH_CLPAY_APIPASS"),
				"DESCR" => GetMessage("VBCH_CLPAY_APIPASS_DESCR"),
				"VALUE" => "",
				"TYPE" => "",
		),
		"PAYTYPE" => array(
				"NAME" => GetMessage("VBCH_CLPAY_PAYTYPE"),
				"DESCR" => GetMessage("VBCH_CLPAY_PAYTYPE_DESCR"),
				"VALUE" => "",
			"TYPE" => "SELECT",
			"VALUE"=>array(
				"W" => array(
					"NAME" => GetMessage("VBCH_CLPAY_WIDGET"),
				),
				"SM" => array(
					"NAME" => GetMessage("VBCH_CLPAY_SM"),
				),
				"SB" =>array(
					"NAME"=>GetMessage("VBCH_CLPAY_SB"),
				),
			),
			),
		"PAYTOSTUD" => array(
				"NAME" => GetMessage("VBCH_CLPAY_PAYTOSTUD"),
				"DESCR" => GetMessage("VBCH_CLPAY_PAYTOSTUD_DESCR"),
				"VALUE" => "",
				"TYPE" => "SELECT",
				"VALUE"=>array(
					"SO" => array(
						"NAME" => GetMessage("VBCH_CLPAY_PAYTOSTUD1"),
					),
					"ST" =>array(
						"NAME"=>GetMessage("VBCH_CLPAY_PAYTOSTUD2"),
					),
				),
			),
		"PAYER_EMAIL"=>array(
			"NAME" => GetMessage("VBCH_CLPAY_PAYER_EMAIL"),
			"DESCR" => GetMessage("VBCH_CLPAY_PAYER_EMAIL_DESCR"),
			"VALUE" => "",
			"TYPE" => ""
		),
		"ORDER_PAY" => array(
				"NAME" => GetMessage("VBCH_CLPAY_ORDER_PAY"),
				"DESCR" => GetMessage("VBCH_CLPAY_ORDER_PAY_DESCR"),
				"VALUE" => "",
				"TYPE" => ""
		),
		"SHOULD_PAY" => array(
				"NAME" => GetMessage("VBCH_CLPAY_SHOULD_PAY"),
				"DESCR" => GetMessage("VBCH_CLPAY_SHOULD_PAY_DESCR"),
				"VALUE" => "",
				"TYPE" => ""
			),
		"CURRENCY" => array(
				"NAME" => GetMessage("VBCH_CLPAY_CURRENCY"),
				"DESCR" => GetMessage("VBCH_CLPAY_CURRENCY_DESCR"),
				"VALUE" => "",
				"TYPE" => ""
			),
		"DATE_INSERT" => array(
				"NAME" => GetMessage("VBCH_CLPAY_DATE_INSERT"),
				"DESCR" => GetMessage("VBCH_CLPAY_DATE_INSERT_DESCR"),
				"VALUE" => "",
				"TYPE" => ""
			),
	);
?>