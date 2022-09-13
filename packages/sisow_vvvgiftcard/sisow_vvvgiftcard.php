<?php
defined('_JEXEC') or die('Restricted access');

if(!class_exists('Sisow'))
	jimport('sisow.base');

class plgVmPaymentSisow_Vvvgiftcard extends SisowBase
{
	function __construct(& $subject, $config) {
		$this->paymentcode = 'vvv';
		$this->paymentname = 'VVV Giftcard';
		$this->redirect = true;
		parent::__construct($subject, $config);
	}
}