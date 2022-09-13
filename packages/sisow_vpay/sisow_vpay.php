<?php
defined('_JEXEC') or die('Restricted access');

if(!class_exists('Sisow'))
	jimport('sisow.base');

class plgVmPaymentSisow_Vpay extends SisowBase
{
	function __construct(& $subject, $config) {
		$this->paymentcode = 'vpay';
		$this->paymentname = 'V PAY';
		$this->redirect = true;
		parent::__construct($subject, $config);
	}
}