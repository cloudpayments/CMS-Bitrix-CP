<?
use \Bitrix\Main\Localization\Loc;

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");   
$APPLICATION->SetTitle("Страница оплаты заказа");
?>

<?
if ($_GET['ORDER_ID'] and $_GET['hash'])
{
        \Bitrix\Main\Loader::includeModule("sale");
        \Bitrix\Main\Loader::includeModule("catalog");
        IncludeTemplateLangFile(__FILE__);
        $order=\Bitrix\Sale\Order::load($_GET['ORDER_ID']);
        
        $propertyCollection = $order->getPropertyCollection();
        $emailPropValue = $propertyCollection->getUserEmail();
        $email=$emailPropValue->getValue();
        $phonePropValue=$propertyCollection->getPhone();
        $phone=$phonePropValue->getValue();
        $arPaymentsCollection = $order->loadPaymentCollection();
        $currentPaymentOrder = $arPaymentsCollection->current();
        $invoiceId=$currentPaymentOrder->getField("ID");
        $PAY_ID=$order->getPaymentSystemId();
        
        $two_stage_payment=false;
        $widget_f='charge';
        
        $db_ptype = CSalePaySystemAction::GetList($arOrder = Array(), Array("ACTIVE"=>"Y", "PAY_SYSTEM_ID"=>$PAY_ID[0]));
        while ($ptype = $db_ptype->Fetch())
        {
               $CLOUD_PARAMS=unserialize($ptype['PARAMS']);
               if ($CLOUD_PARAMS['TYPE_SYSTEM']['VALUE']) $two_stage_payment=$CLOUD_PARAMS['TYPE_SYSTEM']['VALUE'];
        } 
 
 

        if ($order->getField("STATUS_ID")==$CLOUD_PARAMS['STATUS_AU']['VALUE'] and $_GET['test1']!="Y")
        {
             echo Loc::getMessage("WRONG_AU_STATUS");
             die();
        }
        
        if ($order->isPaid() and $_GET['test1']!="Y")
        {
             echo Loc::getMessage("WRONG_ORDER_PAY");
             die();
        }
        
        $hash=md5($_SERVER["HTTP_HOST"].$_GET['ORDER_ID'].$order->getPrice().$email);
        
        if ($_GET['hash']!=$hash) 
        {
              echo Loc::getMessage("WRONG_HASH");
              die();
        }
       /* 
        $db_ptype = CSalePaySystemAction::GetList($arOrder = Array(), Array("ACTIVE"=>"Y", "PAY_SYSTEM_ID"=>$PAY_ID[0]));
        while ($ptype = $db_ptype->Fetch())
        {
               $CLOUD_PARAMS=unserialize($ptype['PARAMS']);
        }  */
            
            
            
        $sum=0;    
        $PAID_IDS='';
        $DATE_PAID='';
        $paymentCollection = $order->getPaymentCollection();
        foreach ($paymentCollection as $payment):
            if ($payment->isPaid()):
                $PAID_IDS[]=$payment->getField("ID");
            else:
                $sum=$payment->getSum();    
            endif;
        endforeach;      
 
        if ($sum<1): 
                echo Loc::getMessage("WRONG_ORDER_PAY");
                die();
        endif;
            
        if ($PAID_IDS[0]):
              global $DB;
              $results_sql = $DB->Query("SELECT `ID`,`DATE_PAID` FROM `b_sale_order_payment` WHERE `ID`=".implode(" or `ID`=",$PAID_IDS)." and `PAID`='Y' ORDER BY `ID` desc");
        
              if ($row_sql = $results_sql->Fetch()):
                     if (!empty($DATE_PAID)):
                           if (strtotime($row_sql['DATE_PAID'])>strtotime($DATE_PAID)):
                                $DATE_PAID=$row_sql['DATE_PAID'];
                           endif;
                     else:
                          $DATE_PAID=$row_sql['DATE_PAID'];      
                     endif;
              endif;
        endif;
            
            
            
        $basket = \Bitrix\Sale\Basket::loadItemsForOrder($order);
        $basketItems = $basket->getBasketItems();
        $data=array();
        $items=array();
        foreach ($basketItems as $basketItem) {
            $prD=\Bitrix\Catalog\ProductTable::getList(
                array(
                    'filter'=>array('ID'=>$basketItem->getField('PRODUCT_ID')),
                   // 'select'=>array('VAT_ID'),
                )
            )->fetch();
            if($prD){
                if($prD['VAT_ID']==0){
                    $nds=null;
                }
                else{
                    $nds=floatval($basketItem->getField('VAT_RATE'))==0 ? 0 : $basketItem->getField('VAT_RATE')*100;
                }
            }else{
                $nds=null;
            }
    
           $ORDER_PRICE0=$order->getPrice();
           $basketQuantity=$basketItem->getQuantity();
           $PRODUCT_PRICE=$basketItem->getField('PRICE'); 
    
              $items[]=array(
                      'label'=>$basketItem->getField('NAME'),
                      'price'=>number_format($PRODUCT_PRICE,2,".",''),
                      'quantity'=>$basketQuantity,
                      'amount'=>number_format(floatval($PRODUCT_PRICE*$basketQuantity),2,".",''),
                      'vat'=>$nds,
                      'ean13'=>null
              );
        }
        
        //Добавляем доставку
          if ($order->getDeliveryPrice() > 0 && $order->getField("DELIVERY_ID")) 
          {
              if ($params['VAT_DELIVERY'.$order->getField("DELIVERY_ID")]) $Delivery_vat=$params['VAT_DELIVERY'.$order->getField("DELIVERY_ID")];
              else $Delivery_vat=null;
              

              $PRODUCT_PRICE_DELIVERY=$order->getDeliveryPrice(); 
              
              $items[] = array(
                  'label' => GetMessage('DELIVERY_TXT'),
                  'price' => number_format($order->getDeliveryPrice(), 2, ".", ''),
                  'quantity' => 1,
                  'amount' => number_format($PRODUCT_PRICE_DELIVERY, 2, ".", ''),
                  'vat' => $Delivery_vat,  
                  'ean13' => null
              );
              unset($PRODUCT_PRICE_DELIVERY);
              unset($PRODUCT_PRICE);
          }
    
          //$data['PAY_SYSTEM_ID']=$params['PAY_ID'];
          $data['cloudPayments']['customerReceipt']['Items']=$items;
          $data['cloudPayments']['customerReceipt']['taxationSystem']=$params['TYPE_NALOG'];
          $data['cloudPayments']['customerReceipt']['email']=$params['PAYMENT_BUYER_EMAIL'];
          $data['cloudPayments']['customerReceipt']['phone']=$params['PAYMENT_BUYER_PHONE'];
}
else 
{
    die(Loc::getMessage("ORDER_NOT_FOUND"));
}


if ($two_stage_payment)
{
    $widget_f='auth';
}
if ($CLOUD_PARAMS['WIDGET_LANG']['VALUE']) $lang_widget=$CLOUD_PARAMS['WIDGET_LANG']['VALUE'];
else $lang_widget='ru-RU';
?>


<script type="text/javascript" src="/bitrix/js/main/jquery/jquery-1.8.3.min.js?151126639193636"></script>
<script src="https://widget.cloudpayments.ru/bundles/cloudpayments"></script>
<div>
				Ваш заказ <b>№<?=$order->getId()?></b> от <?=$order->getDateInsert()?> успешно создан.
        Номер вашей оплаты: <b>№<?=$order->getId()?></b><br><br>
				Вы можете следить за выполнением своего заказа в <a href="/personal/order/">Персональном разделе сайта</a>. 
        Обратите внимание, что для входа в этот раздел вам необходимо будет ввести логин и пароль пользователя сайта.			
</div>
<br><br>
<button class="cloudpay_button" id="payButton">Оплатить</button>
<div id="result" style="display:none"></div>

<script type="text/javascript">
    var payHandler = function () {
        var widget = new cp.CloudPayments({language:'<?=$lang_widget?>'});
        widget.<?=$widget_f?>({ // options
                publicId: '<?=$CLOUD_PARAMS['APIPASS']['VALUE']?>',
                description: 'заказ № <?=$order->getId()?> на "<?=$_SERVER['HTTP_HOST']?>" от <?=$order->getDateInsert()?>', 
                amount: <?=number_format($sum, 2, '.', '')?>,
                currency: '<?=$order->getCurrency()?>',
                email: '<?=$email?>',
                invoiceId: '<?=$order->getId()?>',
                accountId: '<?=htmlspecialcharsbx($order->getUserId());?>',
                data: <?=CUtil::PhpToJSObject($data,false,true)?>,
            },
                function (options) { // success
                    <?if ($CLOUD_PARAMS['SUCCESS_URL']['VALUE'])
                    {
                       ?>
                         window.location.href="<?=$CLOUD_PARAMS['SUCCESS_URL']['VALUE']?>";
                       <?
                    }
                    else
                    {
                    ?>
                        BX("result").innerHTML="Заказ оплачен";
                        BX.style(BX("result"),"color","green");
                        BX.style(BX("result"),"display","block");
                    <?
                    }
                    ?>
                },
                function (reason, options) { // fail
                    <?if ($CLOUD_PARAMS['FAIL_URL']['VALUE'])
                    {
                       ?>
                         window.location.href="<?=$CLOUD_PARAMS['FAIL_URL']['VALUE']?>";
                       <?
                    }
                    else
                    {
                    ?>
                          BX("result").innerHTML=reason;
                          BX.style(BX("result"),"color","red");
                          BX.style(BX("result"),"display","block");
                    <?
                    }
                    ?>
                });
    };        
    $("#payButton").on("click", payHandler); //кнопка "Оплатить"
</script>





<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>