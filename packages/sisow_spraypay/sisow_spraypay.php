<?php
defined('_JEXEC') or die('Restricted access');

if(!class_exists('Sisow'))
	jimport('sisow.base');

class plgVmPaymentSisow_Spraypay extends SisowBase
{
	function __construct(& $subject, $config) {
		$this->paymentcode = 'spraypay';
		$this->paymentname = 'Spraypay';
		$this->redirect = true;
		parent::__construct($subject, $config);
	}
}