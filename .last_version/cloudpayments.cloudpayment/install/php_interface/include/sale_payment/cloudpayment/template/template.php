<?
	use Bitrix\Main\Localization\Loc;
	Loc::loadMessages(__FILE__);
	$sum = roundEx($params['PAYMENT_SHOULD_PAY'], 2);
    CJSCore::Init(array("jquery"));
	$SITE_NAME = COption::GetOptionString("main", "server_name", "");
	$description= Loc::getMessage("VBCH_CLPAY_MM_DESC",array("#ORDER_ID#"=>$params['PAYMENT_ID'],"#SITE_NAME#"=>$SITE_NAME,"#DATE#"=>$params['PAYMENT_DATE_INSERT']));
?>

<?
$two_stage_payment=false;
$widget_f='charge';

\Bitrix\Main\Loader::includeModule("sale");
\Bitrix\Main\Loader::includeModule("catalog");

if ($params['BX_PAYSYSTEM_CODE'])
{
                  $db_ptype = CSalePaySystemAction::GetList($arOrder = Array(), Array("ACTIVE"=>"Y", "PAY_SYSTEM_ID"=>$params['BX_PAYSYSTEM_CODE']));
                  while ($ptype = $db_ptype->Fetch())
                  {
                         $CLOUD_PARAMS=unserialize($ptype['PARAMS']);
                         if ($CLOUD_PARAMS['TYPE_SYSTEM']['VALUE']) $two_stage_payment=$CLOUD_PARAMS['TYPE_SYSTEM']['VALUE'];
                  } 
                  

                  if ($two_stage_payment)
                  {
                      $widget_f='auth';
                  }
}

$order=\Bitrix\Sale\Order::load($params['PAYMENT_ID']);
$status_id=$order->getField("STATUS_ID");

if (empty($status_id)) die(Loc::getMessage("ERROR_ORDER_ID"));

if($params['CHECKONLINE']!='N')
{
    if ($status_id!=$CLOUD_PARAMS['STATUS_AU']['VALUE'] and $status_id!=$CLOUD_PARAMS['STATUS_AUTHORIZE']['VALUE'] and !$order->isPaid())
    {
          $basket = \Bitrix\Sale\Basket::loadItemsForOrder($order);
          $basketItems = $basket->getBasketItems();
          $data=array();
          $items=array();
          foreach ($basketItems as $basketItem) {
          
              $prD=\Bitrix\Catalog\ProductTable::getList(
                  array(
                      'filter'=>array('ID'=>$basketItem->getField('PRODUCT_ID')),
                      //'select'=>array('VAT_ID'),
                  )
              )->fetch();
              if($prD){

                  if($prD['VAT_ID']==0)
                  {
                      $nds=null;
                  }
                  else
                  {
                      $nds=floatval($basketItem->getField('VAT_RATE'))==0 ? 0 : $basketItem->getField('VAT_RATE')*100;
                  }
              }else{
                  $nds=null;
              }
              

              $items[]=array(
                      'label'=>$basketItem->getField('NAME'),
                      'price'=>number_format($basketItem->getField('PRICE'),2,".",''),
                      'quantity'=>$basketItem->getQuantity(),
                      'amount'=>number_format(floatval($basketItem->getField('PRICE')*$basketItem->getQuantity()),2,".",''),
                      'vat'=>$nds,
                      'ean13'=>null
              );
          }
      
          //Добавляем доставку
          if ($order->getDeliveryPrice() > 0 && $order->getField("DELIVERY_ID")) 
          {
              if ($params['VAT_DELIVERY'.$order->getField("DELIVERY_ID")]) $Delivery_vat=$params['VAT_DELIVERY'.$order->getField("DELIVERY_ID")];
              else $Delivery_vat=null;
              
              $items[] = array(
                  'label' => GetMessage('DELIVERY_TXT'),
                  'price' => number_format($order->getDeliveryPrice(), 2, ".", ''),
                  'quantity' => 1,
                  'amount' => number_format($order->getDeliveryPrice(), 2, ".", ''),
                  'vat' => $Delivery_vat,  
                  'ean13' => null
              );
              
              
          }

          $data['cloudPayments']['customerReceipt']['Items']=$items;
          $data['cloudPayments']['customerReceipt']['taxationSystem']=$params['TYPE_NALOG'];
          $data['cloudPayments']['customerReceipt']['email']=$params['PAYMENT_BUYER_EMAIL'];
          $data['cloudPayments']['customerReceipt']['phone']=$params['PAYMENT_BUYER_PHONE'];
    }
}



if ($status_id!=$CLOUD_PARAMS['STATUS_AU']['VALUE'] and $status_id!=$CLOUD_PARAMS['STATUS_AUTHORIZE']['VALUE'] and !$order->isPaid() and !$order->isCanceled() and $status_id!=$CLOUD_PARAMS['STATUS_CHANCEL']['VALUE'])
{
    if ($params['WIDGET_LANG']) $lang_widget=$params['WIDGET_LANG'];
    else $lang_widget='ru-RU';
    
    ?>
    <script src="https://widget.cloudpayments.ru/bundles/cloudpayments"></script>
    <button class="cloudpay_button" id="payButton"><?=Loc::getMessage('SALE_HANDLERS_PAY_SYSTEM_CLOUDPAYMENTS_BUTTON_PAID')?></button>
    <div id="result" style="display:none"></div>
    <script type="text/javascript">
        var payHandler = function () {
            var widget = new cp.CloudPayments({language:'<?=$lang_widget?>'});
            widget.<?=$widget_f?>({ // options
                    publicId: '<?=trim(htmlspecialcharsbx($params["APIPASS"]));?>',
                    description: '<?=$description?>', 
                    amount: <?=number_format($sum, 2, '.', '')?>,
                    currency: '<?=$params['PAYMENT_CURRENCY']?>',
                    email: '<?=$params['PAYMENT_BUYER_EMAIL']?>',
                    invoiceId: '<?=htmlspecialcharsbx($params["PAYMENT_ID"]);?>',
                    accountId: '<?=htmlspecialcharsbx($params["PAYMENT_BUYER_ID"]);?>',
                <?if($params['CHECKONLINE']!='N'){?>
                    data: <?=CUtil::PhpToJSObject($data,false,true)?>,
                <?}?>
    
                },
                function (options) { // success
                    <?if ($params['SUCCESS_URL'])
                    {
                       ?>
                         window.location.href="<?=$params['SUCCESS_URL']?>?InvId=<?=htmlspecialcharsbx($params["PAYMENT_ID"]);?>";
                       <?
                    }
                    else
                    {
                    ?>
                        BX("result").innerHTML="<?=GetMessage('VBCH_CLOUDPAY_SUCCESS')?>";
                        BX.style(BX("result"),"color","green");
                        BX.style(BX("result"),"display","block");
                    <?
                    }
                    ?>
                },
                function (reason, options) { // fail
                    <?if ($params['FAIL_URL'])
                    {
                       ?>
                         window.location.href="<?=$params['FAIL_URL']?>?InvId=<?=htmlspecialcharsbx($params["PAYMENT_ID"]);?>";
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
}
else if ($order->isCanceled() || $status_id==$CLOUD_PARAMS['STATUS_CHANCEL']['VALUE'])
{
     echo GetMessage('NO_PAY_CANCEL');
}
else echo GetMessage('NO_PAY');
?>