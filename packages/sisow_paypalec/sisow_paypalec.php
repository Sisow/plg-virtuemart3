<?php
defined('_JEXEC') or die('Restricted access');

if(!class_exists('Sisow'))
	jimport('sisow.base');

class plgVmPaymentSisow_Paypalec extends SisowBase
{
	function __construct(& $subject, $config) {
		$this->paymentcode = 'paypalec';
		$this->paymentname = 'PayPal Express Checkout';
		$this->redirect = true;
		parent::__construct($subject, $config);
	}
}