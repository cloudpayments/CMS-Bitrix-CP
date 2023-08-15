<?
	
	use Bitrix\Sale\PaySystem;
	
	class CloudpaymentHandler2 {
		public static function OnAdminContextMenuShowHandler_button(&$items) {
			CModule::IncludeModule("sale");
			
			if (
				$GLOBALS['APPLICATION']->GetCurPage() !== '/bitrix/admin/sale_order_view.php' and
				$GLOBALS['APPLICATION']->GetCurPage() !== '/bitrix/admin/sale_order_edit.php'
			) return;
			
			$order = \Bitrix\Sale\Order::load($_REQUEST['ID']);
			
			if ($order->isPaid())
				return;
			
			$paymentCollection = $order->getPaymentCollection();
			
			foreach ($paymentCollection as $payment)
				if ($payment->getPaymentSystemName() == 'CloudPayments' and !$payment->getField('PAY_VOUCHER_NUM'))
				{
					$items = array_merge(array(array_shift($items)), array(
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
			
			foreach ($items as $Key => $arItem)
				if ($arItem['LINK'] == '/bitrix/admin/sale_order_new.php?lang=ru&LID=s1')
					unset($items[$Key]);
		}
		
		public static function Object_to_array($data) {
			if(is_array($data) || is_object($data)) {
				$result = array();
				foreach($data as $key => $value) {
					$result[$key] = self::Object_to_array($value);
				}
				return $result;
			}
			return $data;
		}
		
		public static function OnCloudpaymentOrderDelete($ID) {
			CModule::IncludeModule("sale");
			
			if(empty($ID))
				return false;
			
			$order = \Bitrix\Sale\Order::load($ID);
			$paymentCollection = $order->getPaymentCollection();
			foreach($paymentCollection as $payment)
				if ($payment->getPaymentSystemName() == 'CloudPayments') {
					$psId = $payment->getPaymentSystemId();
					
					$CLOUD_PARAMS = self::get_module_value($psId);
					
					$STATUS_AU = $CLOUD_PARAMS['STATUS_AU']['VALUE'] ?: "AU";
					
					if(
						$payment->getField('PAY_VOUCHER_NUM') and
						$order->getField('STATUS_ID') == $STATUS_AU and
						$order->getPrice() > 0
					) {
						self::curl_request('https://api.cloudpayments.ru/payments/void', $CLOUD_PARAMS,
							array("TransactionId=" . $payment->getField('PAY_VOUCHER_NUM')));
					}
				}
		}
		
		public static function get_module_value($PS_ID) {
			if(empty($PS_ID))
				return false;
			
			$db_ptype = CSalePaySystemAction::GetList($arOrder = Array(), Array("ACTIVE" => "Y", "PAY_SYSTEM_ID" => $PS_ID));
			while($ptype = $db_ptype->Fetch()) {
				$CLOUD_PARAMS = unserialize($ptype['PARAMS']);
			}
			
			return $CLOUD_PARAMS;
		}
		
		public static function void($payment, $refundableSum, $CLOUD_PARAMS) {
			$result = new PaySystem\ServiceResult();
			
			$out = self::curl_request('https://api.cloudpayments.ru/payments/void', $CLOUD_PARAMS,
				array("TransactionId=" . $payment->getField('PAY_VOUCHER_NUM')));
			
			if(!$out['Success']) {
				$result->addError(new Error($out['Message']));
				PaySystem\ErrorLog::add(array(
					'ACTION' => 'returnPaymentRequest',
					'MESSAGE' => join("\n", $result->getErrorMessages())
				));
			} else
				$result->setOperationType(PaySystem\ServiceResult::MONEY_LEAVING);
		}
		
		public static function refund($payment, $refundableSum, $CLOUD_PARAMS) {
			$result = new PaySystem\ServiceResult();
			
			$out = self::curl_request(
				'https://api.cloudpayments.ru/payments/refund',
				$CLOUD_PARAMS,
				array(
					'TransactionId' => $payment->getField('PAY_VOUCHER_NUM'),
					'Amount' => number_format($refundableSum, 2, '.', ''),
				));
			
			if (!$out['Success']) {
				$result->addError(new Error($out['Message']));
				PaySystem\ErrorLog::add(array(
					'ACTION' => 'returnPaymentRequest',
					'MESSAGE' => join("\n", $result->getErrorMessages())
				));
			} else $result->setOperationType(PaySystem\ServiceResult::MONEY_LEAVING);
			
		}
		
		public static function get_transaction($tr_id, $CLOUD_PARAMS) {
			return self::curl_request(
				'https://api.cloudpayments.ru/payments/get',
				$CLOUD_PARAMS,
				array(
					'TransactionId' => $tr_id,
				));
		}
		
		public static function OnCloudpaymentOnSaleBeforeCancelOrder($ORDER_ID, $STATUS_ID) {
			CModule::IncludeModule("sale");
			
			if(empty($ORDER_ID))
				return;
			
			$order = \Bitrix\Sale\Order::load($ORDER_ID);
			$paymentCollection = $order->getPaymentCollection();
			
			foreach($paymentCollection as $payment)
				if($payment->getPaySystem()->getField("ACTION_FILE") == 'cloudpayment') {
					$psId = $payment->getPaymentSystemId();
					
					$CLOUD_PARAMS = self::get_module_value($psId);
					
					$transaction = self::get_transaction($payment->getField('PAY_VOUCHER_NUM'), $CLOUD_PARAMS);
					
					if($transaction['Model']['Status'] == 'Authorized')
						self::void($payment, $order->getPrice(), $CLOUD_PARAMS);
				}
		}
		
		public static function OnCloudpaymentStatusUpdate($ORDER_ID, $STATUS_ID) {
			if (empty($ORDER_ID))
				return;
			
			CModule::IncludeModule("sale");
			
			$order = \Bitrix\Sale\Order::load($ORDER_ID);
			$paymentCollection = $order->getPaymentCollection();
			$refundableSum = $order->getPrice();
			
			foreach($paymentCollection as $payment) {
				if($payment->getPaySystem()->getField("ACTION_FILE") == 'cloudpayment') {
					$psId = $payment->getPaymentSystemId();
					
					$CLOUD_PARAMS = self::get_module_value($psId);
					
					$REFUND_STATUS = $CLOUD_PARAMS['STATUS_CHANCEL']['VALUE'] ?: "RR";
					$STATUS_TWOCHECK = $CLOUD_PARAMS['STATUS_TWOCHECK']['VALUE'] ?: "F";
					$AUTHORIZE_STATUS = $CLOUD_PARAMS['STATUS_AUTHORIZE']['VALUE'] ?: "CP";
					$VOID_STATUS = $CLOUD_PARAMS['STATUS_VOID']['VALUE'] ?: "AR";
					
					switch($STATUS_ID) {
						case $REFUND_STATUS:
							
							$transaction = self::get_transaction($payment->getField('PAY_VOUCHER_NUM'), $CLOUD_PARAMS);
							
							if($transaction['Model']['Status'] != 'Authorized')
								if($payment->isPaid())
									self::refund($payment, $refundableSum, $CLOUD_PARAMS);
							
							break;
						
						case $VOID_STATUS:
							
							$transaction = self::get_transaction($payment->getField('PAY_VOUCHER_NUM'), $CLOUD_PARAMS);
							
							if($transaction['Model']['Status'] == 'Authorized')
								self::void($payment, $refundableSum, $CLOUD_PARAMS);
							
							break;
						
						case $AUTHORIZE_STATUS:
							
							$ORDER_PRICE = $order->getPrice();
							
							if($payment->getField('PAY_VOUCHER_NUM') and $ORDER_PRICE) {
								
								$basket = $order->getBasket();
								$basketItems = $basket->getBasketItems();
								
								$items = array();
								foreach($basketItems as $basketItem) {
									$item = array(
										'label' => $basketItem->getField('NAME'),
										'price' => number_format($basketItem->getField('PRICE'), 2, ".", ''),
										'quantity' => $basketItem->getQuantity(),
										'vat' => $basketItem->getField('VAT_RATE') * 100,
									);
									
									$item['amount'] = number_format($item['price'] * $item['quantity'], 2, ".", '');
									$items[] = $item;
									
								}
								
								if($order->getDeliveryPrice() > 0 and $order->getField("DELIVERY_ID")) {
									$items[] = array(
										'label' => GetMessage('DELIVERY_TXT'),
										'price' => number_format($order->getDeliveryPrice(), 2, ".", ''),
										'quantity' => 1,
										'amount' => number_format($order->getDeliveryPrice(), 2, ".", ''),
										'vat' => $CLOUD_PARAMS['VAT_DELIVERY' . $order->getField("DELIVERY_ID")]['VALUE'] ?: NULL,
									);
								}
								
								$propertyCollection = $order->getPropertyCollection();
								
								$data=array(
									'cloudPayments' => array(
										'customerReceipt' => array(
											'Items' => $items,
											'taxationSystem'=>intval($CLOUD_PARAMS['NALOG_TYPE']['VALUE']),
											'email'=>$propertyCollection->getUserEmail()->getValue(),
											'phone'=>$propertyCollection->getPhone()->getValue(),
											'amounts'=>array(
												"electronic" => $payment->isPaid() ? 0 : $payment->getSum(),
												"advancePayment" => 0,
												"credit" => 0,
												"provision" => $CLOUD_PARAMS['POINTS']['VALUE'] == 'Y' ? ($payment->isPaid() ? 0 : $payment->getSum()) : 0
											)
										)
									)
								);
								
								self::curl_request(
									'https://api.cloudpayments.ru/payments/confirm',
									$CLOUD_PARAMS,
									array(
										'TransactionId' => $payment->getField('PAY_VOUCHER_NUM'),
										'Amount' => number_format($ORDER_PRICE, 2, '.', ''),
										'JsonData' => json_encode($data),
									));
							}
							
							break;
						
						case $STATUS_TWOCHECK:
							if(
								$CLOUD_PARAMS['SPOSOB_RASCHETA1']['VALUE'] == 1 or
								$CLOUD_PARAMS['SPOSOB_RASCHETA1']['VALUE'] == 2 or
								$CLOUD_PARAMS['SPOSOB_RASCHETA1']['VALUE'] == 3 and
								$CLOUD_PARAMS['CHECKONLINE']['VALUE'] != 'N'
							) self::send_kkt0("Income", $order, $CLOUD_PARAMS);
							break;
					}
				}
			}
			
		}
		
		public static function send_kkt0($type, $order, $CLOUD_PARAMS) {
			
			\Bitrix\Main\Loader::includeModule("sale");
			\Bitrix\Main\Loader::includeModule("catalog");
			
			$propertyCollection = $order->getPropertyCollection();
			$paymentCollection = $order->getPaymentCollection();
			
			$basket = $order->getBasket();
			$basketItems = $basket->getBasketItems();
			
			foreach($paymentCollection as $payment)
				if($payment->getPaySystem()->getField("ACTION_FILE") == 'cloudpayment') {
					foreach ($basket->getBasketItems() as $basketItem){
						$fieldProduct = array(
							'label' => $basketItem->getField('NAME'),
							'price' => number_format($basketItem->getField('PRICE'), 2, ".", ''),
							'quantity' => $basketItem->getQuantity(),
							'vat' => $basketItem->getField('VAT_RATE') * 100,
							'object' => $CLOUD_PARAMS['PREDMET_RASCHETA1']['VALUE'] ?: 0,
							'method' => $CLOUD_PARAMS['SPOSOB_RASCHETA1']['VALUE'] ?: 4,
						);
						
						$fieldProduct['amount'] = number_format($fieldProduct['price'] * $fieldProduct['quantity'],2,'.','');
						
						global $DB;
						$results_sql = $DB->Query("SELECT `MARKING_CODE` FROM `b_sale_store_barcode` WHERE `BASKET_ID`='" . $basketItem->getId() . "'");
						if($row_sql = $results_sql->Fetch()) {
							if(!empty($row_sql['MARKING_CODE']))
								$fieldProduct['ProductCodeData']['CodeProductNomenclature'] = dechex ($row_sql['MARKING_CODE']);
						}
						
						$items[] = $fieldProduct;
					}
				}
			
			if($order->getDeliveryPrice() > 0 and $order->getField("DELIVERY_ID")) {
				$items[] = array(
					'label' => GetMessage("DELIVERY_NAME"),
					'price' => number_format($order->getDeliveryPrice(), 2, ".", ''),
					'quantity' => 1,
					'amount' => number_format($order->getDeliveryPrice(), 2, ".", ''),
					'vat' => $CLOUD_PARAMS['VAT_DELIVERY' . $order->getField("DELIVERY_ID")]['VALUE'] ?: NULL,
					'object' => $CLOUD_PARAMS['PREDMET_RASCHETA1']['VALUE'] ?: "4",
					'method' => "4",
				);
			}
			
			$data_kkt = array(
				"Type" => $type,
				"InvoiceId" => $order->getId(),
				"AccountId" => $order->getUserId(),
				"Inn" => $CLOUD_PARAMS['INN']['VALUE'],
				"CustomerReceipt" => array(
					"Items" => $items,
					"taxationSystem" => $CLOUD_PARAMS['TYPE_NALOG']['VALUE'],
					"email" => $propertyCollection->getUserEmail()->getValue(),
					"phone" => $propertyCollection->getPhone()->getValue(),
					"electronic" => 0,
					"advancePayment" => $payment->getSum(),
					"credit" => 0,
					"provision" => 0,
				)
			);
			
			$request2 = self::curl_json_encode($data_kkt);
			$str = date("d-m-Y H:i:s") . $data_kkt['Type'] . $data_kkt['InvoiceId'] . $data_kkt['AccountId'] . $data_kkt['CustomerReceipt']['email'];
			$reque = md5($str);
			
			self::curl_request(
				'https://api.cloudpayments.ru/kkt/receipt',
				$CLOUD_PARAMS,
				$request2,
				array("Content-Type: application/json", "X-Request-ID:" . $reque)
			);
		}
		
		public static function curl_json_encode($a = false)      /////OK
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
				if(is_float($a))
					$a = str_replace(',', '.', strval($a));
				
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
					$result[] = self::curl_json_encode($v);
				}
				
				return '[ ' . join(', ', $result) . ' ]';
			} else {
				foreach($a as $k => $v) {
					$result[] = self::curl_json_encode($k) . ': ' . self::curl_json_encode($v);
				}
				
				return '{ ' . join(', ', $result) . ' }';
			}
		}
		
		public static function curl_request($url, $params, $body, $extraHeader = array()) {
			$httpClient = new \Bitrix\Main\Web\HttpClient();
			$httpClient->setAuthorization(trim($params['APIPASS']['VALUE']), trim($params['APIKEY']['VALUE']));
			
			if (!empty($extraHeader))
				$httpClient->setHeaders($extraHeader);
			
			$content = $httpClient->post($url, $body);
			
			return self::Object_to_array(json_decode($content));
		}
		
	}

