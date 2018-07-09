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

use Bitrix\Catalog\CCatalogProduct;

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
  public function Error($str)
  {
        $file = $_SERVER['DOCUMENT_ROOT'].'/bitrix/php_interface/include/sale_payment/cloudpayment/log.txt';
        $current = file_get_contents($file);
        $current .= $str."\n";
        file_put_contents($file, $current);
  }
  
  public function isFullPricePaid($order,$paymentCollection, $request)
  {      
        $sum=0;
      /*  foreach ($paymentCollection as $payment):
            $sum += $payment->getSum()
        endforeach;  */  
        self::Error(roundEx($paymentCollection->getPaidSum(),2).'=='.roundEx($order->getPrice(),2));

        if (roundEx($paymentCollection->getPaidSum(),2)==roundEx($order->getPrice(),2)) return true;
        else return false;
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
      if ($request->get('InvoiceId'))
      {
	    $order=\Bitrix\Sale\Order::load($request->get('InvoiceId'));
	    foreach($order->getPaymentCollection() as $payment){
			$l[]=$payment->getField("ID");
		}
//		$l=$order->getPaymentSystemId();
	    return current($l);
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
            
            $paymentCollection = $order->getPaymentCollection();
            if($paymentCollection->isPaid())  ////1111
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

                                                                                               ///1111
            if ($order->getField("STATUS_ID")==$STATUS_AU || $paymentCollection->isPaid() || $order->isCanceled() || $order->getField("STATUS_ID")==$STATUS_CHANCEL)
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
    
    function addError2($txt)
    {
          $file = $_SERVER['DOCUMENT_ROOT'].'/bitrix/php_interface/include/sale_payment/cloudpayment/log2.txt';
          $current = file_get_contents($file);
          $current .= $txt."\n";
          file_put_contents($file, $current);
    }
    
    
  function cur_json_encode($a=false)      /////OK
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
  
  
function GetOldBasket($order_id,$DATE_PAID)
{    
      if ($order_id && $DATE_PAID):
            global $DB;
                            //return "SELECT * FROM `b_sale_order_change` WHERE `DATE_MODIFY`>'".$DATE_PAID."' and `ORDER_ID`=".$order_id;   
            
            $results_sql = $DB->Query("SELECT * FROM `b_sale_order_change` WHERE `DATE_MODIFY`<='".$DATE_PAID."' and `ORDER_ID`=".$order_id." and `TYPE`='SHIPMENT_ITEM_BASKET_ADDED'");
            while ($row_sql = $results_sql->Fetch()):
               $tmp=unserialize($row_sql['DATA']);
               $FROM_ITEMS[$tmp['PRODUCT_ID']]['QUANTITY']=$tmp['QUANTITY'];
               $FROM_ITEMS[$tmp['PRODUCT_ID']]['NAME']=$tmp['NAME'];
            endwhile;
            
            $results_sql = $DB->Query("SELECT * FROM `b_sale_order_change` WHERE `DATE_MODIFY`<='".$DATE_PAID."' and `ORDER_ID`=".$order_id." and (`TYPE`='BASKET_QUANTITY_CHANGED' OR `TYPE`='BASKET_ADDED')");
            while ($row_sql = $results_sql->Fetch()):
               $tmp=unserialize($row_sql['DATA']);
               if ($FROM_ITEMS[$tmp['PRODUCT_ID']])
                $FROM_ITEMS[$tmp['PRODUCT_ID']]['QUANTITY']=$tmp['QUANTITY'];
                $FROM_ITEMS[$tmp['PRODUCT_ID']]['NAME']=$tmp['NAME'];
            endwhile;
            
            return $FROM_ITEMS;
      else:
            return false;      
      endif;
}
  
    
    function send_kkt($type,$order,$payment_1)
    {                                          
        \Bitrix\Main\Loader::includeModule("sale");
        \Bitrix\Main\Loader::includeModule("catalog");
        
        $propertyCollection = $order->getPropertyCollection();
        $items=array();
        $basket = \Bitrix\Sale\Basket::loadItemsForOrder($order);
        $basketItems = $basket->getBasketItems();
        $data=array();
        $items=array();


        $PAID_IDS='';
        $DATE_PAID='';
        $paymentCollection = $order->getPaymentCollection();
        foreach ($paymentCollection as $payment):
            if ($payment->isPaid()):
                $PAID_IDS[]=$payment->getField("ID");
            endif;
        endforeach;
        
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



        $OLD_BASKET=self::GetOldBasket($order->getId(),$DATE_PAID);


        foreach ($OLD_BASKET as $basketId=>$basketItem1):
            $basketQuantity=$basketItem1['QUANTITY'];

            $basket = \Bitrix\Sale\Basket::create();
            $item = $basket->createItem('catalog', $basketId);
            $item->setFields(array(
                'QUANTITY' => $basketQuantity,
                'PRODUCT_PROVIDER_CLASS' => '\CCatalogProductProvider',
            ));

            $basketItem=\Bitrix\Catalog\PriceTable::getList(array('filter'=>array('ID'=>$basketId)))->fetch();
            if($basketItem):
                  $prD=\Bitrix\Catalog\ProductTable::getList(array('filter'=>array('ID'=>$basketId)))->fetch();
                  if($prD):
                      if($prD['VAT_ID']==0):
                          $nds=null;
                      else:
                          $nds=floatval($item->getField('VAT_RATE')==0 ? 0 : $item->getField('VAT_RATE')*100);
                      endif;
                  else:
                      $nds=null;
                  endif;
                  
                 $ORDER_PRICE0=$order->getPrice(); 
                 $PRODUCT_PRICE=$item->getField('PRICE'); 
      
                  $items[]=array(
                          'label'=>$basketItem1['NAME'],
                          'price'=>number_format($PRODUCT_PRICE,2,".",''),
                          'quantity'=>$basketQuantity,
                          'amount'=>number_format(floatval($PRODUCT_PRICE*$basketQuantity),2,".",''),
                          'vat'=>$nds,
                          'ean13'=>null);
            endif;
        endforeach;

        //Добавляем доставку
        $KKT_PARAMS['VAT_DELIVERY'.$order->getField("DELIVERY_ID")]=$this->getBusinessValue($payment_1, 'VAT_DELIVERY'.$order->getField("DELIVERY_ID"));
        if ($order->getDeliveryPrice() > 0 && $order->getField("DELIVERY_ID")) 
        {
            if ($KKT_PARAMS['VAT_DELIVERY'.$order->getField("DELIVERY_ID")]) $Delivery_vat=$KKT_PARAMS['VAT_DELIVERY'.$order->getField("DELIVERY_ID")];
            else $Delivery_vat=null;
            

            $PRODUCT_PRICE_DELIVERY=$order->getDeliveryPrice(); 

                 
            $items[] = array(
                'label' => 'Доставка',
                'price' => number_format($order->getDeliveryPrice(), 2, ".", ''),
                'quantity' => 1,
                'amount' => number_format($PRODUCT_PRICE_DELIVERY, 2, ".", ''),
                'vat' => $Delivery_vat,  
                'ean13' => null
            );
            unset($PRODUCT_PRICE_DELIVERY);
            unset($PRODUCT_PRICE);
       }
              
              $KKT_PARAMS['INN']=$this->getBusinessValue($payment_1, 'INN');
              $KKT_PARAMS['NALOG']=$this->getBusinessValue($payment_1, 'TYPE_NALOG');
              $KKT_PARAMS['APIPASS']=$this->getBusinessValue($payment_1, 'APIPASS');
              $KKT_PARAMS['APIKEY']=$this->getBusinessValue($payment_1, 'APIKEY');
               
                
              $data_kkt=array(
                "Type"=>$type,
                "InvoiceId"=>$order->getId(),
                "AccountId"=>$order->getUserId(),
                "Inn"=>$KKT_PARAMS['INN'],
                "CustomerReceipt"=>array(
                   "Items"=>$items,
                   "taxationSystem"=>$KKT_PARAMS['NALOG'],
                    "email"=>$propertyCollection->getUserEmail()->getValue(),
                    "phone"=>$propertyCollection->getPhone()->getValue(),
                )
              );
            
              $this->Error('send kkt');
              $this->Error(print_r($data_kkt,1));
              $request2=self::cur_json_encode($data_kkt);
              $str=date("d-m-Y H:i:s").$data_kkt['Type'].$data_kkt['InvoiceId'].$data_kkt['AccountId'].$data_kkt['CustomerReceipt']['email'];
              $reque=md5($str);
              $ch = curl_init('https://api.cloudpayments.ru/kkt/receipt');
              curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
              curl_setopt($ch,CURLOPT_USERPWD,trim($KKT_PARAMS['APIPASS']).":".trim($KKT_PARAMS['APIKEY']));
              curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
              curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json","X-Request-ID:".$reque));
              curl_setopt($ch, CURLOPT_POST, true);
              curl_setopt($ch, CURLOPT_POSTFIELDS, $request2);
              $content = curl_exec($ch);
              $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
              $curlError = curl_error($ch);
              curl_close($ch);


              $out1=$this->Object_to_array(json_decode($content));

 
    }
    
    
    private function processconfirmAction(Payment $payment, Request $request)
    {     
        $result = new PaySystem\ServiceResult();
        $data = $this->extractDataFromRequest($request);
        $data1=$data['DATA'];
        $data1=$this->Object_to_array(json_decode(mb_convert_encoding($data1, 'utf-8', mb_detect_encoding($data1))));
        

        $order=\Bitrix\Sale\Order::load($data['INVOICE_ID']);
        $paymentCollection = $order->getPaymentCollection();
        
        

        $check_sum=false;
      //  $l = $paymentCollection[0];;
        foreach ($paymentCollection as $payment):
            if (roundEx($payment->getSum(), 2)==roundEx($request->get('Amount'), 2))
            {
                  $l=$payment;
                  $check_sum=true;
            }
        endforeach;

        if ($check_sum):
              $dat=new \Bitrix\Main\Type\DateTime();
              $STATUS_PAY=$this->getBusinessValue($payment, 'STATUS_PAY');
              $STATUS_PARTIAL_PAY=$this->getBusinessValue($payment, 'STATUS_PARTIAL_PAY');
              if (empty($STATUS_PAY)) $STATUS_PAY='P';
              
              $l->setField('PAID','Y');
             // $order->setField('STATUS_ID',$STATUS_PAY);   
              
              $l->setField('DATE_PAID',$dat);
              $l->setField('PAY_VOUCHER_NUM',$request->get('TransactionId'));
              $l->setField('PAY_VOUCHER_DATE',$dat);
              $l->setField('COMMENTS','Y');
              $order->save();
              /** Заново получаем данные по оплате и заказам, т.к. одна из оплат прошла **/
              $order=\Bitrix\Sale\Order::load($data['INVOICE_ID']);
              $paymentCollection = $order->getPaymentCollection();
              
              if ($this->isFullPricePaid($order,$paymentCollection, $request)):
                    $order->setField('STATUS_ID',$STATUS_PAY); 
              else:
                    $order->setField('STATUS_ID',$STATUS_PARTIAL_PAY);      
              endif;
              

              $order->save();
              $data['CODE'] = 0;
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
        $data1=$data['DATA'];
        $data1=$this->Object_to_array(json_decode(mb_convert_encoding($data1, 'utf-8', mb_detect_encoding($data1))));
        

        $order=\Bitrix\Sale\Order::load($data['INVOICE_ID']);
        $paymentCollection = $order->getPaymentCollection();

        /** Если уже была хоть одна оплата - отправляем чек - возврата **/ 
        $IS_PAID=false;
        foreach ($paymentCollection as $payment_):
            if ($payment_->isPaid()):
                $IS_PAID=true;
            endif;
        endforeach;

        
        if ($IS_PAID):
              self::send_kkt("IncomeReturn",$order,$payment);
        endif;

        $TYPE_SYSTEM=$this->getBusinessValue($payment, 'TYPE_SYSTEM');    //двухстадийка - 1 одностадийка - 0
        $STATUS_AU=$this->getBusinessValue($payment, 'STATUS_AU');    //двухстадийка - 1 одностадийка - 0

        $STATUS_PAY=$this->getBusinessValue($payment, 'STATUS_PAY');
        $STATUS_PARTIAL_PAY=$this->getBusinessValue($payment, 'STATUS_PARTIAL_PAY');
        if (empty($STATUS_PAY)) $STATUS_PAY='P';
        $check_sum=false;
      //
      //  $l = $paymentCollection[0];
        foreach ($paymentCollection as $payment):
           // if ($payment->getField("ID")==$data1['PAY_SYSTEM_ID'] && roundEx($payment->getSum(), 2)==roundEx($request->get('Amount'), 2))
           if (roundEx($payment->getSum(), 2)==roundEx($request->get('Amount'), 2))
           {
                  $l=$payment;
                  $check_sum=true;
           } 
        endforeach;
        if ($check_sum):
              $dat=new \Bitrix\Main\Type\DateTime();
              if (!$TYPE_SYSTEM)  
              {
                    $l->setField('PAID','Y');                         
                    $l->setField('DATE_PAID',$dat);
                    $l->setField('PAY_VOUCHER_NUM',$request->get('TransactionId'));
                    $l->setField('PAY_VOUCHER_DATE',$dat);
                    $l->setField('COMMENTS','Y');   
                    $order->save();
                    /** Заново получаем данные по оплате и заказам, т.к. одна из оплат прошла **/
                    $order=\Bitrix\Sale\Order::load($data['INVOICE_ID']);
                    $paymentCollection = $order->getPaymentCollection();
                    
                    if ($this->isFullPricePaid($order,$paymentCollection, $request)):
                          $order->setField('STATUS_ID',$STATUS_PAY); 
                    else:
                          $order->setField('STATUS_ID',$STATUS_PARTIAL_PAY);      
                    endif;
              }
              else                                       
              {
                    $order->setField('STATUS_ID',$STATUS_AU);      //статус авторизован для двухстадийки
                    $l->setField('DATE_PAID',$dat);
                    $l->setField('PAY_VOUCHER_NUM',$request->get('TransactionId'));
                    $l->setField('PAY_VOUCHER_DATE',$dat);
                    $l->setField('COMMENTS','Y');
                    $order->save();
              }
              $order->save();
              $data['CODE'] = 0;
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
        $order=\Bitrix\Sale\Order::load($data['INVOICE_ID']);
        $paymentCollection = $order->getPaymentCollection();
        
        $l = $paymentCollection[0];
        foreach ($paymentCollection as $payment_):
            if ($payment_->getField("PAY_VOUCHER_NUM")==$request->get('PaymentTransactionId')) $l=$payment_;
        endforeach;    



        
        
        $l->setPaid("N");
        $TYPE_SYSTEM=$this->getBusinessValue($payment, 'TYPE_SYSTEM');    //двухстадийка - 1 одностадийка - 0
        $STATUS_CHANCEL=$this->getBusinessValue($payment, 'STATUS_CHANCEL');
        
      //  $l = $paymentCollection[0];
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
			'INVOICE_ID' =>  $request->get('InvoiceId'),
      'DATA' =>  $request->get('Data')
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