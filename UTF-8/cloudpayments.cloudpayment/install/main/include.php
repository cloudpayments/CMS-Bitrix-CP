<?

  use Bitrix\Sale\PaySystem;

  class CloudpaymentHandler2 {
    public static function OnAdminContextMenuShowHandler_button(&$items) {

      $two_stage_payment = false;

      if($GLOBALS['APPLICATION']->GetCurPage() == '/bitrix/admin/sale_order_view.php' || $GLOBALS['APPLICATION']->GetCurPage() == '/bitrix/admin/sale_order_edit.php') {
        if(array_key_exists('ID', $_REQUEST) && $_REQUEST['ID'] > 0 && \Bitrix\Main\Loader::includeModule('sale')) {

          $order = \Bitrix\Sale\Order::load($_REQUEST['ID']);
          $propertyCollection = $order->getPropertyCollection();

          $two_stage_payment = true;

          $TYPE = $order->isPaid() ? 'Y' : "N";

          $paymentCollection = $order->getPaymentCollection();
          foreach($paymentCollection as $payment) {
            $psName = $payment->getPaymentSystemName();
            $psId = $payment->getPaymentSystemId();
            $ps = $payment->getPaySystem();
          }

          if($ps):
            if($TYPE == 'N' && $ps->getField("ACTION_FILE") == 'cloudpayment') {
              if($psId) {
                $db_ptype = CSalePaySystemAction::GetList($arOrder = Array(), Array(
                  "ACTIVE" => "Y",
                  "PAY_SYSTEM_ID" => $psId
                ));
                while($ptype = $db_ptype->Fetch()) {
                  $CLOUD_PARAMS = unserialize($ptype['PARAMS']);
                  if($CLOUD_PARAMS['TYPE_SYSTEM']['VALUE'])
                    $two_stage_payment = $CLOUD_PARAMS['TYPE_SYSTEM']['VALUE'];
                }
              }

              global $DB;
              $PAY_VOUCHER_NUM = '';
              if($_GET['ID']):
                $results = $DB->Query("select `PAY_VOUCHER_NUM` from `b_sale_order` where `ID`=" . $_GET['ID']);
                if($row = $results->Fetch()) {
                  if($row['PAY_VOUCHER_NUM']) {
                    $PAY_VOUCHER_NUM = $row['PAY_VOUCHER_NUM'];
                  }
                }
              endif;

              $FirstItem = array_shift($items);
              if(!$PAY_VOUCHER_NUM) {
                $items = array_merge(array($FirstItem), array(
                  array(
                    'TEXT' => GetMessage("BUTTON_SEND_BILL"),
                    'LINK' => 'javascript:' . $GLOBALS['APPLICATION']->GetPopupLink(array(
                        'URL' => "/bitrix/admin/cloudpayment_adminbutton.php?type=send_bill&ORDER_ID={$_GET['ID']}&LID=" . $order->getSiteId(),
                        'PARAMS' => array(
                          'width' => '400',
                          'height' => '40',
                          'resize' => false,
                          'resizable' => false,
                        )
                      )),
                    'WARNING' => 'Y',
                  )
                ), $items);
              }

              foreach($items as $Key => $arItem) {
                if($arItem['LINK'] == '/bitrix/admin/sale_order_new.php?lang=ru&LID=s1') {
                  unset($items[$Key]);
                }
              }

            }
          endif;

        }

      }
    }

    function Object_to_array($data) {
      if(is_array($data) || is_object($data)) {
        $result = array();
        foreach($data as $key => $value) {
          $result[$key] = self::Object_to_array($value);
        }
        return $result;
      }
      return $data;
    }

    public function OnCloudpaymentOrderDelete($ID) {
      CModule::IncludeModule("sale");
      if(empty($ID))
        return false;
      $arFilter = Array("ID" => $ID);
      $db_sales = CSaleOrder::GetList(array("DATE_INSERT" => "ASC"), $arFilter);
      if($ar_sales = $db_sales->Fetch()) {

        $order = \Bitrix\Sale\Order::load($ID);
        $propertyCollection = $order->getPropertyCollection();
        $paymentCollection = $order->getPaymentCollection();
        foreach($paymentCollection as $payment) {
          $psName = $payment->getPaymentSystemName(); // ???????? ????????? ???????
          $psId = $payment->getPaymentSystemId();
        }

        if(!isset($psId))
          return false;
        $CLOUD_PARAMS = self::get_module_value($psId);

        if(!empty($CLOUD_PARAMS['STATUS_AU']['VALUE']))
          $STATUS_AU = $CLOUD_PARAMS['STATUS_AU']['VALUE']; else $STATUS_AU = "AU";

        if($ar_sales['STATUS_ID'] == $STATUS_AU) {
          $API_URL = 'https://api.cloudpayments.ru/payments/void';

          if($psId) {
            if($CLOUD_PARAMS['APIPASS']['VALUE'])
              $APIPASS = $CLOUD_PARAMS['APIPASS']['VALUE'];
            if($CLOUD_PARAMS['APIKEY']['VALUE'])
              $APIKEY = $CLOUD_PARAMS['APIKEY']['VALUE'];
          }

          global $DB;
          $results = $DB->Query("select `PAY_VOUCHER_NUM` from `b_sale_order` where `ID`=" . $ID);
          if($row = $results->Fetch()) {
            $ORDER_PRICE = $order->getPrice();
            if($row['PAY_VOUCHER_NUM'] and $ORDER_PRICE > 0) {

              if($curl = curl_init()) {

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $API_URL);
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($ch, CURLOPT_USERPWD, $APIPASS . ":" . $APIKEY);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, "TransactionId=" . $row['PAY_VOUCHER_NUM']);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:39.0) Gecko/20100101 Firefox/39.0');
                $data = curl_exec($ch);
                curl_close($ch);

                $data1 = json_decode($data);

              }
            }
          }
        }
      }

    }

    function get_module_value($PS_ID) {
      if(!isset($PS_ID))
        return false;

      $db_ptype = CSalePaySystemAction::GetList($arOrder = Array(), Array("ACTIVE" => "Y", "PAY_SYSTEM_ID" => $PS_ID));
      while($ptype = $db_ptype->Fetch()) {
        $CLOUD_PARAMS = unserialize($ptype['PARAMS']);
      }

      return $CLOUD_PARAMS;
    }

    function void($payment, $refundableSum, $CLOUD_PARAMS) {
      $result = new PaySystem\ServiceResult();
      $error = '';
      $request = array(
        'TransactionId' => $payment->getField('PAY_VOUCHER_NUM'),
      );

      $url = 'https://api.cloudpayments.ru/payments/void';

      if($CLOUD_PARAMS['APIPASS']['VALUE'])
        $accesskey = $CLOUD_PARAMS['APIPASS']['VALUE'];
      if($CLOUD_PARAMS['APIKEY']['VALUE'])
        $access_psw = $CLOUD_PARAMS['APIKEY']['VALUE'];

      if($accesskey && $access_psw) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $accesskey . ":" . $access_psw);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        $out = self::Object_to_array(json_decode($content));
        if($out['Success'] !== false) {
          $result->setOperationType(PaySystem\ServiceResult::MONEY_LEAVING);
        } else {
          $error .= $out['Message'];
        }

        if($error !== '') {
          $result->addError(new Error($error));
          PaySystem\ErrorLog::add(array(
            'ACTION' => 'returnPaymentRequest',
            'MESSAGE' => join("\n", $result->getErrorMessages())
          ));
        }
      }
    }

    function refund($payment, $refundableSum, $CLOUD_PARAMS) {
      $result = new PaySystem\ServiceResult();
      $error = '';
      $request = array(
        'TransactionId' => $payment->getField('PAY_VOUCHER_NUM'),
        'Amount' => number_format($refundableSum, 2, '.', ''),
      );

      $url = 'https://api.cloudpayments.ru/payments/refund';

      if($CLOUD_PARAMS['APIPASS']['VALUE'])
        $accesskey = $CLOUD_PARAMS['APIPASS']['VALUE'];
      if($CLOUD_PARAMS['APIKEY']['VALUE'])
        $access_psw = $CLOUD_PARAMS['APIKEY']['VALUE'];

      if($accesskey && $access_psw) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $accesskey . ":" . $access_psw);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        $out = self::Object_to_array(json_decode($content));
        if($out['Success'] !== false) {
          $result->setOperationType(PaySystem\ServiceResult::MONEY_LEAVING);
        } else {
          $error .= $out['Message'];
        }

        if($error !== '') {
          $result->addError(new Error($error));
          PaySystem\ErrorLog::add(array(
            'ACTION' => 'returnPaymentRequest',
            'MESSAGE' => join("\n", $result->getErrorMessages())
          ));
        }
      }
    }

    function get_transaction($tr_id, $CLOUD_PARAMS) {
      $url = 'https://api.cloudpayments.ru/payments/get';
      if($CLOUD_PARAMS['APIPASS']['VALUE'])
        $accesskey = $CLOUD_PARAMS['APIPASS']['VALUE'];
      if($CLOUD_PARAMS['APIKEY']['VALUE'])
        $access_psw = $CLOUD_PARAMS['APIKEY']['VALUE'];

      $request = array(
        'TransactionId' => $tr_id,
      );

      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
      curl_setopt($ch, CURLOPT_USERPWD, $accesskey . ":" . $access_psw);
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
      $content = curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      $curlError = curl_error($ch);
      curl_close($ch);
      $out = self::Object_to_array(json_decode($content));

      return $out;
    }

    function OnCloudpaymentOnSaleBeforeCancelOrder($ORDER_ID, $STATUS_ID) {
      if(!empty($ORDER_ID)) {
        CModule::IncludeModule("sale");
        $order = \Bitrix\Sale\Order::load($ORDER_ID);
        $paymentCollection = $order->getPaymentCollection();
        $refundableSum = $order->getPrice();
        $tmp_ps = $order->getPaymentSystemId();
        if($tmp_ps[0])
          $ps_id = $tmp_ps[0];
        if($ps_id) {
          $CLOUD_PARAMS = self::get_module_value($ps_id);
          if(!empty($CLOUD_PARAMS['STATUS_CHANCEL']['VALUE']))
            $REFUND_STATUS = $CLOUD_PARAMS['STATUS_CHANCEL']['VALUE']; else $REFUND_STATUS = "RR";

          if(!empty($CLOUD_PARAMS['STATUS_AUTHORIZE']['VALUE']))
            $AUTHORIZE_STATUS = $CLOUD_PARAMS['STATUS_AUTHORIZE']['VALUE']; else $AUTHORIZE_STATUS = "CP";

          if(!empty($CLOUD_PARAMS['STATUS_VOID']['VALUE']))
            $VOID_STATUS = $CLOUD_PARAMS['STATUS_VOID']['VALUE']; else $VOID_STATUS = "AR";

          foreach($paymentCollection as $payment) {
            if($payment->getPaySystem()->getField("ACTION_FILE") == 'cloudpayment') {

              $transaction = self::get_transaction($payment->getField('PAY_VOUCHER_NUM'), $CLOUD_PARAMS);
              if($transaction['Model']['Status'] == 'Authorized') {
                //Authorized
                self::void($payment, $refundableSum, $CLOUD_PARAMS);
              }
            }

          }
        }
      }
    }

    function OnCloudpaymentStatusUpdate($ORDER_ID, $STATUS_ID) {




      if(!empty($ORDER_ID)) {
        CModule::IncludeModule("sale");
        $order = \Bitrix\Sale\Order::load($ORDER_ID);
        $paymentCollection = $order->getPaymentCollection();
        $refundableSum = $order->getPrice();
        $tmp_ps = $order->getPaymentSystemId();
        if($tmp_ps[0])
          $ps_id = $tmp_ps[0];
        $access1 = false;

        if($ps_id) {
          $CLOUD_PARAMS = self::get_module_value($ps_id);
          if(!empty($CLOUD_PARAMS['STATUS_CHANCEL']['VALUE']))
            $REFUND_STATUS = $CLOUD_PARAMS['STATUS_CHANCEL']['VALUE']; else $REFUND_STATUS = "RR";

          if(!empty($CLOUD_PARAMS['STATUS_TWOCHECK']['VALUE']))
            $STATUS_TWOCHECK = $CLOUD_PARAMS['STATUS_TWOCHECK']['VALUE']; else $STATUS_TWOCHECK = "F";

          if(!empty($CLOUD_PARAMS['STATUS_AUTHORIZE']['VALUE']))
            $AUTHORIZE_STATUS = $CLOUD_PARAMS['STATUS_AUTHORIZE']['VALUE']; else $AUTHORIZE_STATUS = "CP";

          if(!empty($CLOUD_PARAMS['STATUS_VOID']['VALUE']))
            $VOID_STATUS = $CLOUD_PARAMS['STATUS_VOID']['VALUE']; else $VOID_STATUS = "AR";

          if($CLOUD_PARAMS['SPOSOB_RASCHETA1']['VALUE'] == 1 || $CLOUD_PARAMS['SPOSOB_RASCHETA1']['VALUE'] == 2 || $CLOUD_PARAMS['SPOSOB_RASCHETA1']['VALUE'] == 3){
            if($CLOUD_PARAMS['CHECKONLINE']['VALUE'] != 'N'){
              $access1 = true;
            }
          }

          switch($STATUS_ID) {
            case $REFUND_STATUS:

              foreach($paymentCollection as $payment) {
                if($payment->getPaySystem()->getField("ACTION_FILE") == 'cloudpayment') {

                  $transaction = self::get_transaction($payment->getField('PAY_VOUCHER_NUM'), $CLOUD_PARAMS);

                  if($transaction['Model']['Status'] != 'Authorized') {
                    if($payment->isPaid())
                      self::refund($payment, $refundableSum, $CLOUD_PARAMS);
                  }
                }

              }

              break;

            case $VOID_STATUS:

              foreach($paymentCollection as $payment) {
                if($payment->getPaySystem()->getField("ACTION_FILE") == 'cloudpayment') {

                  $transaction = self::get_transaction($payment->getField('PAY_VOUCHER_NUM'), $CLOUD_PARAMS);
                  if($transaction['Model']['Status'] == 'Authorized') {
                    //Authorized
                    self::void($payment, $refundableSum, $CLOUD_PARAMS);
                  }
                }

              }

              break;

            case $AUTHORIZE_STATUS:

              $API_URL = 'https://api.cloudpayments.ru/payments/confirm';

              if(empty($CLOUD_PARAMS['PAY_SYSTEM_ID_OUT']['VALUE'])):
                $CLOUD_PARAMS['PAY_SYSTEM_ID_OUT']['VALUE'] = 9;
              endif;

              foreach($paymentCollection as $payment) {
                if($payment->getPaySystem()->getField("ACTION_FILE") == 'cloudpayment') {
                  $ORDER_PRICE = $order->getPrice();

                  if($payment->getField('PAY_VOUCHER_NUM') && $ORDER_PRICE) {
                    if($curl = curl_init()) {
                      if($CLOUD_PARAMS['APIPASS']['VALUE'])
                        $accesskey = $CLOUD_PARAMS['APIPASS']['VALUE'];
                      if($CLOUD_PARAMS['APIKEY']['VALUE'])
                        $access_psw = $CLOUD_PARAMS['APIKEY']['VALUE'];

                      $total = 0;
                      $POINTS = 0;

                      if($payment->isPaid()):
                        $POINTS = $POINTS + $payment->getSum();
                      endif;

                      $basket = \Bitrix\Sale\Basket::loadItemsForOrder($order);
                      $basketItems = $basket->getBasketItems();

                      $items = array();
                      $sum2 = 0;
                      foreach($basketItems as $basketItem) {
                        $prD = \Bitrix\Catalog\ProductTable::getList(array(
                          'filter' => array('ID' => $basketItem->getField('PRODUCT_ID')),
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

                        $basketQuantity = $basketItem->getQuantity();
                        $PRODUCT_PRICE = number_format($basketItem->getField('PRICE'), 2, ".", '');
                        $total = $total + ($basketItem->getField('PRICE') * $basketItem->getQuantity());

                        $items[] = array(
                          'label' => $basketItem->getField('NAME'),
                          'price' => number_format($PRODUCT_PRICE, 2, ".", ''),
                          'quantity' => $basketQuantity,
                          'amount' => number_format($PRODUCT_PRICE * $basketQuantity, 2, ".", ''),
                          'vat' => $nds,
                        );

                      }

                      if($order->getDeliveryPrice() > 0 && $order->getField("DELIVERY_ID")) {
                        if($CLOUD_PARAMS['VAT_DELIVERY' . $order->getField("DELIVERY_ID")]['VALUE'])
                          $Delivery_vat = $CLOUD_PARAMS['VAT_DELIVERY' . $order->getField("DELIVERY_ID")]['VALUE']; else $Delivery_vat = NULL;

                        $total = $total + $order->getDeliveryPrice();
                        $PRODUCT_PRICE_DELIVERY = number_format($order->getDeliveryPrice(), 2, ".", '');

                        $data = array();

                        $data2 = array();

                        $items[] = array(
                          'label' => GetMessage('DELIVERY_TXT'),
                          'price' => number_format($PRODUCT_PRICE_DELIVERY, 2, ".", ''),
                          'quantity' => 1,
                          'amount' => number_format($PRODUCT_PRICE_DELIVERY, 2, ".", ''),
                          'vat' => $Delivery_vat,
                        );

                        unset($PRODUCT_PRICE_DELIVERY);
                        unset($PRODUCT_PRICE);
                      }

                      $Property = $order->getPropertyCollection()->getArray();

                      foreach($Property['properties'] as $prop) {
                        if($prop['IS_EMAIL'] == 'Y') {
                          $data2['EMAIL'] = current($prop['VALUE']);
                        }
                        if($prop['IS_PHONE'] == 'Y') {
                          $data2['PHONE'] = current($prop['VALUE']);
                        }
                      }

                      $data['cloudPayments']['customerReceipt']['Items'] = $items;
                      $data['cloudPayments']['customerReceipt']['taxationSystem'] = intval($CLOUD_PARAMS['NALOG_TYPE']['VALUE']);
                      $data['cloudPayments']['customerReceipt']['email'] = $data2['EMAIL'];
                      $data['cloudPayments']['customerReceipt']['phone'] = $data2['PHONE'];

                      if($CLOUD_PARAMS['POINTS']['VALUE'] == 'Y'):
                        $data['cloudPayments']['customerReceipt']["amounts"] = array(
                          "electronic" => number_format($total - $POINTS, 2, ".", ''),
                          "advancePayment" => 0,
                          "credit" => 0,
                          "provision" => number_format($POINTS, 2, ".", '')
                        );
                      else:
                        $data['cloudPayments']['customerReceipt']["amounts"] = array(
                          "electronic" => number_format($total - $POINTS, 2, ".", ''),
                          "advancePayment" => 0,
                          "credit" => 0,
                          "provision" => 0
                        );
                      endif;

                      $request = array(
                        'TransactionId' => $payment->getField('PAY_VOUCHER_NUM'),
                        'Amount' => number_format($ORDER_PRICE, 2, '.', ''),
                        'JsonData' => json_encode($data),
                      );

                      $ch = curl_init($API_URL);
                      curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

                      curl_setopt($ch, CURLOPT_USERPWD, $accesskey . ":" . $access_psw);
                      curl_setopt($ch, CURLOPT_URL, $API_URL);
                      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                      curl_setopt($ch, CURLOPT_POST, true);
                      curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
                      $content = curl_exec($ch);
                      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                      $curlError = curl_error($ch);
                      curl_close($ch);
                      $out = self::Object_to_array(json_decode($content));

                      if($out['Success'] !== false) {


                      }

                    }
                  }
                }

              }

              break;

            case $STATUS_TWOCHECK:
              if($access1) {
                self::send_kkt0("Income", $order, $CLOUD_PARAMS);
              }
              break;
          }
        }

      }
    }

    function send_kkt0($type, $order, $CLOUD_PARAMS) {
      \Bitrix\Main\Loader::includeModule("sale");
      \Bitrix\Main\Loader::includeModule("catalog");
      $send_Check = false;
      $propertyCollection = $order->getPropertyCollection();
      $items = array();
      $basket = \Bitrix\Sale\Basket::loadItemsForOrder($order);
      $basketItems = $basket->getBasketItems();

      $data = array();
      $items = array();
      $basketList = array();

      foreach ($basketItems as $basketItem) {
        $basketList[$basketItem->getProductId()] = $basketItem->getId();
      }

      $PAID_IDS = array();
      $DATE_PAID = '';
      $paymentCollection = $order->getPaymentCollection();
      foreach($paymentCollection as $payment):
        if($payment->isPaid()):
          $PAID_IDS[] = $payment->getField("ID");
        endif;
      endforeach;

      if($PAID_IDS[0]):
        global $DB;
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

      $OLD_BASKET = self::GetOldBasket($order->getId(), $DATE_PAID);
      $sum2 = 0;



      foreach($OLD_BASKET as $basketId => $basketItem1):
        $basketQuantity = $basketItem1['QUANTITY'];




        $siteId = 's1';
        $basket = \Bitrix\Sale\Basket::create($siteId);
        $item = $basket->createItem('catalog', $basketList[$basketId]);
        $item->setFields(array(
          'QUANTITY' => $basketQuantity,
          'PRODUCT_PROVIDER_CLASS' => '\CCatalogProductProvider',
        ));
      //  $basket->save();



        $basketItem = \Bitrix\Catalog\PriceTable::getList(array('filter' => array('=PRODUCT_ID' => $basketId)))->fetch();
        if($basketItem):
          $prD = \Bitrix\Catalog\ProductTable::getList(array('filter' => array('ID' => $basketItem['ID'])))->fetch();

          if($prD):
            if($prD['VAT_ID'] == 0):
              $nds = NULL;
            else:
              $nds = floatval($item->getField('VAT_RATE') == 0 ? 0 : $item->getField('VAT_RATE') * 100);
            endif;
          else:
            $nds = NULL;
          endif;



          $ORDER_PRICE0 = $order->getPrice();
          $PRODUCT_PRICE = number_format($basketItem['PRICE'], 2, ".", '');

          $PROP_SBR1 = $CLOUD_PARAMS['SPOSOB_RASCHETA1']['VALUE'];
          $PROP_PRDR1 = $CLOUD_PARAMS['PREDMET_RASCHETA1']['VALUE'];

          $sum2 = $sum2 + ($PRODUCT_PRICE * $basketQuantity);
          $fieldProduct = array(
            'label' => $basketItem1['NAME'],
            'price' => number_format($PRODUCT_PRICE, 2, ".", ''),
            'quantity' => $basketQuantity,
            'amount' => number_format($PRODUCT_PRICE * $basketQuantity, 2, ".", ''),
            'vat' => $nds,
            'object' => $PROP_PRDR1,
            'method' => "4",
          );

       //   self::Error7($PROP_PRDR1);die();

          // self::Error7("SELECT `VALUE` FROM `b_iblock_element_property` where `IBLOCK_PROPERTY_ID` = " . $PROP_SBR1 . " and `IBLOCK_ELEMENT_ID` = " . $basketId);die();

          global $DB;
          if(!empty($PROP_PRDR1['VALUE'])) {
            $results_sql = $DB->Query("SELECT `VALUE` FROM `b_iblock_element_property` where `IBLOCK_PROPERTY_ID` = " . $PROP_PRDR1['VALUE'] . " and `IBLOCK_ELEMENT_ID` = " . $basketId);
            if($row_sql = $results_sql->Fetch()) {
              if(!empty($row_sql['VALUE']))
                $fieldProduct['object'] = $row_sql['VALUE'];
            }
          }

          if(!empty($PROP_SBR1['VALUE'])) {
            $results_sql = $DB->Query("SELECT `VALUE` FROM `b_iblock_element_property` where `IBLOCK_PROPERTY_ID` = " . $PROP_SBR1['VALUE'] . " and `IBLOCK_ELEMENT_ID` =" . $basketId);
            if($row_sql = $results_sql->Fetch()) {
              if(!empty($row_sql['VALUE']))
                $fieldProduct['method'] = $row_sql['VALUE'];
              if($fieldProduct['method'] == 1 || $fieldProduct['method'] == 2 || $fieldProduct['method'] == 3) {
                $fieldProduct['method'] = 4;
                $send_Check = true;
              }
            }
          }


       ///   self::Error7($basketItem);die();
          $results_sql = $DB->Query("SELECT `MARKING_CODE` FROM `b_sale_store_barcode` WHERE `BASKET_ID`='" . $basketList[$basketId] . "'");
          if($row_sql = $results_sql->Fetch()) {
            if(!empty($row_sql['MARKING_CODE']))
              $fieldProduct['ProductCodeData']['CodeProductNomenclature'] = dechex ($row_sql['MARKING_CODE']);
          }




          $items[] = $fieldProduct;
          unset($fieldProduct);
        endif;
      endforeach;
      $send_Check = 1;



      if($send_Check) {
        //��������� ��������



        $KKT_PARAMS['VAT_DELIVERY' . $order->getField("DELIVERY_ID")] = $CLOUD_PARAMS['VAT_DELIVERY' . $order->getField("DELIVERY_ID")]['VALUE'];
        if($order->getDeliveryPrice() > 0 && $order->getField("DELIVERY_ID")) {
          if($KKT_PARAMS['VAT_DELIVERY' . $order->getField("DELIVERY_ID")])
            $Delivery_vat = $KKT_PARAMS['VAT_DELIVERY' . $order->getField("DELIVERY_ID")]; else $Delivery_vat = NULL;



          $PRODUCT_PRICE_DELIVERY = number_format($order->getDeliveryPrice(), 2, ".", '');
          if($CLOUD_PARAMS['PAYMENT_CURRENCY']['VALUE'] && $CLOUD_PARAMS['PAYMENT_CURRENCY']['VALUE'] != "RUB"):
          ///  $PRODUCT_PRICE_DELIVERY = get_course($PRODUCT_PRICE_DELIVERY, $CLOUD_PARAMS['PAYMENT_CURRENCY'], $CLOUD_PARAMS['COURSE_RATE']);
          endif;


          $sum2 = $sum2 + $PRODUCT_PRICE_DELIVERY;
          $items[] = array(
            'label' => GetMessage("DELIVERY_NAME1"),
            'price' => number_format($PRODUCT_PRICE_DELIVERY, 2, ".", ''),
            'quantity' => 1,
            'amount' => number_format($PRODUCT_PRICE_DELIVERY, 2, ".", ''),
            'vat' => $Delivery_vat,
            'object' => "4",
            'method' => "4",
          );
          unset($PRODUCT_PRICE_DELIVERY);
          unset($PRODUCT_PRICE);
        }



        $KKT_PARAMS['INN'] = $CLOUD_PARAMS['INN']['VALUE'];
        $KKT_PARAMS['NALOG'] = $CLOUD_PARAMS['TYPE_NALOG']['VALUE'];
        $KKT_PARAMS['APIPASS'] = $CLOUD_PARAMS['APIPASS']['VALUE'];
        $KKT_PARAMS['APIKEY'] = $CLOUD_PARAMS['APIKEY']['VALUE'];

        $data_kkt = array(
          "Type" => $type,
          "InvoiceId" => $order->getId(),
          "AccountId" => $order->getUserId(),
          "Inn" => $KKT_PARAMS['INN'],
          "CustomerReceipt" => array(
            "Items" => $items,
            "taxationSystem" => $KKT_PARAMS['NALOG'],
            "email" => $propertyCollection->getUserEmail()->getValue(),
            "phone" => $propertyCollection->getPhone()->getValue(),
            "electronic" => 0,
            "advancePayment" => $sum2,
            "credit" => 0,
            "provision" => 0,
          )
        );

        self::Error7($data_kkt);
      //die();

        $request2 = self::cur_json_encode($data_kkt);
        $str = date("d-m-Y H:i:s") . $data_kkt['Type'] . $data_kkt['InvoiceId'] . $data_kkt['AccountId'] . $data_kkt['CustomerReceipt']['email'];
        $reque = md5($str);
        $ch = curl_init('https://api.cloudpayments.ru/kkt/receipt');
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, trim($KKT_PARAMS['APIPASS']) . ":" . trim($KKT_PARAMS['APIKEY']));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "X-Request-ID:" . $reque));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request2);
        $content = curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $out1 = $this->Object_to_array(json_decode($content));
      }
    }

    function cur_json_encode($a = false)      /////OK
    {
      if(is_null($a) || is_resource($a)) {
        return 'null';
      }
      if($a === false) {
        return 'false';
      }
      if($a === true) {
        return 'true';
      }

      if(is_scalar($a)) {
        if(is_float($a)) {
          //Always use "." for floats.
          $a = str_replace(',', '.', strval($a));
        }

        // All scalars are converted to strings to avoid indeterminism.
        // PHP's "1" and 1 are equal for all PHP operators, but
        // JS's "1" and 1 are not. So if we pass "1" or 1 from the PHP backend,
        // we should get the same result in the JS frontend (string).
        // Character replacements for JSON.
        static $jsonReplaces = array(
          array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'),
          array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"')
        );

        return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';
      }

      $isList = true;

      for($i = 0, reset($a); $i < count($a); $i++, next($a)) {
        if(key($a) !== $i) {
          $isList = false;
          break;
        }
      }

      $result = array();

      if($isList) {
        foreach($a as $v) {
          $result[] = self::cur_json_encode($v);
        }

        return '[ ' . join(', ', $result) . ' ]';
      } else {
        foreach($a as $k => $v) {
          $result[] = self::cur_json_encode($k) . ': ' . self::cur_json_encode($v);
        }

        return '{ ' . join(', ', $result) . ' }';
      }
    }

    public function Error7($str) {
      $file = $_SERVER['DOCUMENT_ROOT'] . '/log_cloudpayments2020.txt';
      $current = file_get_contents($file);
      $current .= print_r($str, 1) . "\n";
      file_put_contents($file, $current);
    }

    function GetOldBasket($order_id, $DATE_PAID) {
      if($order_id && $DATE_PAID):
        global $DB;

        $results_sql = $DB->Query("SELECT * FROM `b_sale_order_change` WHERE `DATE_MODIFY`<='" . $DATE_PAID . "' and `ORDER_ID`=" . $order_id . " and `TYPE`='SHIPMENT_ITEM_BASKET_ADDED'");
        while($row_sql = $results_sql->Fetch()):
          $tmp = unserialize($row_sql['DATA']);
          $FROM_ITEMS[$tmp['PRODUCT_ID']]['QUANTITY'] = $tmp['QUANTITY'];
          $FROM_ITEMS[$tmp['PRODUCT_ID']]['NAME'] = $tmp['NAME'];
        endwhile;

        $results_sql = $DB->Query("SELECT * FROM `b_sale_order_change` WHERE `DATE_MODIFY`<='" . $DATE_PAID . "' and `ORDER_ID`=" . $order_id . " and (`TYPE`='BASKET_QUANTITY_CHANGED' OR `TYPE`='BASKET_ADDED')");
        while($row_sql = $results_sql->Fetch()):
          $tmp = unserialize($row_sql['DATA']);
          if($FROM_ITEMS[$tmp['PRODUCT_ID']])
            $FROM_ITEMS[$tmp['PRODUCT_ID']]['QUANTITY'] = $tmp['QUANTITY'];
          $FROM_ITEMS[$tmp['PRODUCT_ID']]['NAME'] = $tmp['NAME'];
        endwhile;

        return $FROM_ITEMS;
      else:
        return false;
      endif;
    }

  }

