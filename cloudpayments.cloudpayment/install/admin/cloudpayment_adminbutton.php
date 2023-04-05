<?
  require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php');

  use Bitrix\Main\Localization\Loc;

  Loc::loadMessages(__FILE__);

  if (!CModule::IncludeModule("main"))
    return;
  \Bitrix\Main\Loader::includeModule('sale');

  $order = \Bitrix\Sale\Order::load($_REQUEST['ORDER_ID']);
  $propertyCollection = $order->getPropertyCollection();
  $paymentCollection = $order->getPaymentCollection();
  foreach ($paymentCollection as $payment) {
    $psName = $payment->getPaymentSystemName(); // название платежной системы
    $psId = $payment->getPaymentSystemId();
    $ps = $payment->getPaySystem();
  }

  if ($_GET['type'] && $ps->getField("ACTION_FILE") == 'cloudpayment') {
    switch ($_GET['type']) {
      case 'send_bill':
        $db_ptype = CSalePaySystemAction::GetList($arOrder = Array(), Array("ACTIVE" => "Y", "PAY_SYSTEM_ID" => $psId));
        while ($ptype = $db_ptype->Fetch()) {
          $CLOUD_PARAMS = unserialize($ptype['PARAMS']);
          if ($CLOUD_PARAMS['APIPASS']['VALUE'])
            $APIPASS = $CLOUD_PARAMS['APIPASS']['VALUE'];
          if ($CLOUD_PARAMS['APIKEY']['VALUE'])
            $APIKEY = $CLOUD_PARAMS['APIKEY']['VALUE'];
        }

        if ($CLOUD_PARAMS['CREATE_ORDER_TYPE']['VALUE'] == 2) { //Отправка на email или sms методом create

          $Property = $order->getPropertyCollection()->getArray();

          foreach ($Property['properties'] as $prop) {
            if ($prop['IS_EMAIL'] == 'Y') {
              $email = current($prop['VALUE']);
            }
            if ($prop['IS_PHONE'] == 'Y') {
              $phone = current($prop['VALUE']);
            }
          }
          $fullname = "";
          if($UID = $order->getUserId()) {
            $cuser = CUser::GetByID($UID)->Fetch();
            $fullname = $cuser['NAME'];
            if($cuser['LAST_NAME']) $fullname = $cuser['LAST_NAME'].' '.$fullname;
            if($cuser['SECOND_NAME']) $fullname = $fullname.' '.$cuser['SECOND_NAME'];
          }

          $order_bill = array(
            "Amount" => $order->getPrice(),
            "Description" => Loc::getMessage("ORDER_NUM") . $_GET['ORDER_ID'],
            "RequireConfirmation"=>(!empty($CLOUD_PARAMS['TYPE_SYSTEM']['VALUE'])) ? "true": "false",
            "InvoiceId" => $_GET['ORDER_ID'],
            "Currency" => "RUB", //Валюта задана статично, нужно получать ее правельно (из заказа?)
            "AccountId" => $fullname, //получить ФИО покупателя
          );






          if($CLOUD_PARAMS['CHECKONLINE']['VALUE'] != 'N') {
            $status_id = $order->getField("STATUS_ID");
            if($status_id != $CLOUD_PARAMS['STATUS_AU']['VALUE'] and $status_id != $CLOUD_PARAMS['STATUS_AUTHORIZE']['VALUE'] and !$paymentCollection->isPaid()) {
              $basket = \Bitrix\Sale\Basket::loadItemsForOrder($order);
              $basketItems = $basket->getBasketItems();
              $data = array();
              $items = array();

              foreach($basketItems as $basketItem) {

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

                $PROP_SBR1 = $CLOUD_PARAMS['SPOSOB_RASCHETA1']['VALUE'];
                $PROP_PRDR1 = $CLOUD_PARAMS['PREDMET_RASCHETA1']['VALUE'];

                if(!$PROP_SBR1) {
                  $PROP_SBR1 = 0;
                }

                if(!$PROP_PRDR1) {
                  $PROP_PRDR1 = 0;
                }


                $sum2 = $sum2 + ($PRODUCT_PRICE * $basketQuantity);
                $items[] = array(
                  'label' => $basketItem->getField('NAME'),
                  'price' => number_format($PRODUCT_PRICE, 2, ".", ''),
                  'quantity' => $basketQuantity,
                  'amount' => number_format($PRODUCT_PRICE * $basketQuantity, 2, ".", ''),
                  'vat' => $nds,
                  'object'=>$PROP_PRDR1,
                  'method'=>$PROP_SBR1,
                );
              }

              if($order->getDeliveryPrice() > 0 && $order->getField("DELIVERY_ID")) {
                if($CLOUD_PARAMS['VAT_DELIVERY' . $order->getField("DELIVERY_ID")])
                  $Delivery_vat = $CLOUD_PARAMS['VAT_DELIVERY' . $order->getField("DELIVERY_ID")]['VALUE']; else $Delivery_vat = NULL;

                $PRODUCT_PRICE_DELIVERY = $order->getDeliveryPrice();


                $sum2 = $sum2 + $PRODUCT_PRICE_DELIVERY;
                $items[] = array(
                  'label' => GetMessage('DELIVERY_TXT'),
                  'price' => number_format($PRODUCT_PRICE_DELIVERY, 2, ".", ''),
                  'quantity' => 1,
                  'amount' => number_format($PRODUCT_PRICE_DELIVERY, 2, ".", ''),
                  'vat' => $Delivery_vat,
                  'object' => $PROP_PRDR1,
                  'method' => "4",
                );
                unset($PRODUCT_PRICE_DELIVERY);
                unset($PRODUCT_PRICE);
              }

              $data['cloudPayments']['customerReceipt']['Items'] = $items;
              $data['cloudPayments']['customerReceipt']['taxationSystem'] = $CLOUD_PARAMS['TYPE_NALOG']['VALUE'];
              $data['cloudPayments']['customerReceipt']['calculationPlace'] = $CLOUD_PARAMS['calculationPlace']['VALUE'];
              $data['cloudPayments']['customerReceipt']['email'] = $propertyCollection->getUserEmail()->getValue();
              $data['cloudPayments']['customerReceipt']['phone'] =  $propertyCollection->getPhone()->getValue();

              $data['cloudPayments']['customerReceipt']['amounts']["electronic"] = $sum2;
              $data['cloudPayments']['customerReceipt']['amounts']["advancePayment"] = 0;
              $data['cloudPayments']['customerReceipt']['amounts']["credit"] = 0;
              $data['cloudPayments']['customerReceipt']['amounts']["provision"] = 0;
              $order_bill['JsonData'] = json_encode($data);
            }
          }



          $TXT_MESS = Loc::getMessage("TXT_MESS");

          if ($CLOUD_PARAMS['CREATE_ORDER_PHONE']['VALUE'] == "Y") {
            if (empty($phone)) {
              $ERROR = Loc::getMessage("ERROR_PHONE");
            } else {
              $order_bill['Phone'] = $phone;
              $order_bill['SendViber'] = "true";
              $order_bill["SendSms"] = "true";
            }
          }



          if ($CLOUD_PARAMS['CREATE_ORDER_EMAIL']['VALUE'] == "Y") {
            if (empty($email)) {
              $ERROR = Loc::getMessage("ERROR_EMAIL");
            } else {
              $order_bill['Email'] = $email;
              $order_bill["SendEmail"] = "true";
            }
          }

          if ($CLOUD_PARAMS['TYPE_SYSTEM']['VALUE']){
            $order_bill['RequireConfirmation'] = "true";
          } else $order_bill['RequireConfirmation'] = "false";


          if ($CLOUD_PARAMS['CREATE_ORDER_PHONE']['VALUE'] != "Y" && $CLOUD_PARAMS['CREATE_ORDER_EMAIL']['VALUE'] != "Y") {
            $ERROR = Loc::getMessage("ERROR_NO_VALUE");
          }

          $file = $_SERVER['DOCUMENT_ROOT'] . '/log_cloudpayments2020_2.txt';
          $current = file_get_contents($file);
          $current .= print_r($order_bill,1) . "\n";
          file_put_contents($file, $current);


          if (empty($ERROR)) {
            $ch = curl_init('https://api.cloudpayments.ru/orders/create');
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, trim($APIPASS) . ":" . trim($APIKEY));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            //  curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "X-Request-ID:" . $reque));
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $order_bill);
            $content = curl_exec($ch);



            $html = json_decode($content);
            if ($html->Success) {
              echo $TXT_MESS;
            } else {
              echo $html->Message;
            }
            curl_close($ch);
          } else {
            echo $ERROR;
          }
        } else {


          $emailPropValue = $propertyCollection->getUserEmail();
          $basket = $order->getBasket();
          $orderBasket = $basket->getListOfFormatText();
          $email = $emailPropValue->getValue();
          $orderBasket_list = '';
          if ($orderBasket)
            foreach ($orderBasket as $item) {
              $orderBasket_list .= $item . '<br>';
            }

          $hash = md5($_SERVER["HTTP_HOST"] . $_GET['ORDER_ID'] . $order->getPrice() . $email);


          if ($order->getPrice() > 0 and $email) {
            $arEventFields = array(
              "EMAIL_TO" => $email,
              "SITE_NAME" => 'http://' . $_SERVER['HTTP_HOST'],
              "ORDER_ID" => $_GET['ORDER_ID'],
              "BASKET_LIST" => $orderBasket_list,
              "ORDER_SUMM" => CurrencyFormat($order->getPrice(), 'RUB'),
              "ORDER_LINK" => 'http://' . $_SERVER["HTTP_HOST"] . '/cloudPayments/pay.php?ORDER_ID=' . $_GET['ORDER_ID'] . '&hash=' . $hash
            );

            // echo 'http://'.$_SERVER["HTTP_HOST"].'/cloudPayments/pay.php?ORDER_ID='.$_GET['ORDER_ID'].'&hash='.$hash;
        //    echo $arEventFields['ORDER_LINK'];
            CEvent::SendImmediate("SEND_BILL", $_GET['LID'], $arEventFields);
            echo Loc::getMessage("TXT_MESS");
          }

        }
        break;

      case 'pay_ok':

        break;
    }
  }

?>