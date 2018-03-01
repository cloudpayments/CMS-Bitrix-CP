<?
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_before.php');

if (!CModule::IncludeModule("main")) return;
\Bitrix\Main\Loader::includeModule('sale');
 
 
$order=\Bitrix\Sale\Order::load($_REQUEST['ORDER_ID']);
$propertyCollection = $order->getPropertyCollection();
$paymentCollection = $order->getPaymentCollection();
foreach ($paymentCollection as $payment) 
{
    $psName = $payment->getPaymentSystemName(); // название платежной системы
    $psId=$payment->getPaymentSystemId();
    $ps=$payment->getPaySystem();
}

if ($_GET['type'] && $ps->getField("ACTION_FILE")=='cloudpayment')
{
     switch($_GET['type'])
     {
             case 'send_bill':
             
                    $emailPropValue = $propertyCollection->getUserEmail();
                    $basket=$order->getBasket();
                    $orderBasket = $basket->getListOfFormatText();
                    $email=$emailPropValue->getValue();
                    $orderBasket_list='';
                    if ($orderBasket)
                    foreach ($orderBasket as $item)
                    {
                        $orderBasket_list.=$item.'<br>';
                    }
                    
                    $hash=md5($_SERVER["HTTP_HOST"].$_GET['ORDER_ID'].$order->getPrice().$email);
                    
                    
                    
                    if ($order->getPrice()>0 and $email)
                    {
                          $arEventFields=array(
                              "EMAIL_TO"=>$email,
                              "SITE_NAME"=>'http://'.$_SERVER['HTTP_HOST'],
                              "ORDER_ID"=>$_GET['ORDER_ID'],
                              "BASKET_LIST"=>$orderBasket_list,
                              "ORDER_SUMM"=>CurrencyFormat($order->getPrice(), 'RUB'),
                              "ORDER_LINK"=>'http://'.$_SERVER["HTTP_HOST"].'/cloudPayments/pay.php?ORDER_ID='.$_GET['ORDER_ID'].'&hash='.$hash
                          );
                        // echo 'http://'.$_SERVER["HTTP_HOST"].'/cloudPayments/pay.php?ORDER_ID='.$_GET['ORDER_ID'].'&hash='.$hash;
                         CEvent::SendImmediate("SEND_BILL", $_GET['LID'], $arEventFields);
                        echo str_replace("#PRICE#",CurrencyFormat($order->getPrice(), 'RUB'),GetMessage("SCHET_TEXT1"));
                    }
             
             break;
             
             case 'pay_ok':
                   /*  
                    $API_URL='https://api.cloudpayments.ru/payments/confirm';
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

                     //////
                    
                    $results = $DB->Query("select `PAY_VOUCHER_NUM` from `b_sale_order` where `ID`=".intval($_GET['ORDER_ID']));
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
                                      curl_setopt($ch, CURLOPT_POSTFIELDS, "TransactionId=".$row['PAY_VOUCHER_NUM']."&Amount=".$ORDER_PRICE);
                                      curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:39.0) Gecko/20100101 Firefox/39.0');   
                                      $data = curl_exec($ch);
                                      curl_close($ch);
                               
                                      $data1=json_decode($data);

                                      if ($data1->Success) 
                                      {
                                            global $DB;
                                            $arFields=array(
                                                "SUM_PAID"=>$ORDER_PRICE,
                                                "PAYED"=>"Y",
                                                "STATUS_ID"=>"P",
                                            );                                            
                                            //$DB->Update("b_sale_order", $arFields, "WHERE ID=".$_GET['ORDER_ID'], $err_mess.__LINE__);        ///обновление заказа

                                            CSaleOrder::Update($_GET['ORDER_ID'], $arFields);
                                            unset($arFields);
                                            
                                            
                                            $arFields=array(
                                                "PAID"=>'"Y"'
                                            );
                                            $DB->Update("b_sale_order_payment", $arFields, "WHERE ORDER_ID=".intval($_GET['ORDER_ID']), $err_mess.__LINE__);        ///обновление заказа
                                            unset($arFields);
                                      
                                            echo GetMessage("SUMMA_TXT1").CurrencyFormat($order->getPrice(), 'RUB').'<br>';
                                            echo GetMessage("PAY_OK1");                                            
                                      }
                                      else echo iconv('utf-8','cp1251',$data1->Message);
                                      
                                  }
                        }

                    }  
                    */
             break;
     }
}

?>