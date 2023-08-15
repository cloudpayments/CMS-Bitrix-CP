<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
include(GetLangFileName(dirname(__FILE__)."/", "/payment.php"));
set_time_limit(0);
CModule::IncludeModule("sale");
$apikey = CSalePaySystemAction::GetParamValue("APIKEY");
$pass = CSalePaySystemAction::GetParamValue("APIPASS");
$ORDER_ID = intVal($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["ID"]);
$url="https://api.cloudpayments.ru/payments/find";
if(function_exists("curl_init") && $curl = curl_init() ) {
	CModule::IncludeModule("cloudpayments.cloudpayment");
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC); 
        curl_setopt($curl,CURLOPT_USERPWD,$apikey . ":" . $pass);
		curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, "InvoiceId=".$ORDER_ID);
        $out = curl_exec($curl);
        curl_close($curl);
		$out=VBCHCLPAY::Object_to_array(json_decode($out));
    }
$strPS_STATUS_DESCRIPTION = "";
	$strPS_STATUS_DESCRIPTION .= GetMessage("VBCH_CLOUDPAY_ORDERID").$out['Model']['InvoiceId']."; ";
	$strPS_STATUS_DESCRIPTION .= GetMessage("VBCH_CLOUDPAY_ORDERNUM")." <a target='_blank' href='https://merchant.cloudpayments.ru/Transactions/Details/".$out['Model']['TransactionId']."'>".$out['Model']['TransactionId']."</a>; ";
	if(array_key_exists("CardHolderMessage",$out['Model'])){
		$strPS_STATUS_DESCRIPTION .= GetMessage("VBCH_CLOUDPAY_MESSAGE").GetMessage("VBCH_CLOUDPAYMENT_".$out['Model']['ReasonCode'])."; ";
	}
	$arFields = array(
					"PS_STATUS" => 'N',
					"PS_STATUS_CODE" =>GetMessage("VBCH_CLOUDPAYMENT_".$out['Model']['ReasonCode']),
					"PS_STATUS_DESCRIPTION" => $strPS_STATUS_DESCRIPTION,
					"PS_STATUS_MESSAGE" => $out['Model']['Reason'] ? $out['Model']['Reason'] : 'none',
					"PS_SUM" => $out['Model']['PaymentAmount'],
					"PS_CURRENCY" => $out['Model']['PaymentCurrency'],
					"PS_RESPONSE_DATE" => Date(CDatabase::DateFormatToPHP(CLang::GetDateFormat("FULL", LANG))),
				);
	\CSaleOrder::Update($ORDER_ID, $arFields);
?>