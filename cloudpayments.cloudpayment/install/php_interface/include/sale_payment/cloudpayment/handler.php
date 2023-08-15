<?php

namespace Sale\Handlers\PaySystem;

use Bitrix\Catalog\CCatalogProduct;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Request;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Sale\BusinessValue;
use Bitrix\Sale\Payment;
use Bitrix\Sale\PaySystem;

Loc::loadMessages(__FILE__);

/**
 * Class YandexHandler
 * @package Sale\Handlers\PaySystem
 */
class CloudpaymentHandler extends PaySystem\ServiceHandler implements PaySystem\IHold, PaySystem\IRefund
{
	/**
	 * @param Payment $payment
	 * @param Request|null $request
	 * @return PaySystem\ServiceResult
	 */
	
	public function initiatePay(Payment $payment, Request $request = NULL)
	{
		$params = array(
			'URL' => $this->getUrl($payment, 'pay'),
			'PS_MODE' => $this->service->getField('PS_MODE'),
			'BX_PAYSYSTEM_CODE' => $this->service->getField('ID'),
			'PAYMENT' => $payment,
			'ORDER' => $payment->getOrder(),
			'BASKET' => $payment->getOrder()->getBasket()
		);
		
		$this->setExtraParams($params);
		
		return $this->showTemplate($payment, "template");
	}
	
	/**
	 * @return array
	 */
	public static function getIndicativeFields()
	{
		return array('BX_HANDLER' => 'CLOUDPAYMENT');
	}
	
	/**
	 * @param Request $request
	 * @param $paySystemId
	 * @return bool
	 */
	
	static public function isMyResponse(Request $request, $paySystemId)
	{
		$id = $request->get('BX_PAYSYSTEM_CODE');
		$InvoiceId = $request->get('InvoiceId');
		global $DB;
		$results_sql = $DB->Query("SELECT `PAY_SYSTEM_ID` FROM `b_sale_order` where `ID`= " . $InvoiceId);
		if ($row_sql = $results_sql->Fetch()):
			if($row_sql['PAY_SYSTEM_ID'] == $paySystemId) return true;
		endif;
		
		return false;
	}
	
	public function confirm(Payment $payment)
	{
		$result = new PaySystem\ServiceResult();
		$httpClient = new HttpClient();
		
		$url = $this->getUrl($payment, 'confirm');
		$requestDT = date('c');
		
		$request = array(
			'orderId' => $this->getBusinessValue($payment, 'PAYMENT_ID'),
			'amount' => $this->getBusinessValue($payment, 'PAYMENT_SHOULD_PAY'),
			'currency' => $this->getBusinessValue($payment, 'PAYMENT_CURRENCY'),
			'requestDT' => $requestDT
		);
		$responseString = $httpClient->post($url, $request);
		
		if ($responseString !== false) {
			$element = $this->parseXmlResponse('confirmPaymentResponse', $responseString);
			$status = (int)$element->getAttribute('status');
			if ($status == 0)
				$result->setOperationType(PaySystem\ServiceResult::MONEY_COMING); else
				$result->addError(new Error('Error on try to confirm payment. Status: ' . $status));
		} else {
			$result->addError(new Error("Error sending request. URL=" . $url . " PARAMS=" . join(' ', $request)));
		}
		
		if (!$result->isSuccess()) {
			PaySystem\ErrorLog::add(array(
				'ACTION' => 'confirmPayment',
				'MESSAGE' => join('\n', $result->getErrorMessages())
			));
		}
		
		return $result;
	}
	
	static protected function isMyResponseExtended(Request $request, $paySystemId)
	{
		$id = $request->get('BX_PAYSYSTEM_CODE');
		return $id == $paySystemId;
	}
	
	/**
	 * @param Payment $payment
	 * @param int $refundableSum
	 * @return PaySystem\ServiceResult
	 */
	public function refund(Payment $payment, $refundableSum)
	{
		$result = new PaySystem\ServiceResult();
		$error = '';
		
		$request = array(
			'TransactionId' => $payment->getField('PAY_VOUCHER_NUM'),
			'Amount' => number_format($refundableSum, 2, '.', '')
		);
		
		$url = $this->getUrl($payment, 'return');
		
		$accesskey = trim($this->getBusinessValue($payment, 'APIPASS'));
		$access_psw = trim($this->getBusinessValue($payment, 'APIKEY'));
		
		$httpClient = new HttpClient();
		$httpClient->setAuthorization(trim($accesskey), trim($access_psw));
		$content = $httpClient->post($url, $request);
		
		$out = $this->Object_to_array(json_decode($content));
		if ($out['Success'] !== false) {
			$result->setOperationType(PaySystem\ServiceResult::MONEY_LEAVING);
			
		} else {
			$error .= $out['Message'];
		}
		
		if ($error !== '') {
			$result->addError(new Error($error));
			PaySystem\ErrorLog::add(array(
				'ACTION' => 'returnPaymentRequest',
				'MESSAGE' => join("\n", $result->getErrorMessages())
			));
		}
		
		return $result;
	}
	
	static function Object_to_array($data)
	{
		if (is_array($data) || is_object($data)) {
			$result = array();
			foreach ($data as $key => $value) {
				$result[$key] = self::Object_to_array($value);
			}
			return $result;
		}
		return $data;
	}
	
	private function CheckHMac($APIPASS)
	{
		
		if (!empty($APIPASS)) {
			$headers = $this->detallheaders();
			$message = file_get_contents('php://input');
			$s = hash_hmac('sha256', $message, $APIPASS, true);
			$hmac = base64_encode($s);
			if (!array_key_exists('Content-HMAC', $headers) && !array_key_exists('Content-Hmac', $headers))
				return false;
			if ((array_key_exists('Content-HMAC', $headers) && $headers['Content-HMAC'] == $hmac) || (array_key_exists('Content-Hmac', $headers) && $headers['Content-Hmac'] == $hmac))
				return true;
		}
		return false;
	}
	
	private function detallheaders()
	{
		if (!is_array($_SERVER)) {
			return array();
		}
		$headers = array();
		foreach ($_SERVER as $name => $value) {
			if (substr($name, 0, 5) == 'HTTP_') {
				$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
			}
		}
		return $headers;
	}
	
	/**
	 * @param Payment $payment
	 * @param Request $request
	 * @return bool
	 */
	
	private function isCorrectOrderID(Payment $payment, Request $request)
	{
		$sum = $request->get('InvoiceId');
		$paymentSum = $this->getBusinessValue($payment, 'PAYMENT_ID');
		
		return roundEx($paymentSum, 2) == roundEx($sum, 2);
	}
	
	public function Error($str)
	{
		$file = $_SERVER['DOCUMENT_ROOT'] . '/log_cloudpayments2020.txt';
		$current = file_get_contents($file);
		$current .= print_r($str, 1) . "\n";
		file_put_contents($file, $current);
	}
	
	public function isFullPricePaid($order, $paymentCollection, $request)
	{
		$sum = 0;
		
		if (roundEx($paymentCollection->getPaidSum(), 2) == roundEx($order->getPrice(), 2))
			return true; else return false;
	}
	
	private function isCorrectSum(Payment $payment, Request $request)
	{
		$sum = $request->get('Amount');
		$paymentSum = $this->getBusinessValue($payment, 'PAYMENT_SHOULD_PAY');
		
		return roundEx($paymentSum, 2) == roundEx($sum, 2);
	}
	
	/**
	 * @param PaySystem\ServiceResult $result
	 * @param Request $request
	 * @return mixed
	 */
	public function sendResponse(PaySystem\ServiceResult $result, Request $request)
	{
		global $APPLICATION;
		$APPLICATION->RestartBuffer();
		$data = $result->getData();
		$res['code'] = $data['CODE'];
		echo json_encode($res);
		die();
	}
	
	/**
	 * @param Request $request
	 * @return mixed
	 */
	public function getPaymentIdFromRequest(Request $request)
	{
		if ($request->get('InvoiceId')) {
			
			$INVOICE_ID = $request->get('InvoiceId');
			
			global $DB;
			$results_sql = $DB->Query("SELECT `ID` FROM `b_sale_order` where `ID`= " . $INVOICE_ID);
			if ($row_sql = $results_sql->Fetch()):
				
				$order = \Bitrix\Sale\Order::load($INVOICE_ID); //YY
				foreach ($order->getPaymentCollection() as $payment) {
					$l[] = $payment->getField("ID");
				}
				return current($l);
			endif;
		}
	}
	
	/**
	 * @param Payment $payment
	 * @param Request $request
	 * @return PaySystem\ServiceResult
	 */
	private function processCheckAction(Payment $payment, Request $request)
	{
		
		$result = new PaySystem\ServiceResult();
		$data = $this->extractDataFromRequest($request);
		$data['CODE'] = 0;
		$accesskey = trim($this->getBusinessValue($payment, 'APIKEY'));
		if ($this->CheckHMac($accesskey))
			return $result;
		
		$order = $payment->getOrder();
		
		$STATUS_AU = $this->getBusinessValue($payment, 'STATUS_AU');
		$STATUS_CHANCEL = $this->getBusinessValue($payment, 'STATUS_CHANCEL');
		
		if (!$this->isCorrectOrderID($payment, $request)):
			$data['CODE'] = 10;
			$errorMessage = 'Incorrect order ID';
			
			$result->addError(new Error($errorMessage));
			PaySystem\ErrorLog::add(array(
				'ACTION' => 'checkOrderResponse',
				'MESSAGE' => $errorMessage
			));
			$result->setData($data);
			return $result;
		endif;
		
		if ($order->getCurrency() != $this->getBusinessValue($payment, 'PAYMENT_CURRENCY')):
			$data['CODE'] = 13;
			$result->setData($data);
			return $result;
		endif;
		
		if (roundEx($payment->getSum(), 2) != roundEx($request->get('Amount'), 2)):
			$data['CODE'] = 11;
			$result->setData($data);
			return $result;
		endif;
		
		if ($payment->isPaid()):
			$data['CODE'] = 13;
			
			$result->setData($data);
			return $result;
		endif;
		
		if (
			$order->getField("STATUS_ID") == $STATUS_AU or
			$payment->isPaid() or
			$order->isCanceled() or
			$order->getField("STATUS_ID") == $STATUS_CHANCEL
		) {
			$data['CODE'] = 13;
		}
		
		$result->setData($data);
		
		return $result;
	}
	
	private function processFailAction(Payment $payment, Request $request)
	{
		$result = new PaySystem\ServiceResult();
		$data = $this->extractDataFromRequest($request);
		$data['CODE'] = 0;
		$result->setData($data);
		return $result;
		
	}
	
	function addError2($txt)
	{
		$file = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/include/sale_payment/cloudpayment/log.txt';
		$current = file_get_contents($file);
		$current .= $txt . "\n";
		file_put_contents($file, $current);
	}
	
	static function cur_json_encode($a = false)      /////OK
	{
		if (is_null($a) || is_resource($a)) {
			return 'null';
		}
		if ($a === false) {
			return 'false';
		}
		if ($a === true) {
			return 'true';
		}
		
		if (is_scalar($a)) {
			if (is_float($a)) {
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
		
		for ($i = 0, reset($a); $i < count($a); $i++, next($a)) {
			if (key($a) !== $i) {
				$isList = false;
				break;
			}
		}
		
		$result = array();
		
		if ($isList) {
			foreach ($a as $v) {
				$result[] = self::cur_json_encode($v);
			}
			
			return '[ ' . join(', ', $result) . ' ]';
		} else {
			foreach ($a as $k => $v) {
				$result[] = self::cur_json_encode($k) . ': ' . self::cur_json_encode($v);
			}
			
			return '{ ' . join(', ', $result) . ' }';
		}
	}
	
	static function GetOldBasket($order_id, $DATE_PAID)
	{
		if ($order_id and $DATE_PAID):
			global $DB;
			
			$results_sql = $DB->Query("SELECT * FROM `b_sale_order_change` WHERE `DATE_MODIFY`<='" . $DATE_PAID . "' and `ORDER_ID`=" . $order_id . " and `TYPE`='SHIPMENT_ITEM_BASKET_ADDED'");
			while ($row_sql = $results_sql->Fetch()):
				$tmp = unserialize($row_sql['DATA']);
				$FROM_ITEMS[$tmp['PRODUCT_ID']]['QUANTITY'] = $tmp['QUANTITY'];
				$FROM_ITEMS[$tmp['PRODUCT_ID']]['NAME'] = $tmp['NAME'];
			endwhile;
			
			$results_sql = $DB->Query("SELECT * FROM `b_sale_order_change` WHERE `DATE_MODIFY`<='" . $DATE_PAID . "' and `ORDER_ID`=" . $order_id . " and (`TYPE`='BASKET_QUANTITY_CHANGED' OR `TYPE`='BASKET_ADDED')");
			while ($row_sql = $results_sql->Fetch()):
				$tmp = unserialize($row_sql['DATA']);
				if ($FROM_ITEMS[$tmp['PRODUCT_ID']])
					$FROM_ITEMS[$tmp['PRODUCT_ID']]['QUANTITY'] = $tmp['QUANTITY'];
				$FROM_ITEMS[$tmp['PRODUCT_ID']]['NAME'] = $tmp['NAME'];
			endwhile;
			
			return $FROM_ITEMS;
		else:
			return false;
		endif;
	}
	
	function send_kkt($type, $order, $payment)
	{
		$propertyCollection = $order->getPropertyCollection();
		
		$basket = $order->getBasket();
		$items = array();
		
		foreach ($basket->getBasketItems() as $basketItem):
			$fieldProduct = array(
				'label' => $basketItem->getField('NAME'),
				'price' => number_format($basketItem->getField('PRICE'), 2, ".", ''),
				'quantity' => $basketItem->getQuantity(),
				'vat' => $basketItem->getField('VAT_RATE') * 100,
				'object' => $this->getBusinessValue($payment, 'PREDMET_RASCHETA1') ?: 0,
				'method' => $this->getBusinessValue($payment, 'SPOSOB_RASCHETA1') ?: 0
			);
			
			$fieldProduct['amount'] = number_format($fieldProduct['price'] * $fieldProduct['quantity'], 2, ".", '');
			
			global $DB;
			$results_sql = $DB->Query("SELECT `MARKING_CODE` FROM `b_sale_store_barcode` WHERE `BASKET_ID`='" . $basketItem->getId() . "'");
			if ($row_sql = $results_sql->Fetch()) {
				if (!empty($row_sql['MARKING_CODE']))
					$fieldProduct['ProductCodeData']['CodeProductNomenclature'] = $row_sql['MARKING_CODE'];
			}
			$items[] = $fieldProduct;
		endforeach;
		
		//Добавляем доставку
		
		if ($order->getDeliveryPrice() > 0 and $order->getField("DELIVERY_ID")) {
			$items[] = array(
				'label' => 'Доставка',
				'price' => number_format($order->getDeliveryPrice(), 2, ".", ''),
				'quantity' => 1,
				'amount' => number_format($order->getDeliveryPrice(), 2, ".", ''),
				'vat' => $this->getBusinessValue($payment, 'VAT_DELIVERY' . $order->getField("DELIVERY_ID")) ?: null,
				'object' => $this->getBusinessValue($payment, 'PREDMET_RASCHETA1') ?: 0,
				'method' => "4",
			);
		}
		
		$data_kkt = array(
			"Type" => $type,
			"InvoiceId" => $order->getId(),
			"AccountId" => $order->getUserId(),
			"Inn" => $this->getBusinessValue($payment, 'INN'),
			"CustomerReceipt" => array(
				"Items" => $items,
				"taxationSystem" => $this->getBusinessValue($payment, 'TYPE_NALOG'),
				"email" => $propertyCollection->getUserEmail()->getValue(),
				"phone" => $propertyCollection->getPhone()->getValue(),
				"electronic" => $payment->getSum(),
				"advancePayment" => 0,
				"credit" => 0,
				"provision" => 0,
			)
		);
		
		self::Error($data_kkt);
		
		$str = date("d-m-Y H:i:s") . $data_kkt['Type'] . $data_kkt['InvoiceId'] . $data_kkt['AccountId'] . $data_kkt['CustomerReceipt']['email'];
		
		$httpClient = new HttpClient();
		$httpClient->setAuthorization(trim($this->getBusinessValue($payment, 'APIPASS')), trim($this->getBusinessValue($payment, 'APIKEY')));
		$httpClient->setHeaders(array("Content-Type: application/json", "X-Request-ID:" . md5($str)));
		$content = $httpClient->post('https://api.cloudpayments.ru/kkt/receipt', self::cur_json_encode($data_kkt));
		
		self::Error($content);
	}
	
	private function processconfirmAction(Payment $payment, Request $request)
	{
		$result = new PaySystem\ServiceResult();
		$data = $this->extractDataFromRequest($request);
		$data['CODE'] = 0;
		$order = $payment->getOrder(); //Y
		
		if (roundEx($payment->getSum(), 2) == roundEx($request->get('Amount'), 2)):
			$dat = new \Bitrix\Main\Type\DateTime();
			$STATUS_PAY = $this->getBusinessValue($payment, 'STATUS_PAY');
			$STATUS_PARTIAL_PAY = $this->getBusinessValue($payment, 'STATUS_PARTIAL_PAY');
			if (empty($STATUS_PAY))
				$STATUS_PAY = 'P';
			
			$payment->setField('PAID', 'Y');
			$payment->setField('DATE_PAID', $dat);
			$payment->setField('PAY_VOUCHER_NUM', $request->get('TransactionId'));
			$payment->setField('PAY_VOUCHER_DATE', $dat);
			$payment->setField('COMMENTS', 'Y');
			$order->save();
			
			$this->isFullPricePaid($order, $order->getPaymentCollection(), $request)
				? $order->setField('STATUS_ID', $STATUS_PAY)
				: $order->setField('STATUS_ID', $STATUS_PARTIAL_PAY);
			
			$order->save();
			$result->setData($data);
		else:
			$data['CODE'] = 11;
			$result->setData($data);
		endif;
		
		return $result;
	}
	
	private function processSuccessAction(Payment $payment, Request $request)
	{
		$result = new PaySystem\ServiceResult();
		$data = $this->extractDataFromRequest($request);
		$data['CODE'] = 0;
		$order = $payment->getOrder();
		/** Если уже была хоть одна оплата - отправляем чек - возврата **/
		if ($payment->isPaid()):
			self::send_kkt("IncomeReturn", $order, $payment);
		endif;
		
		$TYPE_SYSTEM = $this->getBusinessValue($payment, 'TYPE_SYSTEM');    //двухстадийка - 1 одностадийка - 0
		
		$STATUS_AU = $this->getBusinessValue($payment, 'STATUS_AU');    //двухстадийка - 1 одностадийка - 0
		$STATUS_PAY = $this->getBusinessValue($payment, 'STATUS_PAY');
		
		$STATUS_PARTIAL_PAY = $this->getBusinessValue($payment, 'STATUS_PARTIAL_PAY');
		if (empty($STATUS_PAY))
			$STATUS_PAY = 'P';
		
		if (roundEx($payment->getSum(), 2) == roundEx($request->get('Amount'), 2)):
			$dat = new \Bitrix\Main\Type\DateTime();
			if (!$TYPE_SYSTEM) {
				$payment->setField('PAID', 'Y');
				$payment->setField('DATE_PAID', $dat);
				$payment->setField('PAY_VOUCHER_NUM', $request->get('TransactionId'));
				$payment->setField('PAY_VOUCHER_DATE', $dat);
				$payment->setField('COMMENTS', 'Y');
				$order->save();
				$this->isFullPricePaid($order, $order->getPaymentCollection(), $request)
					? $order->setField('STATUS_ID', $STATUS_PAY)
					: $order->setField('STATUS_ID', $STATUS_PARTIAL_PAY);
			} else {
				$order->setField('STATUS_ID', $STATUS_AU);      //статус авторизован для двухстадийки
				$payment->setField('DATE_PAID', $dat);
				$payment->setField('PAY_VOUCHER_NUM', $request->get('TransactionId'));
				$payment->setField('PAY_VOUCHER_DATE', $dat);
				$payment->setField('COMMENTS', 'Y');
				$order->save();
			}
			$order->save();
			$result->setData($data);
		else:
			$data['CODE'] = 11;
			$result->setData($data);
		endif;
		
		return $result;
	}
	
	private function processRefundAction(Payment $payment, Request $request)
	{
		$result = new PaySystem\ServiceResult();
		$data = $this->extractDataFromRequest($request);
		$data['CODE'] = 0;
		
		$order = $payment->getOrder(); // YY
		
		if ($payment->getField("PAY_VOUCHER_NUM") == $request->get('PaymentTransactionId'))
		{
			$payment->setPaid("N");
			
			$STATUS_CHANCEL = $this->getBusinessValue($payment, 'STATUS_CHANCEL');
			
			$payment->setField('PAID', 'N');
			$order->setField('STATUS_ID', $STATUS_CHANCEL);
			
			$order->save();
			$result->setData($data);
		}
		return $result;
	}
	
	/**
	 * @param Request $request
	 * @return array
	 */
	private function extractDataFromRequest(Request $request)
	{
		return array(
			'HEAD' => $request->get('action') . 'Response',
			'INVOICE_ID' => $request->get('InvoiceId'),
			'DATA' => $request->get('Data')
		);
	}
	
	/**
	 * @param Payment $payment
	 * @param Request $request
	 * @return PaySystem\ServiceResult
	 */
	private function processCancelAction(Payment $payment, Request $request)
	{
		$result = new PaySystem\ServiceResult();
		$data = $this->extractDataFromRequest($request);
		$data['CODE'] = 0;
		$result->setOperationType(PaySystem\ServiceResult::MONEY_LEAVING);
		$result->setData($data);
		return $result;
	}
	
	/**
	 * @return mixed
	 */
	protected function getUrlList()
	{
		return array(
			'confirm' => array(
				self::ACTIVE_URL => 'https://api.cloudpayments.ru/payments/confirm ',
			),
			'cancel' => array(
				self::ACTIVE_URL => 'https://api.cloudpayments.ru/payments/void',
			
			),
			'return' => array(
				self::ACTIVE_URL => 'https://api.cloudpayments.ru/payments/refund',
			),
			'get' => array(
				self::ACTIVE_URL => ' https://api.cloudpayments.ru/payments/find',
			)
		);
	}
	
	/**
	 * @param Payment $payment
	 * @param Request $request
	 * @return PaySystem\ServiceResult
	 */
	public function processRequest(Payment $payment, Request $request)
	{
		$result = new PaySystem\ServiceResult();
		
		$accesskey = trim($this->getBusinessValue($payment, 'APIKEY'));
		
		$action = $request->get("action");
		$this->Error($action);
		
		if (!$this->CheckHMac($accesskey))
			return $result;
		
		switch ($action) {
			case 'check':
				return $this->processCheckAction($payment, $request);
			case 'fail':
				return $this->processFailAction($payment, $request);
			case 'pay':
				return $this->processSuccessAction($payment, $request);
			case 'refund':
				return $this->processrefundAction($payment, $request);
			case 'confirm':
				return $this->processconfirmAction($payment, $request);
			case 'cancel':
				return $this->processCancelAction($payment, $request);
			default:
				$data = $this->extractDataFromRequest($request);
				$data['TECH_MESSAGE'] = 'Unknown action: ' . $action;
				$result->setData($data);
				return $result;
		}
	}
	
	/**
	 * @param Payment $payment
	 * @return bool
	 */
	protected function isTestMode(Payment $payment = NULL)
	{
		return ($this->getBusinessValue($payment, 'PS_IS_TEST') == 'Y');
	}
	
	/**
	 * @param Payment $payment
	 * @return PaySystem\ServiceResult
	 */
	public function cancel(Payment $payment)
	{
		$result = new PaySystem\ServiceResult();
		
		return $result;
	}
	
	/**
	 * @return array
	 */
	public function getCurrencyList()
	{
		return array('RUB');
	}
	
	/**
	 * @return bool
	 */
	public function isTuned()
	{
		$personTypeList = PaySystem\Manager::getPersonTypeIdList($this->service->getField('ID'));
		$personTypeId = array_shift($personTypeList);
		$shopId = BusinessValue::get('YANDEX_SHOP_ID', $this->service->getConsumerName(), $personTypeId);
		
		return !empty($shopId);
	}
	
}