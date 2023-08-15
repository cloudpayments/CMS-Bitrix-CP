<?
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Sale\PaySystem;

Loc::loadMessages(__FILE__);

\Bitrix\Main\Loader::includeModule('sale');

global $APPLICATION;

$application = \Bitrix\Main\Application::getInstance();
$context = $application->getContext();
$request = $context->getRequest();
$shopId = $request->get("shop_id");
$companyName = $request->get("company_name");
$handler = $request->get("handler");
$errorMsg = '';

\CUtil::InitJSCore();

if ($request->get("csr") === 'Y')
{
	PaySystem\YandexCert::getCsr($shopId);
}

if ($request->get("generate") === 'Y')
{
	$companyName = $request->get('company_name');
	if ($companyName && !preg_match('/[^a-zA-Z]+/', $companyName))
	{
		PaySystem\YandexCert::generate($shopId, $companyName);
		LocalRedirect($APPLICATION->GetCurPage().'?shop_id='.$shopId."&handler=".$handler.'&lang='.LANG);
	}
	else
	{
		$errorMsg = Loc::getMessage('SALE_YANDEX_RETURN_ERROR_CN');
	}
}

if (($request->getPost("Update") || $request->getPost("Apply")) && check_bitrix_sessid())
{
	if ($request->get('SETTINGS_CLEAR') || $request->get('SETTINGS_CLEAR_ALL'))
	{
		$all = $request->get('SETTINGS_CLEAR_ALL') !== null;
		PaySystem\YandexCert::clear($shopId, $all);
	}

	$certFile = $request->getFile("CERT_FILE");
	if (file_exists($certFile['tmp_name']))
		PaySystem\YandexCert::setCert($certFile, $shopId);

	if (PaySystem\YandexCert::$errors)
	{
		foreach (PaySystem\YandexCert::$errors as $error)
			$errorMsg .= $error."<br>\n";
	}

	if ($errorMsg === '')
	{
		LocalRedirect($APPLICATION->GetCurPage().'?shop_id='.$shopId.'&handler='.$handler.'&lang='.LANG);
	}
}

if ($errorMsg !== '')
	CAdminMessage::ShowMessage(array("DETAILS"=>$errorMsg, "TYPE"=>"ERROR", "HTML"=>true));

$personTypeTabs = array();
$personTypeTabs[] = array(
	"PERSON_TYPE" => 0,
	"DIV" => 0,
	"TAB" => Loc::getMessage('SALE_YANDEX_RETURN_PT'),
	"TITLE" => Loc::getMessage("SALE_YANDEX_RETURN_TITLE")
);

$tabRControl = new \CAdminTabControl("tabRControl", $personTypeTabs);
$tabRControl->Begin();?>

<form method="POST" enctype="multipart/form-data"
	  action="<?=$APPLICATION->GetCurPage()?>?shop_id=<?=$shopId;?>&handler=<?=$handler;?>&lang=<?echo LANG?>"
	  xmlns="http://www.w3.org/1999/html">
<?
	echo bitrix_sessid_post();
	$tabRControl->BeginNextTab();?>


<?$tabRControl->End();