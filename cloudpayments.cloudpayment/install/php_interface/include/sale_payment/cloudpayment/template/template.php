<?
	
	/**
	 * @var array $params
	 * */
	
	use Bitrix\Main\Localization\Loc;
	
	Loc::loadMessages(__FILE__);
	
	$description = Loc::getMessage("VBCH_CLPAY_MM_DESC", array(
		"#ORDER_ID#" => $params['PAYMENT_ID'],
		"#SITE_NAME#" => COption::GetOptionString("main", "server_name", "") ?: SITE_SERVER_NAME,
		"#DATE#" => $params['PAYMENT_DATE_INSERT']
	));
	
	$widget_f = $params['TYPE_SYSTEM'] ? 'auth' : 'charge';
	$lang_widget = $params['WIDGET_LANG'] ?: 'ru-RU';
	$skin = $params['WIDGET_SKIN'] ?: 'classic';
	
	$order = $params['ORDER'];
	$payment = $params['PAYMENT'];
	$basket = $params['BASKET'];
	
	if (!empty($order)) {
		
		$params["ORDER_ID"] = $order->getId();
		
		if (!$status_id = $order->getField("STATUS_ID"))
			die(Loc::getMessage("ERROR_ORDER_ID"));
		
		if (
			$params['CHECKONLINE'] != 'N' and
			$status_id != $params['STATUS_AU'] and
			$status_id != $params['STATUS_AUTHORIZE'] and
			!$payment->isPaid()
		) {
			$data = array();
			$items = array();
			
			foreach ($basket->getBasketItems() as $basketItem) {
				
				$item = array(
					'label' => $basketItem->getField('NAME'),
					'price' => number_format($basketItem->getField('PRICE'), 2, ".", ''),
					'quantity' => $basketItem->getQuantity(),
					'vat' => $basketItem->getField('VAT_RATE') * 100,
					"object" => $params['PREDMET_RASCHETA1'] ?: 0,
					"method" => $params['SPOSOB_RASCHETA1'] ?: 0,
				);
				
				$item['amount'] = number_format($item['price'] * $item['quantity'], 2, ".", '');
				
				foreach ($basketItem->getPropertyCollection() as $property) {
					if ($property->getField('CODE') === 'SPIC')
						$item["spic"] = $property->getField('VALUE');
					if ($property->getField('CODE') === 'PACKAGE_CODE')
						$item["packageCode"] = $property->getField('VALUE');
				}
				
				$items[] = $item;
			}
			
			if (
				$order->getDeliveryPrice() > 0 and
				$order->getField("DELIVERY_ID")
			) {
				
				$item_d = array(
					'label' => GetMessage('DELIVERY_TXT'),
					'price' => number_format($order->getDeliveryPrice(), 2, ".", ''),
					'quantity' => 1,
					'amount' => number_format($order->getDeliveryPrice(), 2, ".", ''),
					'vat' => $params['VAT_DELIVERY' . $order->getField("DELIVERY_ID")] ?: NULL,
					'object' => "4",
					'method' => $params['SPOSOB_RASCHETA1'] ?: 0
				);
				
				if (!empty($params['SPIC']))
					$item_d['spic'] = $params['SPIC'];
				if (!empty($params['PACKAGE_CODE']))
					$item_d['packageCode'] = $params['PACKAGE_CODE'];
				
				$items[] = $item_d;
			}
			
			$data['PAY_SYSTEM_ID'] = $params['PAY_ID'];
			
			$data['cloudPayments']['customerReceipt'] = array(
				'Items' => $items,
				'taxationSystem' => $params['TYPE_NALOG'],
				'calculationPlace' => $params['calculationPlace'],
				'email' => $params['PAYMENT_BUYER_EMAIL'],
				'phone' => $params['PAYMENT_BUYER_PHONE'],
				'amounts' => array(
					"electronic" => $payment->getSum(),
					"advancePayment" => 0,
					"credit" => 0,
					"provision" => 0,
				),
			);
			
			if (!empty($params['SPIC']) and !empty($params['PACKAGE_CODE']))
				$data['cloudPayments']['customerReceipt']['AdditionalReceiptInfos'] = ["?? ????? ??????????? ????? ?? 1% cashback"]; // ??? ????????? ????????
			
		}
		
		if (
			$status_id != $params['STATUS_AU'] and
			$status_id != $params['STATUS_AUTHORIZE'] and
			!$payment->isPaid() and
			!$order->isCanceled() and
			$status_id != $params['STATUS_CHANCEL']
		):
			?>
      <div>
        <button class="cloudpay_button"
                id="payButton"><?= Loc::getMessage('SALE_HANDLERS_PAY_SYSTEM_CLOUDPAYMENTS_BUTTON_PAID') ?></button>
        <div id="result" style="display:none"></div>
        <script src="https://widget.cloudpayments.ru/bundles/cloudpayments?cms=1CBitrix"
                async></script>
        <script type="text/javascript">
            const payHandler = function () {
                const widget = new cp.CloudPayments({language: '<?=$lang_widget?>'});
                const w_options = { // options
                    publicId: '<?=trim(htmlspecialcharsbx($params["APIPASS"]));?>',
                    description: '<?=$description?>',
                    amount: <?=number_format($params['PAYMENT_SHOULD_PAY'], 2, '.', '')?>,
                    currency: '<?=$params['PAYMENT_CURRENCY']?>',
                    email: '<?=$params['PAYMENT_BUYER_EMAIL']?>',
                    invoiceId: '<?=htmlspecialcharsbx($params["ORDER_ID"]);?>',
                    accountId: '<?=htmlspecialcharsbx($params["PAYMENT_BUYER_ID"]);?>',
                    skin: '<?=$skin?>',
                }
							
							<?if ($params['CHECKONLINE'] != 'N')
							echo "w_options.data =" . CUtil::PhpToJSObject($data, false, true)?>

                widget.<?=$widget_f?>(
                    w_options,
                    function (options) { // success
                        BX("result").innerHTML = "<?=GetMessage('VBCH_CLOUDPAY_SUCCESS')?>";
                        BX.style(BX("result"), "color", "green");
                        BX.style(BX("result"), "display", "block");
											
											<?if ($params['SUCCESS_URL'])
											echo "window.location.href = " . $params['SUCCESS_URL']."?InvId=".htmlspecialcharsbx($params["ORDER_ID"]); ?>
                    },
                    function (reason, options) { // fail
                        BX("result").innerHTML = reason;
                        BX.style(BX("result"), "color", "red");
                        BX.style(BX("result"), "display", "block");
											<?if ($params['FAIL_URL'])
											echo "window.location.href = " . $params['FAIL_URL']."?InvId=".htmlspecialcharsbx($params["ORDER_ID"]); ?>
                    }
                );
            };
            document.getElementById('payButton').addEventListener('click', payHandler)
        </script>
      </div>
			<?
        elseif ($order->isCanceled() or $status_id == $params['STATUS_CHANCEL']):
			echo 'ORDER_CANCELED';
        endif;
	}
?>