<?
	global $MESS;
	IncludeModuleLangFile(__DIR__ . "/install.php");
	
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
			if (!CModule::IncludeModule("sale"))
				return false;
			
			CSaleStatus::Add(array(
				'ID' => 'AU',
				'SORT' => 998,
				'LANG' =>array(
					array("LID"=>'ru',"NAME"=>GetMessage("cloudpayments_STATUS1")),
					array("LID"=>'en',"NAME"=>"Cloudpayments: Authorized")
				),
			));
			
			CSaleStatus::Add(array(
				'ID' => 'RR',
				'SORT' => 1000,
				'LANG' =>array(
					array("LID"=>'ru',"NAME"=>GetMessage("cloudpayments_STATUS2")),
					array("LID"=>'en',"NAME"=>"Cloudpayments: Refund")
				),
			));
			
			CSaleStatus::Add(array(
				'ID' => 'CP',
				'SORT' => 999,
				'LANG' =>array(
					array("LID"=>'ru',"NAME"=>GetMessage("cloudpayments_STATUS3")),
					array("LID"=>'en',"NAME"=>"Cloudpayments: Confirm")
				),
			));
			
			CSaleStatus::Add(array(
				'ID' => 'AR',
				'SORT' => 1001,
				'LANG' =>array(
					array("LID"=>'ru',"NAME"=>GetMessage("cloudpayments_STATUS4")),
					array("LID"=>'en',"NAME"=>"Cloudpayments: Void")
				),
			));
		}
		
		function UnInstallOrderStatus()
		{
			if (!CModule::IncludeModule("sale"))
				return false;
			
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
			
			$obTemplate = new CEventMessage;
			$obTemplate->Add(array(
				"ACTIVE"      => "Y",
				"EVENT_NAME"  => "SEND_BILL",
				"LID"         => array("s1"),
				"EMAIL_FROM"  => "#DEFAULT_EMAIL_FROM#",
				"EMAIL_TO"    => "#EMAIL_TO#",
				"BCC"         => "",
				"SUBJECT"     => GetMessage("MAIL_SUBJECT"),
				"BODY_TYPE"   => "html",
				"MESSAGE"     =>GetMessage("MAIL_TEXT1")
			));
			
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
			
			$rsMess = CEventMessage::GetList("site_id", "desc", array(
				"TYPE_ID" => array("SEND_BILL"),
			));
			
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