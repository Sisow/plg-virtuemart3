<?php
defined('_JEXEC') or die('Restricted access');

if(!class_exists('Sisow'))
	jimport('sisow.base');

class plgVmPaymentSisow_Bunq extends SisowBase
{
	function __construct(& $subject, $config) {
		$this->paymentcode = 'bunq';
		$this->paymentname = 'bunq';
		$this->redirect = true;
		parent::__construct($subject, $config);
	}
}