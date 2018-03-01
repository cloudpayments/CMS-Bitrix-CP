<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?>
<?include(GetLangFileName(dirname(__FILE__)."/", "/payment.php"));?>
<?$ORDER_ID = IntVal($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["ID"]);?>
<?
$SITE_NAME = COption::GetOptionString("main", "server_name", "");
$arParams['date_insert']=(strlen(CSalePaySystemAction::GetParamValue("DATE_INSERT")) > 0) ? CSalePaySystemAction::GetParamValue("DATE_INSERT") : $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["DATE_INSERT"];
$arParams['order_id'] = $ORDER_ID;
$arParams['shouldPay'] = (strlen(CSalePaySystemAction::GetParamValue("SHOULD_PAY")) > 0) ? CSalePaySystemAction::GetParamValue("SHOULD_PAY") : $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["SHOULD_PAY"];
$arParams['currency'] = (strlen(CSalePaySystemAction::GetParamValue("CURRENCY")) > 0) ? CSalePaySystemAction::GetParamValue("CURRENCY") : $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"];
$arParams['access_key'] = (strlen(CSalePaySystemAction::GetParamValue("APIKEY")) > 0) ? CSalePaySystemAction::GetParamValue("APIKEY") : $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["APIKEY"];
$arParams['access_psw'] = (strlen(CSalePaySystemAction::GetParamValue("APIPASS")) > 0) ? CSalePaySystemAction::GetParamValue("APIPASS") : $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["APIPASS"];
$arParams['buyer_email'] = (strlen(CSalePaySystemAction::GetParamValue("PAYER_EMAIL")) > 0) ? CSalePaySystemAction::GetParamValue("PAYER_EMAIL") : $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["PAYER_EMAIL"];
$arParams['buyer_ip'] = $_SERVER['REMOTE_ADDR'];
$arParams['description']=GetMessage("VBCH_CLPAY_MM_DESC",array("#ORDER_ID#"=>$ORDER_ID,"#SITE_NAME#"=>$SITE_NAME,"#DATE#"=>$arParams['date_insert']));
$arParams['PAYSTUD']=(strlen(CSalePaySystemAction::GetParamValue("PAYTOSTUD")) > 0) ? CSalePaySystemAction::GetParamValue("PAYTOSTUD") : $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["PAYTOSTUD"];
$arParams['PAYTYPE']=(strlen(CSalePaySystemAction::GetParamValue("PAYTYPE")) > 0) ? CSalePaySystemAction::GetParamValue("PAYTYPE") : $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["PAYTYPE"];
?>
<style>
	.cloudpay_button {
		border:1px solid gray;
		color:#000000;
		padding:10px;
	}
</style>
<div class="text">
    <p><?=GetMessage("VBCH_CLOUDPAY_TITLE")?><br>
	<?=GetMessage("VBCH_CLOUDPAY_DESC",array("#ORDER_ID#"=>$ORDER_ID,"#DATE#"=>CSalePaySystemAction::GetParamValue("DATE_INSERT"),
				"#SUMMA#"=>SaleFormatCurrency(CSalePaySystemAction::GetParamValue("SHOULD_PAY"), CSalePaySystemAction::GetParamValue("CURRENCY"))));?>
      <br/>
	<?if($arParams['PAYTYPE']!='SM'){?>
        <button class="cloudpay_button" onClick="pay();return false;"><?=GetMessage("VBCH_CLOUDPAY_BUTTON")?></button>
	<?}?>
    <div id="result" style="display:none"></div>
</div>
<?if($arParams['PAYTYPE']=='SM'){
	CModule::IncludeModule("cloudpayments.cloudpayment");
	$url="https://api.cloudpayments.ru/orders/create";
    $data=array(
        "Amount"=>intval($arParams['shouldPay']),
        "Currency"=>$arParams['currency'],
        "Description"=>urlencode($arParams['description']),
        "Email"=>$arParams['buyer_email'],
        "RequireConfirmation"=>($arParams['PAYTOSTUD']=='SO') ? 'false':'true',
        "SendEmail"=>'true',
        "InvoiceId"=>$arParams['order_id'],
        "AccountId"=>$arParams['buyer_email']
    );
	$post="";
	foreach($data as $k=>$d){
		$post.=$k."=".$d."&";
	}
	$post=substr($post,0,-1);
    if(function_exists("curl_init") && $curl = curl_init() ) {
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC); 
        curl_setopt($curl,CURLOPT_USERPWD,$arParams['access_key'] . ":" . $arParams['access_psw']);
		curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
        $out = curl_exec($curl);
        curl_close($curl);
		$out=VBCHCLPAY::Object_to_array(json_decode($out));
    }
	if($out['Success']==1){?>
		<a href="<?=$out['Model']['Url']?>" target="__blank" class="cloudpay_button"><?=GetMessage("VBCH_CLOUDPAY_BUTTON")?></a>
	<?}?>
    <?
}else{?>
<?    CJSCore::Init(array("jquery"));
    ?>
    <script src="https://widget.cloudpayments.ru/bundles/cloudpayments"></script>
    <script>
        this.pay = function () {
            var widget = new cp.CloudPayments();
            <?if($arParams['PAYTYPE']=='SB'){?>
            var data = {};
            data.cloudPayments = {recurrent: { interval: 'Month', period: 1 }}; 
            <?}?>
            widget.<?=($arParams['PAYSTUD']=="SO" ? "charge" : "auth")?>({ 
                publicId: '<?=$arParams['access_key']?>', 
                description: '<?=$arParams['description']?>',
                amount: <?=$arParams['shouldPay']?>, 
                currency: '<?=$arParams['currency']?>',
                invoiceId: '<?=$arParams['order_id']?>',
                accountId: '<?=$arParams['buyer_email']?>'
                    <?if($arParams['PAYTYPE']=='SB'){?>
                        ,data: data
                    <?}?>
            },
            function (options) { // success
                BX("result").innerHTML="<?=GetMessage('VBCH_CLOUDPAY_SUCCESS')?>";
                BX.style(BX("result"),"color","green");
                BX.style(BX("result"),"display","block");
            },
            function (reason, options) { // fail
                BX("result").innerHTML=reason;
                BX.style(BX("result"),"color","red");
                BX.style(BX("result"),"display","block");
            });
        };
    </script>
<?}?>