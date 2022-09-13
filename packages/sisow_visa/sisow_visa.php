<?php
defined('_JEXEC') or die('Restricted access');

if(!class_exists('Sisow'))
	jimport('sisow.base');

class plgVmPaymentSisow_Visa extends SisowBase
{
	function __construct(& $subject, $config) {
		$this->paymentcode = 'visa';
		$this->paymentname = 'Visa';
		$this->redirect = true;
		parent::__construct($subject, $config);
	}
}