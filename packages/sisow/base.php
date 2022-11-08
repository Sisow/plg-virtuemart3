<?php
if (!class_exists('vmPSPlugin')) {
	require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');
}

if(!class_exists('Sisow'))
	jimport('sisow.sisowcls5');

class SisowBase extends vmPSPlugin {
	const RELEASE = 'VM 3.0.3';
	const PAYMENT_CURRENCY_CODE_3 = 'EUR';
	
	function __construct(& $subject, $config) {

		parent::__construct($subject, $config);
		$this->_loggable = TRUE;
		$this->tableFields = array_keys($this->getTableSQLFields());
		$this->_tablepkey = 'id'; //virtuemart_sofort_id';
		$this->_tableId = 'id'; //'virtuemart_sofort_id';
		$this->setConfigParameterable($this->_configTableFieldName, $this->getVarsToPush());
	}
	
	public function getVmPluginCreateTableSQL() {
		return $this->createTableSQL('Payment Sisow Ideal Table');
	}
	
	function getTableSQLFields() {
		$SQLfields = array(
			'id' => 'int(11) UNSIGNED NOT NULL AUTO_INCREMENT',
			'virtuemart_order_id' => 'int(1) UNSIGNED',
			'order_number' => 'char(64)',
			'virtuemart_paymentmethod_id' => 'mediumint(1) UNSIGNED',
			'payment_name' => 'varchar(1000)',
			'payment_order_total' => 'decimal(15,5) NOT NULL',
			'payment_currency' => 'smallint(1)',
			'cost_per_transaction' => 'decimal(10,2)',
			'cost_percent_total' => 'decimal(10,2)',
			'tax_id' => 'smallint(1)',
			'entrancecode' => 'varchar(255)',
			'transactionid' => 'varchar(255)',
			'accountholder' => 'varchar(255)',
			'accountiban' => 'varchar(255)',
			'accountbic' => 'varchar(255)',
			'refunded' => 'int(1) '
		);
		return $SQLfields;
	}
	
	function plgVmDisplayListFEPayment(VirtueMartCart $cart, $selected = 0, &$htmlIn) {

		if ($this->getPluginMethods($cart->vendorId) === 0) {
			if (empty($this->_name)) {
				$app = JFactory::getApplication();
				$app->enqueueMessage(vmText::_('COM_VIRTUEMART_CART_NO_' . strtoupper($this->_psType)));
				return false;
			} else {
				return false;
			}
		}
		$htmla = array();
		$html = '';		
		$method_name = $this->_psType . '_name';
		vmdebug('methods', $this->methods);
		VmConfig::loadJLang('com_virtuemart');
		$currency = CurrencyDisplay::getInstance();
		$ret = FALSE;
		foreach ($this->methods as $method) {
			if ($this->checkConditions($cart, $method, $cart->cartPrices)) {				
				$methodSalesPrice = $this->calculateSalesPrice($cart, $method, $cart->cartPrices);											
				$method->$method_name = $this->renderPluginName($method,'checkout');
				$html = $this->getPluginHtml($method, $selected, $methodSalesPrice);

				$this->payment_cost = '';
				if ($methodSalesPrice) {					
					$this->payment_cost = $currency->priceDisplay($methodSalesPrice);
				}
				
				$htmla[] = $html;
			}
		}
		if (!empty($htmla)) {
			$htmlIn[] = $htmla;
			$ret = TRUE;
		}

		return $ret;
	}
	
	function plgVmConfirmedOrder($cart, $order) {

		if (!($method = $this->getVmPluginMethod($order['details']['BT']->virtuemart_paymentmethod_id))) {
			return NULL; // Another method was selected, do nothing
		}
		if (!$this->selectedThisElement($method->payment_element)) {
			return FALSE;
		}
		$db =& JFactory::getDBO();
		$this->setInConfirmOrder($cart);
		$session = JFactory::getSession();
		
		$country = shopFunctions::getCountryByID($order['details']['ST']->virtuemart_country_id);
		
		if (isset($this->errors) && count ($this->errors) > 0) {			
			foreach ($this->errors as $error) {
				$msg .= "- " . $error . "<br>";
			}
			unset($errors);
			$mainframe = JFactory::getApplication ();
			$mainframe->enqueueMessage ($html);
			$mainframe->redirect (JRoute::_ ('index.php?option=com_virtuemart&view=cart',FALSE), $msg,'error');			
			// Redirect to cart prevent problems with one page checkout not stopping on errors.
			exit;		
		}

		$arg = array();
		$arg['ipaddress'] = $_SERVER['REMOTE_ADDR'];
		//Software Headers
		$arg['PlatformName'] = 'VirtueMart';
		$arg['PlatformVersion'] = RELEASE;
		$arg['ModuleSupplier'] = 'Buckaroo B.V.';
		$arg['ModuleName'] = 'Buckaroo (former Sisow) VirtueMart';
		$arg['ModuleVersion'] = '5.4.1';

		$arg['testmode'] = $method->sisow_testmode == '1' ? 'true' : 'false';
		$arg['shipping_firstname'] = !empty($order['details']['ST']->first_name) ? $order['details']['ST']->first_name : $order['details']['BT']->first_name;
		$arg['shipping_lastname'] = !empty($order['details']['ST']->last_name) ? $order['details']['ST']->last_name : $order['details']['BT']->last_name;
		$arg['shipping_mail'] = !empty($order['details']['ST']->email) ? $order['details']['ST']->email : $order['details']['BT']->email;
		$arg['shipping_company'] = !empty($order['details']['ST']->company) ? $order['details']['ST']->company : $order['details']['BT']->company;
		$arg['shipping_address1'] = !empty($order['details']['ST']->address_1) ? $order['details']['ST']->address_1 : $order['details']['BT']->address_1;
		$arg['shipping_address2'] = !empty($order['details']['ST']->address_2) ? $order['details']['ST']->address_2 : $order['details']['BT']->address_2;
		$arg['shipping_zip'] = !empty($order['details']['ST']->zip) ? $order['details']['ST']->zip : $order['details']['BT']->zip;
		$arg['shipping_city'] = !empty($order['details']['ST']->city) ? $order['details']['ST']->city : $order['details']['BT']->city;
		$arg['shipping_country'] = shopFunctions::getCountryByID(!empty($order['details']['ST']->virtuemart_country_id) ? $order['details']['ST']->virtuemart_country_id : $order['details']['BT']->virtuemart_country_id);	
		$arg['shipping_countrycode'] = shopFunctions::getCountryByID(!empty($order['details']['ST']->virtuemart_country_id) ? $order['details']['ST']->virtuemart_country_id : $order['details']['BT']->virtuemart_country_id, 'country_2_code');
		$arg['shipping_phone'] = !empty($order['details']['ST']->phone_1) ? $order['details']['ST']->phone_1 : $order['details']['BT']->phone_1;
		//$arg['customer'] = $order['details']['BT']->customer_number;
		$arg['billing_firstname'] = $order['details']['BT']->first_name;
		$arg['billing_lastname'] = $order['details']['BT']->last_name;
		$arg['billing_mail'] = $order['details']['BT']->email;
		$arg['billing_company'] = $order['details']['BT']->company;
		$arg['billing_address1'] = $order['details']['BT']->address_1;
		$arg['billing_address2'] = $order['details']['BT']->address_2;
		$arg['billing_zip'] = $order['details']['BT']->zip;
		$arg['billing_city'] = $order['details']['BT']->city;
		$arg['billing_country'] = shopFunctions::getCountryByID($order['details']['BT']->virtuemart_country_id);
		$arg['billing_countrycode'] = shopFunctions::getCountryByID($order['details']['BT']->virtuemart_country_id, 'country_2_code');
		$arg['billing_phone'] = $order['details']['BT']->phone_1;
		$arg['tax'] = round($order['details']['BT']->order_billTaxAmount * 100.0, 0);
		$arg['currency'] = shopFunctions::getCurrencyByID($order['details']['BT']->order_currency, 'currency_code_3');
				
		$productid = 1;
		foreach($order['items'] as $item)
		{
			$item_tax_percent = 0;
			foreach ($order['calc_rules'] as $calc_rule) {
				if ($calc_rule->virtuemart_order_item_id == $item->virtuemart_order_item_id AND $calc_rule->calc_kind == 'VatTax') {
					$item_tax_percent = $calc_rule->calc_value;
					break;
				}
			}
			
			$arg['product_id_' . $productid] = utf8_decode($item->order_item_sku);
			$arg['product_description_' . $productid] = utf8_decode(strip_tags($item->order_item_name));
			$arg['product_quantity_' . $productid] = $item->product_quantity;
			$arg['product_netprice_' . $productid] = round($item->product_item_price * 100.0, 0);
			$arg['product_total_' . $productid] = round($item->product_subtotal_with_tax * 100.0, 0);
			$arg['product_nettotal_' . $productid] = round(($item->product_quantity * $arg['product_netprice_' . $productid]), 0);
			$arg['product_tax_' . $productid] = $arg['product_total_' . $productid] - $arg['product_nettotal_' . $productid];
			$arg['product_taxrate_' . $productid] = Round($item_tax_percent * 100.0, 0);
			$productid++;

			$discount_tax_percent = 0.0;
			if ($item->product_subtotal_discount != 0.0) {
				if ($item->product_subtotal_discount > 0.0) {
					$discount_tax_percent = $item_tax_percent;
					$includeVat = 0;
				}
				$name = utf8_decode(strip_tags($item->order_item_name)) . ' (' . vmText::_('VMPAYMENT_SISOW_DISCOUNT') . ')';
				$arg['product_id_' . $productid] = utf8_decode($item->order_item_sku);
				$arg['product_description_' . $productid] = $name;
				$arg['product_quantity_' . $productid] = "1";
				$arg['product_netprice_' . $productid] = round( (($item->product_subtotal_discount * 100.0) / ($discount_tax_percent + 100)) * 100.0, 0);
				$arg['product_total_' . $productid] = round(((double)(round(abs($item->product_subtotal_discount), 2)) * -1) * 100.0, 0);
				$arg['product_nettotal_' . $productid] = $arg['product_netprice_' . $productid];
				$arg['product_tax_' . $productid] = $arg['product_total_' . $productid] - $arg['product_nettotal_' . $productid];
				$arg['product_taxrate_' . $productid] = $discount_tax_percent;
				$productid++;
			}
		}
				
		if($order['details']['BT']->order_shipment > 0)
		{
			foreach ($order['calc_rules'] as $calc_rule) {
				if ($calc_rule->calc_kind == 'shipment') {
					$shipment_tax_percent = $calc_rule->calc_value;
					break;
				}
			}
			
			$arg['product_id_' . $productid] = "shipping";
			$arg['product_description_' . $productid] = vmText::_('VMPAYMENT_SISOW_SHIPPING');
			$arg['product_quantity_' . $productid] = "1";
			$arg['product_netprice_' . $productid] = round($order['details']['BT']->order_shipment * 100.0, 0);
			$arg['product_total_' . $productid] = round( ($order['details']['BT']->order_shipment + $order['details']['BT']->order_shipment_tax) * 100.0, 0);
			$arg['product_nettotal_' . $productid] = round($order['details']['BT']->order_shipment * 100.0, 0);
			$arg['product_tax_' . $productid] = round($order['details']['BT']->order_shipment_tax * 100.0, 0);
			$arg['product_taxrate_' . $productid] = Round($shipment_tax_percent * 100.0, 0);
			$productid++;
		}
		
		if($order['details']['BT']->order_payment > 0)
		{
			foreach ($order['calc_rules'] as $calc_rule) {
				if ($calc_rule->calc_kind == 'payment') {
					$payment_tax_percent = $calc_rule->calc_value;
					break;
				}
			}			
			
			$arg['product_id_' . $productid] = "paymentfee";
			$arg['product_description_' . $productid] = vmText::_('VMPAYMENT_SISOW_PAYMENTFEE');
			$arg['product_quantity_' . $productid] = "1";
			$arg['product_netprice_' . $productid] = round($order['details']['BT']->order_payment * 100.0, 0);
			$arg['product_total_' . $productid] = round( ($order['details']['BT']->order_payment + $order['details']['BT']->order_payment_tax) * 100.0, 0);
			$arg['product_nettotal_' . $productid] = round($order['details']['BT']->order_payment * 100.0, 0);
			$arg['product_tax_' . $productid] = round($order['details']['BT']->order_payment_tax * 100.0, 0);
			$arg['product_taxrate_' . $productid] = Round($payment_tax_percent * 100.0, 0);
			$productid++;
		}
		
		if($order['details']['BT']->coupon_discount < 0)
		{
			foreach ($order['calc_rules'] as $calc_rule) {
				if ($calc_rule->calc_kind == 'payment') {
					$payment_tax_percent = $calc_rule->calc_value;
					break;
				}
			}			
			
			$arg['product_id_' . $productid] = "coupon";
			$arg['product_description_' . $productid] = utf8_decode(vmText::_('VMPAYMENT_SISOW_COUPON')) . ' ' . utf8_decode($order['details']['BT']->coupon_code);
			$arg['product_quantity_' . $productid] = "1";
			$arg['product_netprice_' . $productid] = round($order['details']['BT']->coupon_discount * 100.0, 0);
			$arg['product_total_' . $productid] = round( $order['details']['BT']->coupon_discount * 100.0, 0);
			$arg['product_nettotal_' . $productid] = round($order['details']['BT']->coupon_discount * 100.0, 0);
			$arg['product_tax_' . $productid] = "0";
			$arg['product_taxrate_' . $productid] = "0";
			$productid++;
		}
		
		if($order['details']['BT']->order_billDiscountAmount < 0)
		{
			foreach ($order['calc_rules'] as $calc_rule) {
				if ($calc_rule->calc_kind == 'payment') {
					$payment_tax_percent = $calc_rule->calc_value;
					break;
				}
			}			
			
			$arg['product_id_' . $productid] = "billdiscount";
			$arg['product_description_' . $productid] = utf8_decode(vmText::_('VMPAYMENT_SISOW_BILLDISCOUNT')) . ' ' . utf8_decode($order['details']['BT']->coupon_code);
			$arg['product_quantity_' . $productid] = "1";
			$arg['product_netprice_' . $productid] = round($order['details']['BT']->order_billDiscountAmount * 100.0, 0);
			$arg['product_total_' . $productid] = round( $order['details']['BT']->order_billDiscountAmount * 100.0, 0);
			$arg['product_nettotal_' . $productid] = round($order['details']['BT']->order_billDiscountAmount * 100.0, 0);
			$arg['product_tax_' . $productid] = "0";
			$arg['product_taxrate_' . $productid] = "0";
			$productid++;
		}

		if($this->paymentcode == 'overboeking' || $this->paymentcode == 'ebill')
		{
			$arg['including'] = $method->sisow_including == "1" ? "true" : "false";
			$arg['days'] = $method->sisow_days;
		}
		
		$sisow = new Sisow($method->sisow_merchantid, $method->sisow_merchantkey, $method->sisow_shopid);
		$sisow->entranceCode = $order['details']['BT']->virtuemart_order_id;
		$sisow->purchaseId = $order['details']['BT']->order_number;
		$sisow->amount = round($order['details']['BT']->order_total, 2);
		$sisow->payment = $this->paymentcode;
		
		//Load User input values
		$mainframe = JFactory::getApplication();
		$bic = $this->paymentcode == 'eps' ? $mainframe->getUserState( "biceps" ) : $this->paymentcode == 'giropay' ? $mainframe->getUserState( "bicgiropay" ) : '';
		if(!empty($bic))
			$arg['bic'] = $bic;
		
		$iban = $mainframe->getUserState( "iban" );
		if(!empty($iban))
			$arg['iban'] = $mainframe->getUserState( "iban" );
		
		$coc = $mainframe->getUserState( "coc" );
		if(!empty($coc))
			$arg['billing_coc'] = $mainframe->getUserState( "coc" );
		
		$dag = $mainframe->getUserState( "dag" );
		$maand = $mainframe->getUserState( "maand" );
		$jaar = $mainframe->getUserState( "jaar" );
		if(!empty($dag) && !empty($maand) && !empty($jaar))
			$arg['birthdate'] = $dag . $maand . $jaar;
		
		$gender = $mainframe->getUserState( "gender" );
		if(!empty($gender))
			$arg['gender'] = $mainframe->getUserState( "gender" );
		
		$initials = $mainframe->getUserState( "initials" );
		if(!empty($initials))
			$arg['initials'] = $mainframe->getUserState( "initials" );
		
		$issuerid = $mainframe->getUserState( "issuerid" );
		if(!empty($issuerid))
			$sisow->issuerId = $mainframe->getUserState( "issuerid" );
		
		if(!empty($method->sisow_description))
		{
			$sisow->description = $method->sisow_description . " " . $order['details']['BT']->order_number;
		}
		else
		{
			$config = JFactory::getConfig();
			$sisow->description = $config->get( 'sitename' ) . " " . $order['details']['BT']->order_number;
		}
		$session = JFactory::getSession();		
		$sisow->notifyUrl = JURI::root() . "index.php?option=com_virtuemart&view=pluginresponse&tmpl=component&task=pluginnotification&on=" . $order['details']['BT']->order_number . "&sid=" . $session->getId() . '&pm=' . $order['details']['BT']->virtuemart_paymentmethod_id;
		$sisow->returnUrl = JURI::root() . "index.php?option=com_virtuemart&view=pluginresponse&task=pluginresponsereceived&pm=" . $order['details']['BT']->virtuemart_paymentmethod_id . '&on=' . $order['details']['BT']->order_number;
		$sisow->cancelUrl = JURI::root() . "index.php?option=com_virtuemart&view=pluginresponse&task=pluginUserPaymentCancel&pm=" . $order['details']['BT']->virtuemart_paymentmethod_id . '&on=' . $order['details']['BT']->order_number;
		$sisow->callbackUrl = $sisow->notifyUrl;
		
		$modelOrder = VmModel::getModel ('orders');

		if(($ex = $sisow->TransactionRequest($arg)) < 0)
		{
			$order['customer_notified'] = 0;
			$order['order_status'] = $method->status_failure;
			$order['comments'] = $ex . " " . $sisow->errorCode;
			$modelOrder->updateStatusForOneOrder ($order['details']['BT']->virtuemart_order_id, $order, TRUE);

            $app = JFactory::getApplication ();
			$app->enqueueMessage (vmText::_('VMPAYMENT_SISOW_PAYMENT_FAILED') . '('.$ex.', '.$sisow->errorCode.')', 'error');
			$app->redirect (JRoute::_ ('index.php?option=com_virtuemart&view=cart&task=editpayment'));
			exit;
		} 
		else
		{
			if($order['details']['BT']->order_status != $method->status_pending)
			{
				$order['customer_notified'] = 0;
				$order['order_status'] = $method->status_pending;
				$modelOrder->updateStatusForOneOrder ($order['details']['BT']->virtuemart_order_id, $order, TRUE);
			}
			
			$dbValues = array();
			$dbValues['order_number'] = $order['details']['BT']->order_number;
			$dbValues['payment_name'] = $this->renderPluginName($method, 'order');
			$dbValues['virtuemart_paymentmethod_id'] = $cart->virtuemart_paymentmethod_id;
			$dbValues['cost_per_transaction'] = $method->cost_per_transaction;
			$dbValues['cost_percent_total'] = $method->cost_percent_total;
			$dbValues['tax_id'] = $method->tax_id;
			$this->storePSPluginInternalData($dbValues);
			
			if($this->redirect)
			{
				$app = JFactory::getApplication ();
				$app->redirect ($sisow->issuerUrl);
				exit;
			}
			else
			{
				if (!class_exists('VirtueMartCart')) {
					require(VMPATH_SITE . DS . 'helpers' . DS . 'cart.php');
				}
				
				//We delete the old stuff
				// get the correct cart / session
				$cart = VirtueMartCart::getCart();
				$cart->emptyCart();
			}
		}
	}
	
	function plgVmOnPaymentNotification() {
		// the payment itself should send the parameter needed.
		$virtuemart_paymentmethod_id = vRequest::getInt('pm', 0);

		if (!($method = $this->getVmPluginMethod($virtuemart_paymentmethod_id))) {
			return NULL; // Another method was selected, do nothing
		}	
		
		if (!class_exists('VirtueMartModelOrders')) {
			require(VMPATH_ADMIN . DS . 'models' . DS . 'orders.php');
		}

		if( sha1(vRequest::getString('trxid') . vRequest::getString('ec') . vRequest::getString('status') . $method->sisow_merchantid . $method->sisow_merchantkey) != vRequest::getString('sha1')) {
			exit('URL not authentic');
		}
				
		$orderModel = VmModel::getModel('orders');
		
		$sisow = new Sisow($method->sisow_merchantid, $method->sisow_merchantkey, $method->sisow_shopid);
		if($sisow->StatusRequest(vRequest::getString('trxid')) == 0)
		{			
			$order = $orderModel->getOrder(vRequest::getString('ec'));
			$orderdata = array();
			
			if($order['details']['BT']->order_status == $method->status_success && $sisow->status != Sisow::statusReversed && $sisow->status != Sisow::statusRefunded)
				exit("already success state");
			
			switch($sisow->status)
			{
				case Sisow::statusSuccess:
					$orderdata['order_status'] = $method->status_success;
					break;
				case Sisow::statusCancelled:
					if($order['details']['BT']->order_status == $method->status_pending)
						$orderdata['order_status'] = $method->status_cancelled;
					break;
				case Sisow::statusExpired:
					if($order['details']['BT']->order_status == $method->status_pending)
						$orderdata['order_status'] = $method->status_expired;
					break;
				case Sisow::statusFailure:
					if($order['details']['BT']->order_status == $method->status_pending)
						$orderdata['order_status'] = $method->status_failure;
					break;
				case Sisow::statusReversed:
					$orderdata['order_status'] = $method->status_reversed;
					break;
				case Sisow::statusRefunded:
					$orderdata['order_status'] = $method->status_refund;
					break;
				case Sisow::statusPending:
					$orderdata['order_status'] = $method->status_pending;
					break;
				default:
					exit('Status not supported: ' . $sisow->status);
			}

			if(!empty($orderdata['order_status']))
			{
				$orderdata['customer_notified'] = 1;
				$orderModel->updateStatusForOneOrder($order['details']['BT']->virtuemart_order_id, $orderdata, true);
			}
			
			$tempSid = vRequest::getString('sid'); 
			if (!empty($tempSid)) {
				$this->emptyCart(vRequest::getString('sid'), $order_number);
			}
		}
		exit;
	}
	
	/**
	 * @param $html
	 * @return bool|null|string
	 */
	function plgVmOnPaymentResponseReceived(&$html) {
		if (!class_exists('VirtueMartCart')) {
			require(VMPATH_SITE . DS . 'helpers' . DS . 'cart.php');
		}
		if (!class_exists('shopFunctionsF')) {
			require(VMPATH_SITE . DS . 'helpers' . DS . 'shopfunctionsf.php');
		}
		if (!class_exists('VirtueMartModelOrders')) {
			require(VMPATH_ADMIN . DS . 'models' . DS . 'orders.php');
		}
		VmConfig::loadJLang('com_virtuemart_orders', TRUE);

		// the payment itself should send the parameter needed.
		$virtuemart_paymentmethod_id = vRequest::getInt('pm', 0);

		$order_number = vRequest::getString('on', 0);
		$vendorId = 0;
		if (!($this->_currentMethod = $this->getVmPluginMethod($virtuemart_paymentmethod_id))) {
			return NULL; // Another method was selected, do nothing
		}
		if (!$this->selectedThisElement($this->_currentMethod->payment_element)) {
			return NULL;
		}

		if (!($virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($order_number))) {
			return NULL;
		}
		
		$payment_name = $this->renderPluginName($this->_currentMethod);

		VmConfig::loadJLang('com_virtuemart');
		$orderModel = VmModel::getModel('orders');
		$order = $orderModel->getOrder($virtuemart_order_id);
		
		$html = '';
		$html .= '<br />
<style>
	.paypalordersummary td {padding:10px;}
</style>
<table cellpadding="2" class="paypal_ordersummary">';
	$html .= $this->getHtmlRow('VMPAYMENT_SISOW_PAYMENT_NAME',  $payment_name);
	$html .= $this->getHtmlRow('COM_VIRTUEMART_ORDER_NUMBER', $order_number);
	//$html .= $this->getHtmlRow('VMPAYMENT_PAYPAL_API_AMOUNT', $response['PAYMENTINFO_0_AMT'] . ' ' . $response['PAYMENTINFO_0_CURRENCYCODE']);

	$html .= '</table>
	<br />
	<a class="vm-button-correct" href="'. JRoute::_('index.php?option=com_virtuemart&view=orders&layout=details&order_number='.$order['details']['BT']->order_number.'&order_pass='.$order['details']['BT']->order_pass, false).'">'. vmText::_('COM_VIRTUEMART_ORDER_VIEW_ORDER').' </a>';


		//We delete the old stuff
		// get the correct cart / session
		$cart = VirtueMartCart::getCart();
		$cart->emptyCart();
		return TRUE;
	}
	
	function plgVmOnUserPaymentCancel() {

		if (!class_exists('VirtueMartModelOrders')) {
			require(VMPATH_ADMIN . DS . 'models' . DS . 'orders.php');
		}

		$order_number = vRequest::getString('on', '');
		$virtuemart_paymentmethod_id = vRequest::getInt('pm', '');
		if (empty($order_number) or empty($virtuemart_paymentmethod_id) or !$this->selectedThisByMethodId($virtuemart_paymentmethod_id)) {
			return NULL;
		}
		if (!($virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($order_number))) {
			return NULL;
		}
		if (!($paymentTable = $this->getDataByOrderNumber($order_number))) {
			return NULL;
		}

		VmInfo(vmText::_('VMPAYMENT_SISOW_IDEAL_PAYMENT_CANCELLED'));
		$session = JFactory::getSession();
		$return_context = $session->getId();
		if (strcmp($paymentTable->paypal_custom, $return_context) === 0) {
			$this->handlePaymentUserCancel($virtuemart_order_id);
		}
		return TRUE;
	}
	
	protected function getSisowLogos($method) {
		if ($method->sisow_payment_logos == "1") {		
			$pos = strpos($method->payment_element,'_');
			if ($pos !== false) {
				$logo = substr($method->payment_element, $pos+1);				
				
				$url = "plugins/vmpayment/".$method->payment_element;
				$logo.=".png";

				if(JFile::exists(VMPATH_ROOT .'/'. $url .'/'. $logo)){
					$alt_text = $method->payment_name;					
					return '<span class="vmCart' . ucfirst($this->_psType) . 'Logo" ><img align="middle" src="' . JUri::root().$url.'/'.$logo . '"  alt="' . $alt_text . '" /></span> ';
				}
			}
		}
	}

	protected function renderPluginName($method, $where = '') {
		if (empty($where) || $where == 'order') 
			return $this->paymentname;

		// checkout
		$return = '';

		if ($method->sisow_payment_logos == "1") {
			$logo =	$this->getSisowLogos($method);
		} else {
			$logo = $this->displayLogos($method->payment_logos);	
		}
		if (!empty($logo)) {
			$return = '<span class="vmpayment_logo">'.$logo.'</span>';
		}		
		if (array_key_exists('sisow_testmode',$method) && $method->sisow_testmode == "1") {
			$test_text = '<span style="color:red;font-weight:bold"> [Testmode]</span>';			
		}

		if (!empty($method->payment_desc )) {
			$description =  '<span class="vmpayment_description">'.$method->payment_desc.'</span>';
		}		

		$return .= '<span class="vmpayment_name">' . $this->paymentname . $test_text. '</span>' . $description;

		return $return;
	}
	
	protected function checkConditions($cart, $method, $cart_prices) {
		if($cart->BT != 0)
		{
			//land controleren
			if(is_array($method->countries) && !in_array($cart->BT['virtuemart_country_id'], $method->countries))
				return false;
			else if(!empty($method->countries) && $cart->BT['virtuemart_country_id'] != $method->countries)
					return false;
		}
		
		//currency valid?
		if($method->payment_currency > 0)
			if($method->payment_currency != CurrencyDisplay::getInstance()->getId())
				$enbabled = FALSE;
		
		//merchant id en key?
		if( empty($method->sisow_merchantid) || empty($method->sisow_merchantkey) )
			return false;			

		//price valid?
		$amount = $this->getCartAmount($cart_prices);
		if(!empty($method->min_amount) && $amount < $method->min_amount)
			return false;
			
		if(!empty($method->max_amount) && $amount > $method->max_amount)
			return false;
		
		return true;
	}

	/**
	 * We must reimplement this triggers for joomla 1.7
	 */

	/**
	 * Create the table for this plugin if it does not yet exist.
	 * This functions checks if the called plugin is active one.
	 * When yes it is calling the standard method to create the tables
	 *
	 * @author Val�rie Isaksen
	 *
	 */
	function plgVmOnStoreInstallPaymentPluginTable($jplugin_id) {

		return $this->onStoreInstallPluginTable($jplugin_id);
	}
	
	/**
	 * This event is fired after the payment method has been selected. It can be used to store
	 * additional payment info in the cart.
	 *
	 * @author Max Milbers
	 * @author Val�rie isaksen
	 *
	 * @param VirtueMartCart $cart: the actual cart
	 * @return null if the payment was not selected, true if the data is valid, error message if the data is not vlaid
	 *
	 */
	public function plgVmOnSelectCheckPayment (VirtueMartCart $cart, &$msg) {
		return $this->OnSelectCheck ($cart);
	}
	

	/*
		 * plgVmonSelectedCalculatePricePayment
		 * Calculate the price (value, tax_id) of the selected method
		 * It is called by the calculator
		 * This function does NOT to be reimplemented. If not reimplemented, then the default values from this function are taken.
		 * @author Valerie Isaksen
		 * @cart: VirtueMartCart the current cart
		 * @cart_prices: array the new cart prices
		 * @return null if the method was not selected, false if the payment is not valid any more, true otherwise
		 *
		 *
		 */

	/**
	 * @param VirtueMartCart $cart
	 * @param array $cart_prices
	 * @param                $cart_prices_name
	 * @return bool','null
	 */

	public function plgVmOnSelectedCalculatePricePayment(VirtueMartCart $cart, array &$cart_prices, &$cart_prices_name) {

		return $this->onSelectedCalculatePrice($cart, $cart_prices, $cart_prices_name);
	}


	/**
	 * plgVmOnCheckAutomaticSelectedPayment
	 * Checks how many plugins are available. If only one, the user will not have the choice. Enter edit_xxx page
	 * The plugin must check first if it is the correct type
	 *
	 * @author Valerie Isaksen
	 * @param VirtueMartCart cart: the cart object
	 * @return null if no plugin was found, 0 if more then one plugin was found,  virtuemart_xxx_id if only one plugin is found
	 *
	 */
	function plgVmOnCheckAutomaticSelectedPayment(VirtueMartCart $cart, array $cart_prices = array(), &$paymentCounter) {

		return $this->onCheckAutomaticSelected ($cart, $cart_prices, $paymentCounter);
	}

	/**
	 * This method is fired when showing the order details in the frontend.
	 * It displays the method-specific data.
	 *
	 * @param integer $order_id The order ID
	 * @return mixed Null for methods that aren't active, text (HTML) otherwise
	 * @author Valerie Isaksen
	 */
	public function plgVmOnShowOrderFEPayment($virtuemart_order_id, $virtuemart_paymentmethod_id, &$payment_name) {
		$this->onShowOrderFE($virtuemart_order_id, $virtuemart_paymentmethod_id, $payment_name);
	}


	/**
	 * This method is fired when showing when priting an Order
	 * It displays the the payment method-specific data.
	 *
	 * @param integer $_virtuemart_order_id The order ID
	 * @param integer $method_id method used for this order
	 * @return mixed Null when for payment methods that were not selected, text (HTML) otherwise
	 * @author Valerie Isaksen
	 */
	function plgVmonShowOrderPrintPayment($order_number, $method_id) {

		return $this->onShowOrderPrint($order_number, $method_id);
	}

	/**
	 * Save updated order data to the method specific table
	 *
	 * @param array $_formData Form data
	 * @return mixed, True on success, false on failures (the rest of the save-process will be
	 * skipped!), or null when this method is not actived.
	 *
	 * public function plgVmOnUpdateOrderPayment(  $_formData) {
	 * return null;
	 * }
	 */
	/**
	 * Save updated orderline data to the method specific table
	 *
	 * @param array $_formData Form data
	 * @return mixed, True on success, false on failures (the rest of the save-process will be
	 * skipped!), or null when this method is not actived.
	 *
	 * public function plgVmOnUpdateOrderLine(  $_formData) {
	 * return null;
	 * }
	 */
	/**
	 * plgVmOnEditOrderLineBE
	 * This method is fired when editing the order line details in the backend.
	 * It can be used to add line specific package codes
	 *
	 * @param integer $_orderId The order ID
	 * @param integer $_lineId
	 * @return mixed Null for method that aren't active, text (HTML) otherwise
	 *
	 * public function plgVmOnEditOrderLineBE(  $_orderId, $_lineId) {
	 * return null;
	 * }
	 */

	/**
	 * This method is fired when showing the order details in the frontend, for every orderline.
	 * It can be used to display line specific package codes, e.g. with a link to external tracking and
	 * tracing systems
	 *
	 * @param integer $_orderId The order ID
	 * @param integer $_lineId
	 * @return mixed Null for method that aren't active, text (HTML) otherwise
	 *
	 * public function plgVmOnShowOrderLineFE(  $_orderId, $_lineId) {
	 * return null;
	 * }
	 */
	function plgVmDeclarePluginParamsPaymentVM3(&$data) {
		return $this->declarePluginParams('payment', $data);
	}

	/**
	 * @param $name
	 * @param $id
	 * @param $table
	 * @return bool
	 */
	function plgVmSetOnTablePluginParamsPayment($name, $id, &$table) {
		return $this->setOnTablePluginParams($name, $id, $table);
	}
}