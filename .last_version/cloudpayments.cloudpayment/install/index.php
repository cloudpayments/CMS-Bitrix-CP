<?
global $MESS;
$PathInstall = str_replace("\\", "/", __FILE__);
$PathInstall = substr($PathInstall, 0, strlen($PathInstall) - strlen("/index.php"));
IncludeModuleLangFile($PathInstall . "/install.php");

if(class_exists("cloudpayments_cloudpayment")) return;

class cloudpayments_cloudpayment extends CModule
{
	var $MODULE_ID = "cloudpayments.cloudpayment";
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
		$this->MODULE_NAME = GetMessage("cloudpayments.cloudpayment_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("cloudpayments.cloudpayment_MODULE_DESC");
		$this->PARTNER_NAME = GetMessage("cloudpayments.cloudpayment_PARTNER_NAME");
		$this->PARTNER_URI = GetMessage("cloudpayments.cloudpayment_PARTNER_URI");
	}
  
	function DoInstall()
	{	
		$this->InstallFiles();
		RegisterModule($this->MODULE_ID);
    $this->InstallEvents();
    $this->InstallOrderStatus();
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
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/admin",  $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin",true,true);
    mkdir($_SERVER["DOCUMENT_ROOT"]."/cloudPayments");
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/front",  $_SERVER["DOCUMENT_ROOT"]."/cloudPayments",true,true);
		return true;
	}
	function UnInstallFiles()
	{
		DeleteDirFilesEx('/bitrix/php_interface/include/sale_payment/cloudpayment');
		unlink($_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/cloudpayment_adminbutton.php");
		unlink($_SERVER["DOCUMENT_ROOT"]."/cloudPayments/pay.php");
		unlink($_SERVER["DOCUMENT_ROOT"]."/cloudPayments/lang/ru/pay.php");
    rmdir($_SERVER["DOCUMENT_ROOT"].'/cloudPayments/lang/ru');
    rmdir($_SERVER["DOCUMENT_ROOT"].'/cloudPayments/lang');
    rmdir($_SERVER["DOCUMENT_ROOT"].'/cloudPayments');
		return true;
	}

  function InstallOrderStatus()
  {
        if (!CModule::IncludeModule("sale")) return false;
        $lang[]=array("LID"=>'ru',"NAME"=>GetMessage("cloudpayments_STATUS1"));
        $lang[]=array("LID"=>'en',"NAME"=>"Cloudpayments: Authorized");
        
        $new_status = array(
                'ID' => 'AU',
                'SORT' => 998,
                'LANG' =>$lang,    
        );
        CSaleStatus::Add($new_status);
        unset($new_status);
        unset($lang);
        
        $lang[]=array("LID"=>'ru',"NAME"=>GetMessage("cloudpayments_STATUS2"));
        $lang[]=array("LID"=>'en',"NAME"=>"Cloudpayments: Refund");
        
        $new_status = array(
                'ID' => 'RR',
                'SORT' => 1000,
                'LANG' =>$lang,    
        );
        CSaleStatus::Add($new_status);
        unset($new_status);
        unset($lang);
        
        
        
        
        $lang[]=array("LID"=>'ru',"NAME"=>GetMessage("cloudpayments_STATUS3"));
        $lang[]=array("LID"=>'en',"NAME"=>"Cloudpayments: Confirm");
        
        $new_status = array(
                'ID' => 'CP',
                'SORT' => 999,
                'LANG' =>$lang,    
        );
        CSaleStatus::Add($new_status);
        unset($new_status);
        unset($lang);
        
        
        $lang[]=array("LID"=>'ru',"NAME"=>GetMessage("cloudpayments_STATUS4"));
        $lang[]=array("LID"=>'en',"NAME"=>"Cloudpayments: Void");
        
        $new_status = array(
                'ID' => 'AR',
                'SORT' => 1001,
                'LANG' =>$lang,    
        );
        CSaleStatus::Add($new_status);
        unset($new_status);
        unset($lang);
  }
  
  function UnInstallOrderStatus()
  {
        if (!CModule::IncludeModule("sale")) return false;
        CSaleStatus::Delete("AU");
        CSaleStatus::Delete("CP");
  }


  function InstallEvents()
  {
    $obEventType = new CEventType;
    $obEventType->Add(array(
      "EVENT_NAME"    => "SEND_BILL",
      "NAME"          => GetMessage("EVENT_NAME1"),
      "LID"           => "ru",
      "DESCRIPTION"   => ""
      ));
      
      
    $arr["ACTIVE"]      = "Y";
    $arr["EVENT_NAME"]  = "SEND_BILL";
    $arr["LID"]         = array("s1");
    $arr["EMAIL_FROM"]  = "#DEFAULT_EMAIL_FROM#";
    $arr["EMAIL_TO"]    = "#EMAIL_TO#";
    $arr["BCC"]         = "";
    $arr["SUBJECT"]     = GetMessage("MAIL_SUBJECT");
    $arr["BODY_TYPE"]   = "html";
    $arr["MESSAGE"]     =GetMessage("MAIL_TEXT1");
    $obTemplate = new CEventMessage;
    $obTemplate->Add($arr);
    
    $eventManager = \Bitrix\Main\EventManager::getInstance();
    $eventManager->registerEventHandler("main", "OnAdminContextMenuShow", $this->MODULE_ID, "CloudpaymentHandler2", "OnAdminContextMenuShowHandler_button",9999);
    $eventManager->registerEventHandler("sale", "OnBeforeOrderDelete", $this->MODULE_ID, "CloudpaymentHandler2", "OnCloudpaymentOrderDelete",9999);
    
    $eventManager->registerEventHandler("sale", "OnSaleStatusOrder", $this->MODULE_ID, "CloudpaymentHandler2", "OnCloudpaymentStatusUpdate",9999);    ///
    $eventManager->registerEventHandler("sale", "OnSaleBeforeCancelOrder", $this->MODULE_ID, "CloudpaymentHandler2", "OnCloudpaymentOnSaleBeforeCancelOrder",9999);    ///
    
    return true;
  }

  function UnInstallEvents()
  {
    $eventManager = \Bitrix\Main\EventManager::getInstance();
    $eventManager->unRegisterEventHandler("main", "OnAdminContextMenuShow", $this->MODULE_ID, "CloudpaymentHandler2", "OnAdminContextMenuShowHandler_button");
    $eventManager->unRegisterEventHandler("sale", "OnBeforeOrderDelete", $this->MODULE_ID, "CloudpaymentHandler2", "OnCloudpaymentOrderDelete");
    $eventManager->unRegisterEventHandler("sale", "OnSaleStatusOrder", $this->MODULE_ID, "CloudpaymentHandler2", "OnCloudpaymentStatusUpdate");    //
    $eventManager->unRegisterEventHandler("sale", "OnSaleBeforeCancelOrder", $this->MODULE_ID, "CloudpaymentHandler2", "OnCloudpaymentOnSaleBeforeCancelOrder");    //
    $emessage = new CEventMessage;
    $arFilter = Array(
        "TYPE_ID" => array("SEND_BILL"),
    );
    
    $rsMess = CEventMessage::GetList($by="site_id", $order="desc", $arFilter);
    while($arMess = $rsMess->GetNext())
    {
        $emessage->Delete($arMess['ID']);
    }
 
 

    
    $et = new CEventType;
    $et->Delete("SEND_BILL");
    return true;
  }



	function DoUninstall()
	{	
    $this->UnInstallEvents();
		$this->UnInstallDB();
		$this->UnInstallFiles();
    $this->UnInstallOrderStatus();
		UnRegisterModule($this->MODULE_ID);
    
		return true;
	}
	function UnInstallDB()
	{	
		return true;
	}
}?>