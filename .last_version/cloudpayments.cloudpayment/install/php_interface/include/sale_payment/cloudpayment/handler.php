<?php

namespace Sale\Handlers\PaySystem;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Request;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Sale\BusinessValue;
use Bitrix\Sale\PaySystem;
use Bitrix\Sale\Payment;

Loc::loadMessages(__FILE__);

/**
 * Class YandexHandler
 * @package Sale\Handlers\PaySystem
 */
class CloudpaymentHandler extends PaySystem\ServiceHandler implements  PaySystem\IHold, PaySystem\IRefund
{
	/**
	 * @param Payment $payment
	 * @param Request|null $request
	 * @return PaySystem\ServiceResult
	 */

  
	public function initiatePay(Payment $payment, Request $request = null)
	{
		$params = array(
			'URL' => $this->getUrl($payment, 'pay'),
			'PS_MODE' => $this->service->getField('PS_MODE'),
			'BX_PAYSYSTEM_CODE' => $this->service->getField('ID')
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

    static  public function isMyResponse(Request $request, $paySystemId)
    {
        return true;
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

        if ($responseString !== false)
        {
            $element = $this->parseXmlResponse('confirmPaymentResponse', $responseString);
            $status = (int)$element->getAttribute('status');
            if ($status == 0)
                $result->setOperationType(PaySystem\ServiceResult::MONEY_COMING);
            else
                $result->addError(new Error('Error on try to confirm payment. Status: '.$status));
        }
        else
        {
            $result->addError(new Error("Error sending request. URL=".$url." PARAMS=".join(' ', $request)));
        }

        if (!$result->isSuccess())
        {
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

        $request=array(
            'TransactionId'=>$payment->getField('PAY_VOUCHER_NUM'),
            'Amount'=>number_format($refundableSum, 2, '.', ''),
          //  'JsonData'=>'',
        );



        $url = $this->getUrl($payment, 'return');

        $accesskey=trim($this->getBusinessValue($payment, 'APIPASS'));
        $access_psw=trim($this->getBusinessValue($payment, 'APIKEY'));
      	$ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch,CURLOPT_USERPWD,$accesskey . ":" . $access_psw);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
    	  $content = curl_exec($ch);
	      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    		$curlError = curl_error($ch);
    		curl_close($ch);
        $out=$this->Object_to_array(json_decode($content));
        if ($out['Success'] !== false)
        {
              $result->setOperationType(PaySystem\ServiceResult::MONEY_LEAVING);


        }
        else
        {
            $error .= $out['Message'];
        }
        
    		if ($error !== '')
    		{
    			$result->addError(new Error($error));
    			PaySystem\ErrorLog::add(array(
    				'ACTION' => 'returnPaymentRequest',
    				'MESSAGE' => join("\n", $result->getErrorMessages())
    			));
    		}

		return $result;
	}

    function Object_to_array($data)
    {
        if (is_array($data) || is_object($data))
        {
            $result = array();
            foreach ($data as $key => $value)
            {
                $result[$key] = self::Object_to_array($value);
            }
            return $result;
        }
        return $data;
    }
    private function CheckHMac($APIPASS){

        $headers = $this->detallheaders();
        if (!((!isset($headers['Content-HMAC'])) and (!isset($headers['Content-Hmac'])))) {
            $message = file_get_contents('php://input');
            $s = hash_hmac('sha256', $message, $APIPASS, true);
            $hmac = base64_encode($s);
            return (!array_key_exists('Content-HMAC',$headers) && !array_key_exists('Content-Hmac',$headers) || (array_key_exists('Content-HMAC',$headers) && $headers['Content-HMAC'] != $hmac) || (array_key_exists('Content-Hmac',$headers) && $headers['Content-Hmac'] != $hmac));

        }


    }
    private function detallheaders(){
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

	private function isCorrectSum(Payment $payment, Request $request)
	{
		$sum = $request->get('orderSumAmount');
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
		$res['code']=$data['CODE'];
        echo json_encode($res);
		die();
	}

	/**
	 * @param Request $request
	 * @return mixed
	 */
	public function getPaymentIdFromRequest(Request $request)
	{
	    $order=\Bitrix\Sale\Order::load($request->get('InvoiceId'));
	    foreach($order->getPaymentCollection() as $payment){
			$l[]=$payment->getField("ID");
		}
//		$l=$order->getPaymentSystemId();
	    return current($l);
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

        $accesskey=trim($this->getBusinessValue($payment, 'APIPSW'));
        if($this->CheckHMac($accesskey))
        {
            if ($this->isCorrectSum($payment, $request))
            {
                $data['CODE'] = 0;
            }
            else
            {
                $data['CODE'] = 11;
                $errorMessage = 'Incorrect payment sum';

                $result->addError(new Error($errorMessage));
                PaySystem\ErrorLog::add(array(
                    'ACTION' => 'checkOrderResponse',
                    'MESSAGE' => $errorMessage
                ));
            }
            
            ////13
            
            $order=\Bitrix\Sale\Order::load($data['INVOICE_ID']);
            $STATUS_AU = $this->getBusinessValue($payment, 'STATUS_AU');
            $STATUS_CHANCEL= $this->getBusinessValue($payment, 'STATUS_CHANCEL');
            
            if($this->isCorrectOrderID($payment, $request))
            {
                $data['CODE'] = 0;
            }else
            {
                $data['CODE'] = 10;
                $errorMessage = 'Incorrect order ID';

                $result->addError(new Error($errorMessage));
                PaySystem\ErrorLog::add(array(
                    'ACTION' => 'checkOrderResponse',
                    'MESSAGE' => $errorMessage
                ));
            }
            
            
            $orderID=$request->get('InvoiceId');
            $order=\Bitrix\Sale\Order::load($orderID);
            if($order->isPaid())
            {
                $data['CODE'] = 13;
                $errorMessage = 'Order already paid';

                $result->addError(new Error($errorMessage));
                PaySystem\ErrorLog::add(array(
                    'ACTION' => 'checkOrderResponse',
                    'MESSAGE' => $errorMessage
                ));
            }else{
                $data['CODE'] = 0;
            }

             
            if ($order->getField("STATUS_ID")==$STATUS_AU || $order->isPaid() || $order->isCanceled() || $order->getField("STATUS_ID")==$STATUS_CHANCEL)
            {
               $data['CODE'] = 13;
            }
            
            $result->setData($data);
        }else{
            $errorMessage='ERROR HMAC RECORDS';
            $result->addError(new Error($errorMessage));
            PaySystem\ErrorLog::add(array(
                'ACTION' => 'checkOrderResponse',
                'MESSAGE' => $errorMessage
            ));
        }

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
    
    
    private function processconfirmAction(Payment $payment, Request $request)
    {     
        $result = new PaySystem\ServiceResult();
        $data = $this->extractDataFromRequest($request);


        $order=\Bitrix\Sale\Order::load($data['INVOICE_ID']);
        $paymentCollection = $order->getPaymentCollection();


        $l = $paymentCollection[0];
        $dat=new \Bitrix\Main\Type\DateTime();
        $STATUS_PAY=$this->getBusinessValue($payment, 'STATUS_PAY');
        if (empty($STATUS_PAY)) $STATUS_PAY='P';
        $l->setField('PAID','Y');
        $order->setField('STATUS_ID',$STATUS_PAY);   
        
        $l->setField('DATE_PAID',$dat);
        $l->setField('PAY_VOUCHER_NUM',$request->get('TransactionId'));
        $l->setField('PAY_VOUCHER_DATE',$dat);
        $l->setField('COMMENTS','Y');
 
        $order->save();
        $data['CODE'] = 0;
        $result->setData($data);
        return $result;
    
    }
    
    
    private function processSuccessAction(Payment $payment, Request $request)
    {
        $result = new PaySystem\ServiceResult();
        $data = $this->extractDataFromRequest($request);


        $order=\Bitrix\Sale\Order::load($data['INVOICE_ID']);
        $paymentCollection = $order->getPaymentCollection();

        $TYPE_SYSTEM=$this->getBusinessValue($payment, 'TYPE_SYSTEM');    //двухстадийка - 1 одностадийка - 0
        $STATUS_AU=$this->getBusinessValue($payment, 'STATUS_AU');    //двухстадийка - 1 одностадийка - 0

        $STATUS_PAY=$this->getBusinessValue($payment, 'STATUS_PAY');
        if (empty($STATUS_PAY)) $STATUS_PAY='P';
        $l = $paymentCollection[0];
        $dat=new \Bitrix\Main\Type\DateTime();
        if (!$TYPE_SYSTEM)  
        {
              $l->setField('PAID','Y');
              $order->setField('STATUS_ID',$STATUS_PAY); 
        }
        else $order->setField('STATUS_ID',$STATUS_AU);      //статус авторизован для двухстадийки
        
        $l->setField('DATE_PAID',$dat);
        $l->setField('PAY_VOUCHER_NUM',$request->get('TransactionId'));
        $l->setField('PAY_VOUCHER_DATE',$dat);
        $l->setField('COMMENTS','Y');
 
        $order->save();
        $data['CODE'] = 0;
        $result->setData($data);
        return $result;
    }
    
    private function processRefundAction(Payment $payment, Request $request)
    {
        $result = new PaySystem\ServiceResult();
        $data = $this->extractDataFromRequest($request);


        $order=\Bitrix\Sale\Order::load($data['INVOICE_ID']);
        $paymentCollection = $order->getPaymentCollection();
        $onePayment = $paymentCollection[0];
        $onePayment->setPaid("N");
        $TYPE_SYSTEM=$this->getBusinessValue($payment, 'TYPE_SYSTEM');    //двухстадийка - 1 одностадийка - 0
        $STATUS_CHANCEL=$this->getBusinessValue($payment, 'STATUS_CHANCEL');
        
        $l = $paymentCollection[0];
        $dat=new \Bitrix\Main\Type\DateTime();
        $l->setField('PAID','N');
        $order->setField('STATUS_ID',$STATUS_CHANCEL);
        
        $order->save();
        $data['CODE'] = 0;
        $result->setData($data);
        
        return $result;
    }

	/**
	 * @param Request $request
	 * @return array
	 */
	private function extractDataFromRequest(Request $request)
	{
		return array(
			'HEAD' => $request->get('action').'Response',
			'INVOICE_ID' =>  $request->get('InvoiceId')
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
            'get'=>array(
                self::ACTIVE_URL =>' https://api.cloudpayments.ru/payments/find',
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

	    $action= $request->get("action");
        if ($action == 'check')
        {
            return $this->processCheckAction($payment, $request);
        }
        else if ($action == 'fail')
        {
            return $this->processFailAction($payment, $request);
        }
        else if ($action == 'pay')
        {
            return $this->processSuccessAction($payment, $request);
        }
        else if ($action == 'refund')
        {
            return $this->processrefundAction($payment, $request);
        }
        else if ($action == 'confirm')
        {
            return $this->processconfirmAction($payment, $request);
        }
        else{

            $data = $this->extractDataFromRequest($request);
            $data['TECH_MESSAGE'] = 'Unknown action: '.$action;
            $result->setData($data);
            $result->addError(new Error('Unknown action: '.$action.'. Request='.join(', ', $request->toArray())));
        }

		return $result;
	}

	/**
	 * @param Payment $payment
	 * @return bool
	 */
	protected function isTestMode(Payment $payment = null)
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