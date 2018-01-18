<? 
use Bitrix\Sale\PaySystem;


class CloudpaymentHandler2
{
    public static function OnAdminContextMenuShowHandler_button(&$items) {

        $two_stage_payment=false;
        
        if ($GLOBALS['APPLICATION']->GetCurPage()=='/bitrix/admin/sale_order_view.php' || $GLOBALS['APPLICATION']->GetCurPage()=='/bitrix/admin/sale_order_edit.php') {
            if (array_key_exists('ID', $_REQUEST) && $_REQUEST['ID']>0 && \Bitrix\Main\Loader::includeModule('sale')) {
                //$post = \Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getQueryList()->toArray();
                $order=\Bitrix\Sale\Order::load($_REQUEST['ID']);
                $propertyCollection = $order->getPropertyCollection();
                        
                $two_stage_payment=true; ///двухстадийная оплата
                
                
                $TYPE=$order->isPaid() ? 'Y' : "N";
                
                $paymentCollection = $order->getPaymentCollection();
                foreach ($paymentCollection as $payment) 
                {
                    $psName = $payment->getPaymentSystemName(); // название платежной системы
                    $psId=$payment->getPaymentSystemId();
                }

                
                
                if ($TYPE=='N' and $psName=='CloudPayments')
                {
                      if ($psId)
                      {
                                        $db_ptype = CSalePaySystemAction::GetList($arOrder = Array(), Array("ACTIVE"=>"Y", "PAY_SYSTEM_ID"=>$psId));
                                        while ($ptype = $db_ptype->Fetch())
                                        {
                                               $CLOUD_PARAMS=unserialize($ptype['PARAMS']);
                                               if ($CLOUD_PARAMS['TYPE_SYSTEM']['VALUE']) $two_stage_payment=$CLOUD_PARAMS['TYPE_SYSTEM']['VALUE'];
                                        } 
                      }
                      
                      
                         global $DB;
                         $PAY_VOUCHER_NUM='';
                         
                         $results = $DB->Query("select `PAY_VOUCHER_NUM` from `b_sale_order` where `ID`=".$_GET['ID']);
                         if ($row = $results->Fetch())
                         {
                               if ($row['PAY_VOUCHER_NUM'])
                               {
                                    $PAY_VOUCHER_NUM=$row['PAY_VOUCHER_NUM'];
                               }
                         }      
                      
                      
                      $FirstItem = array_shift($items);
                      if (!$PAY_VOUCHER_NUM)
                      {
                                    $items = array_merge(array($FirstItem),array(array(
                                        'TEXT' => 'Отправить счет клиенту',
                                        'LINK' => 'javascript:'.$GLOBALS['APPLICATION']->GetPopupLink(
                                                array(
                                                    'URL' => "/bitrix/admin/cloudpayment_adminbutton.php?type=send_bill&ORDER_ID={$_GET['ID']}&LID=".$order->getSiteId(),
                                                    'PARAMS' => array(
                                                        'width' => '400',
                                                        'height' => '40',
                                                        'resize' => false,
                                                        'resizable' => false,
                                                    )
                                                )
                                            ),
                                        'WARNING' => 'Y',
                                    )),$items);
                      }
                    
                   
                      if ($two_stage_payment and $PAY_VOUCHER_NUM)
                      {
                                      $FirstItem = array_shift($items);
                                      $items = array_merge(array($FirstItem),array(array(
                                          'TEXT' => 'Подтвердить оплату Cloudpayments',
                                          'LINK' => 'javascript:'.$GLOBALS['APPLICATION']->GetPopupLink(
                                                  array(
                                                      'URL' => "/bitrix/admin/cloudpayment_adminbutton.php?type=pay_ok&ORDER_ID={$_GET['ID']}&LID=".$order->getSiteId(),
                                                      'PARAMS' => array(
                                                          'width' => '400',
                                                          'height' => '120',
                                                          'resize' => false,
                                                          'resizable' => false,
                                                      )
                                                  )
                                              ),
                                          'WARNING' => 'Y',
                                      )),$items);
                               
                          
                      }    
                      
                      foreach($items as $Key => $arItem) 
                      {
                          if ($arItem['LINK']=='/bitrix/admin/sale_order_new.php?lang=ru&LID=s1') {
                              unset($items[$Key]);
                          }
                      }
                     
                }     ///777
                
           }
           
        }
   }  
   
   
   function OnCloudpaymentOrderDelete($ID)
   {
        CModule::IncludeModule("sale");
        $arFilter = Array("ID"=>$ID);
        $db_sales = CSaleOrder::GetList(array("DATE_INSERT" => "ASC"), $arFilter);
        if ($ar_sales = $db_sales->Fetch())
        {
            if ($ar_sales['STATUS_ID']=="AU")
            {
                                $API_URL='https://api.cloudpayments.ru/payments/void';
                                
                                $order=\Bitrix\Sale\Order::load($ID);
                                $propertyCollection = $order->getPropertyCollection();
                                $paymentCollection = $order->getPaymentCollection();
                                foreach ($paymentCollection as $payment) 
                                {
                                    $psName = $payment->getPaymentSystemName(); // название платежной системы
                                    $psId=$payment->getPaymentSystemId();
                                }
                                
                                if ($psId)
                                {
                                                  $db_ptype = CSalePaySystemAction::GetList($arOrder = Array(), Array("ACTIVE"=>"Y", "PAY_SYSTEM_ID"=>$psId));
                                                  while ($ptype = $db_ptype->Fetch())
                                                  {
                                                         $CLOUD_PARAMS=unserialize($ptype['PARAMS']);
                                                         if ($CLOUD_PARAMS['APIPASS']['VALUE']) $APIPASS=$CLOUD_PARAMS['APIPASS']['VALUE'];
                                                         if ($CLOUD_PARAMS['APIKEY']['VALUE']) $APIKEY=$CLOUD_PARAMS['APIKEY']['VALUE'];
                                                         
                                                  } 
                                }
                                
                                global $DB;
                                $results = $DB->Query("select `PAY_VOUCHER_NUM` from `b_sale_order` where `ID`=".$ID);
                                if ($row = $results->Fetch())
                                {                            
                                    $ORDER_PRICE=$order->getPrice();
                                    if ($row['PAY_VOUCHER_NUM'] and $ORDER_PRICE>0)
                                    {
                                    
                                          if($curl = curl_init()) 
                                          {     
                                                                     
                                                $ch = curl_init();     
                                                curl_setopt($ch, CURLOPT_URL, $API_URL);
                                                curl_setopt($ch, CURLOPT_HEADER, false);
                                                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);       
                                                curl_setopt($ch, CURLOPT_USERPWD, $APIPASS . ":" . $APIKEY);
                                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                                curl_setopt($ch, CURLOPT_POSTFIELDS, "TransactionId=".$row['PAY_VOUCHER_NUM']);
                                                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:39.0) Gecko/20100101 Firefox/39.0');   
                                                $data = curl_exec($ch);
                                                curl_close($ch);
                                         
                                                $data1=json_decode($data);
                                           }    
                                     }
                                  }          
            }
        }

   }   
}