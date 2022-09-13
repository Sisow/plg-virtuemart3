<?php
defined('_JEXEC') or die('Restricted access');

if(!class_exists('Sisow'))
	jimport('sisow.base');

class plgVmPaymentSisow_Mastercard extends SisowBase
{
	function __construct(& $subject, $config) {
		$this->paymentcode = 'mastercard';
		$this->paymentname = 'Mastercard';
		$this->redirect = true;
		parent::__construct($subject, $config);
	}
}