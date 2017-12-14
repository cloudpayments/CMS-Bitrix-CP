<?
global $MESS;
$PathInstall = str_replace("\\", "/", __FILE__);
$PathInstall = substr($PathInstall, 0, strlen($PathInstall) - strlen("/index.php"));
IncludeModuleLangFile($PathInstall . "/install.php");

if(class_exists("websputnik_cloudpayment")) return;

class websputnik_cloudpayment extends CModule
{
	var $MODULE_ID = "websputnik.cloudpayment";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_GROUP_RIGHTS = "Y";
	
	function __construct()
	{
		$arModuleVersion = array();
		include(dirname(__FILE__)."/version.php");
		$this->MODULE_VERSION = $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		$this->MODULE_NAME = GetMessage("websputnik.cloudpayment_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("websputnik.cloudpayment_MODULE_DESC");
		$this->PARTNER_NAME = GetMessage("websputnik.cloudpayment_PARTNER_NAME");
		$this->PARTNER_URI = GetMessage("websputnik.cloudpayment_PARTNER_URI");
	}
  
	function DoInstall()
	{	
		$this->InstallFiles();
		RegisterModule($this->MODULE_ID);
		return true;
	}

	function InstallDB()
	{
	
		return true;
	}

	function InstallFiles($arParams = array())
	{
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/images", $_SERVER["DOCUMENT_ROOT"]."/bitrix/images",true,true);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/php_interface",  $_SERVER["DOCUMENT_ROOT"]."/bitrix/php_interface",true,true);
		return true;
	}
	function UnInstallFiles()
	{
		DeleteDirFilesEx('/bitrix/php_interface/include/sale_payment/cloudpayment');
		
		return true;
	}

	function DoUninstall()
	{	
		$this->UnInstallDB();
		$this->UnInstallFiles();
		UnRegisterModule($this->MODULE_ID);
		return true;
	}
	function UnInstallDB()
	{	
		return true;
	}
}?>