<?

  use Bitrix\Main\Localization\Loc;

  Loc::loadMessages(__FILE__);
  $sum = roundEx($params['PAYMENT_SHOULD_PAY'], 2);
  CJSCore::Init(array("jquery"));
  $SITE_NAME = COption::GetOptionString("main", "server_name", "");
  $description = Loc::getMessage("VBCH_CLPAY_MM_DESC", array(
    "#ORDER_ID#" => $params['PAYMENT_ID'],
    "#SITE_NAME#" => $SITE_NAME,
    "#DATE#" => $params['PAYMENT_DATE_INSERT']
  ));


?>
<?
  global $DB;
  $two_stage_payment = false;
  $widget_f = 'charge';

  \Bitrix\Main\Loader::includeModule("sale");
  \Bitrix\Main\Loader::includeModule("catalog");

  if($params['BX_PAYSYSTEM_CODE']) {
    $db_ptype = CSalePaySystemAction::GetList($arOrder = Array(), Array(
      "ACTIVE" => "Y",
      "PAY_SYSTEM_ID" => $params['BX_PAYSYSTEM_CODE']
    ));
    while($ptype = $db_ptype->Fetch()) {
      $CLOUD_PARAMS = unserialize($ptype['PARAMS']);
      if($CLOUD_PARAMS['TYPE_SYSTEM']['VALUE'])
        $two_stage_payment = $CLOUD_PARAMS['TYPE_SYSTEM']['VALUE'];
    }

    if($two_stage_payment) {
      $widget_f = 'auth';
    }
  }
  $NO_ORDER = false;
  $results_sql = $DB->Query("SELECT `ID` FROM `b_sale_order` where `ID`= " . $params['PAYMENT_ID']);
  if(!$row_sql = $results_sql->Fetch()):
    $results_sql = $DB->Query("SELECT `ORDER_ID` FROM `b_sale_order_payment` where `ID`= " . $params['PAYMENT_ID']);
    if(!$row_sql = $results_sql->Fetch()){
      echo GetMessage("NO_ORDER_ID");
      $NO_ORDER = true;
    }
  endif;

  if(!$NO_ORDER) {
    $order = \Bitrix\Sale\Order::load($params['PAYMENT_ID']);
    if(empty($order)) $order = \Bitrix\Sale\Order::load($row_sql['ORDER_ID']);
    $PAID_IDS = array();
    $DATE_PAID = '';
    $paymentCollection = $order->getPaymentCollection();
    foreach($paymentCollection as $payment):
      if($payment->isPaid()):
        $PAID_IDS[] = $payment->getField("ID");
      endif;
    endforeach;

    if($PAID_IDS[0]):
      $results_sql = $DB->Query("SELECT `ID`,`DATE_PAID` FROM `b_sale_order_payment` WHERE `ID`=" . implode(" or `ID`=", $PAID_IDS) . " and `PAID`='Y' ORDER BY `ID` desc");

      if($row_sql = $results_sql->Fetch()):
        if(!empty($DATE_PAID)):
          if(strtotime($row_sql['DATE_PAID']) > strtotime($DATE_PAID)):
            $DATE_PAID = $row_sql['DATE_PAID'];
          endif;
        else:
          $DATE_PAID = $row_sql['DATE_PAID'];
        endif;
      endif;
    endif;

    $status_id = $order->getField("STATUS_ID");

    if(empty($status_id))
      die(Loc::getMessage("ERROR_ORDER_ID"));
    $order_sum_ = $order->getPrice();
    $sum2 = 0;

    if($params['CHECKONLINE'] != 'N') {
      if($status_id != $CLOUD_PARAMS['STATUS_AU']['VALUE'] and $status_id != $CLOUD_PARAMS['STATUS_AUTHORIZE']['VALUE'] and !$paymentCollection->isPaid()) {
        $basket = \Bitrix\Sale\Basket::loadItemsForOrder($order);
        $basketItems = $basket->getBasketItems();
        $data = array();
        $items = array();

        foreach($basketItems as $basketItem) {
          foreach ($basketItem->getPropertyCollection() as $item)
          {
            if($item->getField('CODE') === 'SPIC')  $spic = $item->getField('VALUE');
            if ($item->getField('CODE') === 'PACKAGE_CODE') $packageCode = $item->getField('VALUE');
          }

          $prD = \Bitrix\Catalog\ProductTable::getList(array(
            'filter' => array('ID' => $basketItem->getField('PRODUCT_ID')),
            //'select'=>array('VAT_ID'),
          ))->fetch();
          if($prD) {

            if($prD['VAT_ID'] == 0) {
              $nds = NULL;
            } else {
              $nds = floatval($basketItem->getField('VAT_RATE')) == 0 ? 0 : $basketItem->getField('VAT_RATE') * 100;
            }
          } else {
            $nds = NULL;
          }

          $ORDER_PRICE0 = $order->getPrice();
          $basketQuantity = $basketItem->getQuantity();
          $PRODUCT_PRICE = number_format($basketItem->getField('PRICE'), 2, ".", '');

          $PROP_SBR1 = $params['SPOSOB_RASCHETA1'];
          $PROP_PRDR1 = $params['PREDMET_RASCHETA1'];


          if(!$PROP_SBR1) {
            $PROP_SBR1 = 0;
          }

          if(!$PROP_PRDR1) {
            $PROP_PRDR1 = 0;
          }

          $object = $PROP_PRDR1;
          $method = $PROP_SBR1;

          $sum2 = $sum2 + ($PRODUCT_PRICE * $basketQuantity);
          $items[] = array(
            'label' => $basketItem->getField('NAME'),
            'price' => number_format($PRODUCT_PRICE, 2, ".", ''),
            'quantity' => $basketQuantity,
            'amount' => number_format($PRODUCT_PRICE * $basketQuantity, 2, ".", ''),
            'vat' => $nds,
            "object"=>$object,
            "method"=>$method,
            "spic"=>isset($spic) ?$spic : '',
            "packageCode"=>isset($packageCode)? $packageCode : ''
          );
        }

        if($order->getDeliveryPrice() > 0 && $order->getField("DELIVERY_ID")) {
          if($params['VAT_DELIVERY' . $order->getField("DELIVERY_ID")])
            $Delivery_vat = $params['VAT_DELIVERY' . $order->getField("DELIVERY_ID")]; else $Delivery_vat = NULL;

          $PRODUCT_PRICE_DELIVERY = number_format($order->getDeliveryPrice(), 2, ".", '');

          $sum2 = $sum2 + $PRODUCT_PRICE_DELIVERY;
          $items[] = array(
            'label' => GetMessage('DELIVERY_TXT'),
            'price' => number_format($PRODUCT_PRICE_DELIVERY, 2, ".", ''),
            'quantity' => 1,
            'amount' => number_format($PRODUCT_PRICE_DELIVERY, 2, ".", ''),
            'vat' => $Delivery_vat,
            'object' => "4",
            'method' => $method,
            'spic' => $params['SPIC'] ?: '',
            'packageCode' => $params['PACKAGE_CODE'] ?: ''
          );
          unset($PRODUCT_PRICE_DELIVERY);
          unset($PRODUCT_PRICE);
        }

        $data['PAY_SYSTEM_ID'] = $params['PAY_ID'];
        $data['cloudPayments']['customerReceipt']['Items'] = $items;
        $data['cloudPayments']['customerReceipt']['taxationSystem'] = $params['TYPE_NALOG'];
        $data['cloudPayments']['customerReceipt']['calculationPlace'] = $params['calculationPlace'];
        $data['cloudPayments']['customerReceipt']['email'] = $params['PAYMENT_BUYER_EMAIL'];
        $data['cloudPayments']['customerReceipt']['phone'] = $params['PAYMENT_BUYER_PHONE'];

        $data['cloudPayments']['customerReceipt']['amounts']["electronic"] = $sum2;
        $data['cloudPayments']['customerReceipt']['amounts']["advancePayment"] = 0;
        $data['cloudPayments']['customerReceipt']['amounts']["credit"] = 0;
        $data['cloudPayments']['customerReceipt']['amounts']["provision"] = 0;

        if(!empty($params['SPIC']) && !empty($params['PACKAGE_CODE'])) {
          $data['cloudPayments']['customerReceipt']['AdditionalReceiptInfos'] = ["Вы стали обладателем права на 1% cashback"]; // Это статичное значение;
        }
      }
    }

    if($status_id != $CLOUD_PARAMS['STATUS_AU']['VALUE'] and $status_id != $CLOUD_PARAMS['STATUS_AUTHORIZE']['VALUE'] and !$paymentCollection->isPaid() and !$order->isCanceled() and $status_id != $CLOUD_PARAMS['STATUS_CHANCEL']['VALUE']) {
      if($params['WIDGET_LANG'])
        $lang_widget = $params['WIDGET_LANG']; else $lang_widget = 'ru-RU';

      if($params['WIDGET_SKIN'])
        $skin = $params['WIDGET_SKIN']; else $skin = 'classic';

      ?>
       <script src = "https://widget.cloudpayments.ru/bundles/cloudpayments?cms=1CBitrix"></script>
       <button class = "cloudpay_button" id = "payButton"><?= Loc::getMessage('SALE_HANDLERS_PAY_SYSTEM_CLOUDPAYMENTS_BUTTON_PAID') ?></button>
       <div id = "result" style = "display:none"></div>
       <script type = "text/javascript">
           var payHandler = function () {
               var widget = new cp.CloudPayments({language: '<?=$lang_widget?>'});
               widget.<?=$widget_f?>({ // options
                       publicId: '<?=trim(htmlspecialcharsbx($params["APIPASS"]));?>',
                       description: '<?=$description?>',
                       amount: <?=number_format($sum, 2, '.', '')?>,
                       currency: '<?=$params['PAYMENT_CURRENCY']?>',
                       email: '<?=$params['PAYMENT_BUYER_EMAIL']?>',
                       invoiceId: '<?=htmlspecialcharsbx($params["PAYMENT_ID"]);?>',
                       accountId: '<?=htmlspecialcharsbx($params["PAYMENT_BUYER_ID"]);?>',
                       skin: '<?=$skin?>',
                   <?if($params['CHECKONLINE'] != 'N'){?>
                       data: <?=CUtil::PhpToJSObject($data, false, true)?>,
                   <?}?>

                   },
                   function (options) { // success
                     <?if ($params['SUCCESS_URL'])
                     {
                     ?>
                       window.location.href = "<?=$params['SUCCESS_URL']?>?InvId=<?=htmlspecialcharsbx($params["PAYMENT_ID"]);?>";
                     <?
                     }
                     else
                     {
                     ?>
                       BX("result").innerHTML = "<?=GetMessage('VBCH_CLOUDPAY_SUCCESS')?>";
                       BX.style(BX("result"), "color", "green");
                       BX.style(BX("result"), "display", "block");
                     <?
                     }
                     ?>
                   },
                   function (reason, options) { // fail
                     <?if ($params['FAIL_URL'])
                     {
                     ?>
                       window.location.href = "<?=$params['FAIL_URL']?>?InvId=<?=htmlspecialcharsbx($params["PAYMENT_ID"]);?>";
                     <?
                     }
                     else
                     {
                     ?>
                       BX("result").innerHTML = reason;
                       BX.style(BX("result"), "color", "red");
                       BX.style(BX("result"), "display", "block");
                     <?
                     }
                     ?>
                   });
           };
           $("#payButton").on("click", payHandler);
       </script>
      <?
    } elseif($order->isCanceled() || $status_id == $CLOUD_PARAMS['STATUS_CHANCEL']['VALUE']) {
      echo GetMessage('NO_PAY_CANCEL');
    } else echo GetMessage('NO_PAY');
  }

?>